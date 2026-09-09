const { test, expect } = require('./fixtures.cjs');
const fs = require('node:fs');
const path = require('node:path');

fs.mkdirSync(path.resolve(__dirname, '..', '..', 'test-results', 'sessions'), { recursive: true });

async function jsonOrThrow(response, label) {
    if (!response.ok()) throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    return response.json();
}

async function criarJogoFixture(request) {
    const base = process.env.SGI_BASE_URL || 'http://localhost/SGI/';
    const api = (value) => new URL(value, base).href;
    await jsonOrThrow(await request.post(api('api/v1/login'), {
        data: { matricula: 'admin', senha: '123' },
    }), 'login administrativo');

    const edicoes = await jsonOrThrow(
        await request.get(api('api/v1/edicoes?regulamento=true')),
        'edições',
    );
    const edicao = edicoes.find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('Nenhuma edição ativa disponível para o teste de placar.');

    const idInterclasse = Number(edicao.id_interclasse);
    const [equipes, modalidades] = await Promise.all([
        request.get(api(`api/v1/equipes?id_interclasse=${idInterclasse}`)).then((response) => jsonOrThrow(response, 'equipes')),
        request.get(api(`api/v1/modalidades?id_interclasse=${idInterclasse}`)).then((response) => jsonOrThrow(response, 'modalidades')),
    ]);
    const modalidade = modalidades.find((item) =>
        String(item.nome_tipo_modalidade || '').toLowerCase().includes('mata') &&
        String(item.nome_modalidade || '').toLowerCase().includes('futsal')
    );
    if (!modalidade) throw new Error('Nenhuma modalidade de futsal mata-mata disponível.');
    const disponiveis = equipes.filter((item) => String(item.modalidades_id_modalidade) === String(modalidade.id_modalidade));
    if (disponiveis.length < 2) throw new Error('O fixture precisa de duas equipes.');

    const nomeJogo = `T10 Score ${Date.now()}`;
    await jsonOrThrow(await request.post(api('api/v1/sincronizacao/chaveamento'), {
        data: {
            id_modalidade: Number(modalidade.id_modalidade),
            tipo_modalidade: 'mata_mata',
            jogos: [{
                nome_jogo: nomeJogo,
                status_jogo: 'Agendado',
                partidas: [
                    { id_equipe: Number(disponiveis[0].id_equipe), resultado: 0 },
                    { id_equipe: Number(disponiveis[1].id_equipe), resultado: 0 },
                ],
            }],
        },
    }), 'criação do jogo');

    const jogos = await jsonOrThrow(
        await request.get(api(`api/v1/jogos?id_modalidade=${Number(modalidade.id_modalidade)}`)),
        'consulta do jogo',
    );
    const jogo = jogos.find((item) => String(item.nome_jogo) === nomeJogo);
    if (!jogo) throw new Error('A API não retornou o jogo criado.');
    const partidas = await jsonOrThrow(
        await request.get(api(`api/v1/partidas?id_jogo=${Number(jogo.id_jogo)}`)),
        'consulta das partidas',
    );
    return {
        idInterclasse,
        idJogo: Number(jogo.id_jogo),
        idsPartidas: partidas.map((item) => String(item.id_partida)),
    };
}

async function navegar(page, tela, params) {
    await page.evaluate(({ tela, params }) => {
        window.__SGI_SPA__.navegarPara(tela, params);
    }, { tela, params });
}

async function lerFila(page, idsPartidas) {
    return page.evaluate(async (ids) => {
        const fila = await window.SGIDataLayer.read('fila_sincronizacao');
        const partidas = new Set(ids.map((item) => String(item)));
        return fila.filter((item) => {
            if (!item || !String(item.url || '').includes('/api/v1/partidas')) return false;
            try {
                const body = typeof item.body === 'string' ? JSON.parse(item.body) : item.body;
                return partidas.has(String(body.id_partida));
            } catch (_) {
                return false;
            }
        }).sort((a, b) => Number(a.id) - Number(b.id));
    }, idsPartidas);
}

async function lerPlacarLocal(page, idJogo) {
    return page.evaluate(async (id) => {
        const partidas = await window.SGIDataLayer.read('partidas');
        return partidas
            .filter((item) => String(item.jogos_id_jogo ?? item.id_jogo) === String(id))
            .sort((a, b) => Number(a.id_partida) - Number(b.id_partida))
            .map((item) => Number(item.resultado_partida));
    }, idJogo);
}

