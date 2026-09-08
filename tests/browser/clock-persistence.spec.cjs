const { test, expect } = require('./fixtures.cjs');
const fs = require('node:fs');
const path = require('node:path');

// O servidor de teste usa este diretório relativo para as sessões. O
// Playwright limpa o outputDir antes de carregar os testes, então recriamos
// somente a pasta descartável necessária ao login do navegador.
fs.mkdirSync(path.resolve(__dirname, '..', '..', 'test-results', 'sessions'), { recursive: true });

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

async function criarJogoFixture(request) {
    const base = process.env.SGI_BASE_URL || 'http://localhost/SGI/';
    const api = (path) => new URL(path, base).href;
    const login = await request.post(api('api/login.php'), {
        data: { matricula: 'admin', senha: '123' }
    });
    await jsonOrThrow(login, 'login administrativo');

    const edicoes = await jsonOrThrow(
        await request.get(api('api/interclasse.php?regulamento=true')),
        'edições',
    );
    const edicao = edicoes.find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('Nenhuma edição ativa disponível para o teste do cronômetro.');

    const idInterclasse = Number(edicao.id_interclasse);
    const [equipes, modalidades] = await Promise.all([
        request.get(api(`api/equipes.php?id_interclasse=${idInterclasse}`)).then((response) => jsonOrThrow(response, 'equipes')),
        request.get(api(`api/modalidades.php?id_interclasse=${idInterclasse}`)).then((response) => jsonOrThrow(response, 'modalidades')),
    ]);
    const modalidade = modalidades.find((item) =>
        String(item.nome_tipo_modalidade || '').toLowerCase().includes('mata') &&
        String(item.nome_modalidade || '').toLowerCase().includes('futsal')
    );
    if (!modalidade) throw new Error('Nenhuma modalidade de futsal mata-mata disponível.');

    const equipesDaModalidade = equipes.filter((item) =>
        String(item.modalidades_id_modalidade) === String(modalidade.id_modalidade)
    );
    if (equipesDaModalidade.length < 2) throw new Error('O fixture precisa de duas equipes.');

    const nomeJogo = `T09 Clock ${Date.now()}`;
    await jsonOrThrow(await request.post(api('api/sincronizar_chaveamento.php'), {
        data: {
            id_modalidade: Number(modalidade.id_modalidade),
            tipo_modalidade: 'mata_mata',
            jogos: [{
                nome_jogo: nomeJogo,
                status_jogo: 'Agendado',
                partidas: [
                    { id_equipe: Number(equipesDaModalidade[0].id_equipe), resultado: 0 },
                    { id_equipe: Number(equipesDaModalidade[1].id_equipe), resultado: 0 },
                ],
            }],
        },
    }), 'criação do jogo');

    const jogos = await jsonOrThrow(
        await request.get(api(`api/jogos.php?id_modalidade=${Number(modalidade.id_modalidade)}`)),
        'consulta do jogo',
    );
    const jogo = jogos.find((item) => String(item.nome_jogo) === nomeJogo);
    const idJogo = Number(jogo && jogo.id_jogo);
    if (!idJogo) throw new Error(`A API não retornou o ID do jogo: ${JSON.stringify(jogo)}`);
    return { idJogo, nomeJogo };
}

async function lerJogoLocal(page, idJogo) {
    return page.evaluate(async (id) => {
        const jogos = await window.SGIDataLayer.read('jogos');
        return jogos.find((jogo) => String(jogo.id_jogo) === String(id)) || null;
    }, idJogo);
}

async function lerJogoServidor(page, idJogo) {
    return page.evaluate(async (id) => {
        const response = await fetch(`../../../api/jogos.php?id_jogo=${id}`);
        const jogos = await response.json();
        return jogos[0] || null;
    }, idJogo);
}

async function lerMutacaoCronometro(page, idJogo) {
    return page.evaluate(async (id) => {
        const fila = await window.SGIDataLayer.read('fila_sincronizacao');
        return fila.find((item) => {
            if (!item || !String(item.url || '').includes('/jogos.php')) return false;
            try {
                const corpo = typeof item.body === 'string' ? JSON.parse(item.body) : item.body;
                return String(corpo.id_jogo) === String(id);
            } catch (_) {
                return false;
            }
        }) || null;
    }, idJogo);
}

async function lerTempoTela(page) {
    return page.locator('#timer-placar').textContent().then((texto) => {
        const [minutos, segundos] = String(texto).trim().split(':').map(Number);
        return (minutos * 60) + segundos;
    });
}

test.describe('Mesário — persistência do cronômetro', () => {
    test('preserva o saldo entre pausa, navegação, retomada e envio atrasado', async ({ page, context, request }) => {
        test.setTimeout(180_000);
        const fixture = await criarJogoFixture(request);
        const pageErrors = [];
        const dialogs = [];
        const timerResponses = [];
        page.on('pageerror', (error) => pageErrors.push(error.message));
        page.on('dialog', async (dialog) => {
            dialogs.push(dialog.message());
            await dialog.dismiss();
        });
        page.on('response', async (response) => {
            if (response.request().method() === 'PUT' && response.url().includes('/api/jogos.php')) {
                timerResponses.push({ status: response.status(), request: response.request().postData(), body: await response.text() });
            }
        });

        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill('mesario');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/dashboard\.php\?id=\d+/, { waitUntil: 'domcontentloaded' });

        await page.goto(`views/src/pages/jogos.php?id_jogo=${fixture.idJogo}`, {
            waitUntil: 'domcontentloaded',
        });
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        expect(await page.evaluate(() => typeof window.SGICronometro)).toBe('object');
        await expect(page.locator('#mc-status-badge')).toContainText('Agendado');

        await page.clock.install({ time: new Date(Date.now()) });
        await page.getByRole('button', { name: /Iniciar jogo/i }).click();
        await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');
        const saldoInicial = await lerTempoTela(page);
        expect(saldoInicial).toBeGreaterThanOrEqual(1198);
        expect(saldoInicial).toBeLessThanOrEqual(1200);

        await page.clock.fastForward(30_000);
        const saldoApos30 = await lerTempoTela(page);
        expect(saldoApos30).toBeGreaterThanOrEqual(1168);
        expect(saldoApos30).toBeLessThanOrEqual(1170);

        await page.locator('#btn-pausar').click();
        await expect(page.locator('#mc-status-badge')).toContainText('Pausado');
        expect(await lerTempoTela(page)).toBeGreaterThanOrEqual(saldoApos30 - 1);
        expect(await lerTempoTela(page)).toBeLessThanOrEqual(saldoApos30 + 1);
        await expect.poll(async () => Number((await lerJogoLocal(page, fixture.idJogo))?.tempo_restante_jogo)).toBeGreaterThanOrEqual(saldoApos30 - 1);
        await expect.poll(async () => Number((await lerJogoLocal(page, fixture.idJogo))?.tempo_restante_jogo)).toBeLessThanOrEqual(saldoApos30 + 1);

        // Sai da tela e volta pela rota real; o valor exibido deve vir do
        // snapshot persistido, não da duração original.
        await page.goto('views/src/pages/edicao_agenda.php', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#lista-eventos')).toBeVisible();
        await page.goto(`views/src/pages/jogos.php?id_jogo=${fixture.idJogo}`, {
            waitUntil: 'domcontentloaded',
        });
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await expect(page.locator('#mc-status-badge')).toContainText('Pausado');
        expect(await lerTempoTela(page)).toBeGreaterThanOrEqual(saldoApos30 - 1);
        expect(await lerTempoTela(page)).toBeLessThanOrEqual(saldoApos30 + 1);

        await page.locator('#btn-pausar').click();
        await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');
        await page.clock.fastForward(10_000);
        const saldoAposRetomada = await lerTempoTela(page);
        expect(saldoAposRetomada).toBeGreaterThanOrEqual(saldoApos30 - 12);
        expect(saldoAposRetomada).toBeLessThanOrEqual(saldoApos30 - 8);

        // A pausa offline captura o saldo corrente e enfileira exatamente esse
        // snapshot. O tempo virtual avançar enquanto pausado não pode consumi-lo.
        await context.setOffline(true);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(false);
        await page.locator('#btn-pausar').click();
        await expect(page.locator('#mc-status-badge')).toContainText('Pausado');
        const saldoOffline = await lerTempoTela(page);
        expect(saldoOffline).toBeGreaterThanOrEqual(saldoAposRetomada - 1);
        expect(saldoOffline).toBeLessThanOrEqual(saldoAposRetomada + 1);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBeGreaterThan(0);
        await page.clock.fastForward(45_000);
        expect(await lerTempoTela(page)).toBeGreaterThanOrEqual(saldoOffline - 1);
        expect(await lerTempoTela(page)).toBeLessThanOrEqual(saldoOffline + 1);

        const localOffline = await lerJogoLocal(page, fixture.idJogo);
        expect(localOffline.status_jogo).toBe('Pausado');
        expect(Number(localOffline.tempo_restante_jogo)).toBeGreaterThanOrEqual(saldoOffline - 1);
        expect(Number(localOffline.tempo_restante_jogo)).toBeLessThanOrEqual(saldoOffline + 1);
        expect(localOffline._pendente).toBe(true);
        const mutacao = await lerMutacaoCronometro(page, fixture.idJogo);
        const corpoMutacao = JSON.parse(mutacao.body);
        expect(corpoMutacao.cronometro.versao).toBe(2);
        expect(Number(corpoMutacao.cronometro.saldo_segundos)).toBeGreaterThanOrEqual(saldoOffline - 1);
        expect(Number(corpoMutacao.cronometro.saldo_segundos)).toBeLessThanOrEqual(saldoOffline + 1);

        await context.setOffline(false);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(true);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 45_000 }).toBe(0);

        const servidor = await lerJogoServidor(page, fixture.idJogo);
        expect(servidor.status_jogo).toBe('Pausado');
        expect(Number(servidor.tempo_restante_calculado)).toBeGreaterThanOrEqual(saldoOffline - 1);
        expect(Number(servidor.tempo_restante_calculado)).toBeLessThanOrEqual(saldoOffline + 1);
        expect((await lerJogoLocal(page, fixture.idJogo))._pendente).not.toBe(true);
        expect(timerResponses.every((item) => item.status === 200)).toBeTruthy();
        expect(dialogs).toEqual([]);
        expect(pageErrors).toEqual([]);
    });
});