async function clicarPlacar(page, seletor) {
    await page.locator(seletor).first().click();
    const artilheiro = page.locator('#modalArtilheiro');
    try {
        await artilheiro.waitFor({ state: 'visible', timeout: 2_000 });
    } catch (_) {
        // Modal é opcional para a ação de gol; não aguardar quando não foi aberto.
        return;
    }
    await artilheiro.locator('.btn-close').click();
    await expect(artilheiro).toBeHidden();
}

test.describe('Mesário — persistência imediata do placar', () => {
    test('sobrevive à desmontagem imediata, preserva a ordem e sincroniza o valor final', async ({ page, context, request }) => {
        test.setTimeout(180_000);
        const fixture = await criarJogoFixture(request);

        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill('mesario');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/painel\?id=\d+/, { waitUntil: 'domcontentloaded' });
        await navegar(page, 'jogos', { id_jogo: fixture.idJogo, origem: 'agenda_edit' });
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await page.getByRole('button', { name: /Iniciar jogo/i }).click();
        await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');

        // O cenário que reproduzia a perda do primeiro gol desmonta a tela
        // imediatamente após a ação, sem esperar qualquer janela de atraso.
        await clicarPlacar(page, '.btn-score-plus');
        await navegar(page, 'agenda', { id: fixture.idInterclasse });
        await expect(page.locator('#lista-eventos')).toBeVisible();
        await navegar(page, 'jogos', { id_jogo: fixture.idJogo, origem: 'agenda_edit' });
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await expect(page.locator('.score-number').first()).toHaveText('01');
        const artilheiro = page.locator('#modalArtilheiro');
        if (await artilheiro.isVisible()) {
            await artilheiro.locator('.btn-close').click();
            await expect(artilheiro).toBeHidden();
        }

        await context.setOffline(true);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(false);
        await clicarPlacar(page, '.btn-score-plus');
        await clicarPlacar(page, '.btn-score-plus');
        await clicarPlacar(page, '.btn-score-minus');
        await navegar(page, 'agenda', { id: fixture.idInterclasse });
        await expect(page.locator('#lista-eventos')).toBeVisible();
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBeGreaterThanOrEqual(3);

        const fila = await lerFila(page, fixture.idsPartidas);
        const corpos = fila.map((item) => typeof item.body === 'string' ? JSON.parse(item.body) : item.body);
        const sequencia = corpos.slice(-3).map((item) => Number(item.resultado_partida));
        expect(sequencia).toEqual([2, 3, 2]);

        await navegar(page, 'jogos', { id_jogo: fixture.idJogo, origem: 'agenda_edit' });
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await expect(page.locator('.score-number').first()).toHaveText('02');
        expect((await lerPlacarLocal(page, fixture.idJogo))[0]).toBe(2);

        await context.setOffline(false);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(true);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 45_000 }).toBe(0);

        const servidor = await page.evaluate(async (id) => {
            const response = await fetch(`/api/v1/partidas?id_jogo=${id}`);
            return response.json();
        }, fixture.idJogo);
        const resultados = servidor.map((item) => Number(item.resultado_partida));
        expect(resultados[0]).toBe(2);

        const dialogo = new Promise((resolve) => {
            page.once('dialog', async (dialog) => {
                const mensagem = dialog.message();
                await dialog.dismiss();
                resolve(mensagem);
            });
        });
        await page.evaluate(() => {
            window.__SGI_QUEUE_MUTATION_ORIGINAL__ = window.SGIOffline.queueMutation;
            window.SGIOffline.queueMutation = () => Promise.reject(new Error('IndexedDB indisponível'));
        });
        await page.locator('.btn-score-plus').first().click();
        expect(await dialogo).toContain('IndexedDB indisponível');
        const artilheiroFinal = page.locator('#modalArtilheiro');
        if (await artilheiroFinal.isVisible()) {
            await artilheiroFinal.locator('.btn-close').click();
            await expect(artilheiroFinal).toBeHidden();
        }
        await page.evaluate(() => {
            window.SGIOffline.queueMutation = window.__SGI_QUEUE_MUTATION_ORIGINAL__;
            delete window.__SGI_QUEUE_MUTATION_ORIGINAL__;
        });
    });
});
