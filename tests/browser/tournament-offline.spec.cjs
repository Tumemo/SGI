const { test, expect, request: playwrightRequest } = require('@playwright/test');

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

async function capturarTela(page, testInfo, nome) {
    const caminho = testInfo.outputPath(`${nome}.png`);
    await page.screenshot({ path: caminho, fullPage: true });
    await testInfo.attach(`${nome}.png`, { path: caminho, contentType: 'image/png' });
}

function nomeEquipeExibida(partida) {
    return [partida && partida.nome_fantasia_turma, partida && partida.nome_fantasia,
        partida && partida.nome_turma, partida && partida.nome_equipe]
        .map((valor) => String(valor || '').trim())
        .find(Boolean) || '';
}

async function validarDadosJogoOffline(page, esperado, opcoes = {}) {
    const jogo = esperado && esperado.jogo ? esperado.jogo : {};
    const partidasEsperadas = (esperado && Array.isArray(esperado.partidas)) ? esperado.partidas : [];
    const titulo = (await page.locator('#placar-titulo-jogo').innerText()).trim();
    const meta = (await page.locator('#placar-meta').innerText()).trim();
    const nomes = (await page.locator('.mc-team-name').allTextContents()).map((nome) => nome.trim()).filter(Boolean);

    expect(titulo).not.toBe('');
    expect(titulo).not.toBe('Placar');
    expect(meta).not.toBe('');
    if (jogo.nome_modalidade) expect(meta).toContain(String(jogo.nome_modalidade));
    if (jogo.nome_local) expect(meta).toContain(String(jogo.nome_local));
    if (jogo.data_jogo) expect(meta).toContain(String(jogo.data_jogo));
    if (jogo.inicio_jogo) expect(meta).toContain(String(jogo.inicio_jogo).slice(0, 5));

    expect(nomes).toHaveLength(2);
    const esperados = partidasEsperadas.map(nomeEquipeExibida).filter(Boolean);
    expect(esperados).toHaveLength(2);
    for (const nome of esperados) expect(nomes).toContain(nome);

    if (opcoes.placarInicial !== false) {
        const placares = (await page.locator('.score-number').allTextContents()).map((valor) => valor.trim());
        expect(placares).toEqual(['00', '00']);
    }
}

async function criarChaveFixture(request) {
    await jsonOrThrow(await request.post('api/login.php', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login administrativo do fixture');

    // Uma edição nova mantém o cenário isolado dos jogos existentes e garante
    // quatro equipes reais na modalidade escolhida.
    const nomeEdicao = `E2E Torneio Offline ${Date.now()}`;
    const edicao = await jsonOrThrow(await request.post('api/interclasse.php', {
        data: { nome_interclasse: nomeEdicao, ano_interclasse: new Date().toISOString().slice(0, 10) }
    }), 'criação da edição fixture');
    const idInterclasse = Number(edicao.id);
    if (!idInterclasse) throw new Error(`Edição fixture sem ID: ${JSON.stringify(edicao)}`);

    const modalidades = await jsonOrThrow(
        await request.get(`api/modalidades.php?id_interclasse=${idInterclasse}`),
        'modalidades do fixture'
    );

    let modalidade = null;
    let equipes = [];
    for (const item of modalidades) {
        if (!String(item.nome_tipo_modalidade || '').toLowerCase().includes('mata')) continue;
        const lista = await jsonOrThrow(
            await request.get(`api/equipes.php?id_modalidade=${Number(item.id_modalidade)}`),
            `equipes da modalidade ${item.id_modalidade}`
        );
        if (lista.length >= 4) {
            modalidade = item;
            equipes = lista.slice(0, 4);
            break;
        }
    }
    if (!modalidade || equipes.length < 4) {
        throw new Error('O fixture precisa de uma modalidade mata-mata com quatro equipes.');
    }

    const jogos = [
        { tag: 'MM:4:0:N', a: equipes[0], b: equipes[1] },
        { tag: 'MM:4:1:N', a: equipes[2], b: equipes[3] }
    ];
    // Esta rota grava os jogos e suas partidas na mesma transação, exatamente
    // como o gerador de chaveamento da aplicação.
    await jsonOrThrow(await request.post('api/sincronizar_chaveamento.php', {
        data: {
            id_modalidade: Number(modalidade.id_modalidade),
            tipo_modalidade: 'mata_mata',
            jogos: jogos.map((jogo) => ({
                nome_jogo: jogo.tag,
                status_jogo: 'Agendado',
                partidas: [
                    { id_equipe: Number(jogo.a.id_equipe), resultado: 0 },
                    { id_equipe: Number(jogo.b.id_equipe), resultado: 0 }
                ]
            }))
        }
    }), 'criação do chaveamento fixture');

    const listaJogos = await jsonOrThrow(
        await request.get(`api/jogos.php?id_modalidade=${Number(modalidade.id_modalidade)}`),
        'consulta dos jogos do fixture'
    );
    const ids = {};
    const detalhes = {};
    for (const jogo of jogos) {
        const encontrado = listaJogos.find((item) => String(item.nome_jogo) === jogo.tag);
        const idJogo = Number(encontrado && encontrado.id_jogo);
        if (!idJogo) throw new Error(`A API não retornou o ID de ${jogo.tag}: ${JSON.stringify(encontrado)}`);
        ids[jogo.tag] = idJogo;
        const partidas = await jsonOrThrow(
            await request.get(`api/partidas.php?id_jogo=${idJogo}`),
            `partidas da fixture ${jogo.tag}`
        );
        detalhes[jogo.tag] = { jogo: encontrado, partidas };
    }

    return {
        idInterclasse,
        idModalidade: Number(modalidade.id_modalidade),
        equipeSf1: Number(equipes[0].id_equipe),
        equipeSf2: Number(equipes[2].id_equipe),
        ids,
        detalhes
    };
}

async function abrirJogo(page, idJogo, esperado, opcoes = {}) {
    await page.evaluate((id) => {
        if (!window.__SGI_SPA__ || typeof window.__SGI_SPA__.navegarPara !== 'function') {
            throw new Error('SPA do mesário não está disponível.');
        }
        window.__SGI_SPA__.navegarPara('jogos', { id_jogo: id, origem: 'agenda_edit' });
    }, idJogo);
    await expect(page.locator('#placar-grid')).toBeVisible({ timeout: 20_000 });
    if (esperado) await validarDadosJogoOffline(page, esperado, opcoes);
}

async function esperarJogoLocal(page, tag, predicate) {
    await expect.poll(async () => page.evaluate(async ({ tag: nome, predicate: regra }) => {
        const jogos = await window.SGIDataLayer.read('jogos');
        const jogo = jogos.find((item) => String(item.nome_jogo) === String(nome));
        if (!jogo) return false;
        if (regra === 'concluido') return /Concluido|Finalizado/.test(String(jogo.status_jogo));
        if (regra === 'final-formada') return Number(jogo.id_jogo) < 0 && jogo.status_jogo === 'Agendado' && Array.isArray(jogo.equipes) && jogo.equipes.length >= 2;
        if (regra === 'campeao') return Number(jogo.id_jogo) < 0 && /Concluido|Finalizado/.test(String(jogo.status_jogo));
        return true;
    }, { tag, predicate })).toBe(true);
}

async function marcarPartida(page, pontos) {
    await expect(page.locator('#mc-status-badge')).toContainText('Agendado');
    await page.locator('button.mc-action-btn--start').click();
    await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');

    for (let i = 0; i < pontos; i += 1) {
        await page.locator('.btn-score-plus').first().click();
        // Futsal abre o registro de artilheiro depois de cada gol. O fluxo
        // deste teste valida o placar de vários jogos; o atleta pode ser
        // lançado em outro teste, então fechamos o modal sem perder o gol.
        const modal = page.locator('#modalArtilheiro');
        // O modal entra com a animação do Bootstrap; aguardar o estado visível
        // evita tentar finalizar a partida enquanto o backdrop ainda captura
        // os cliques.
        await modal.waitFor({ state: 'visible', timeout: 3_000 }).catch(() => {});
        if (await modal.isVisible().catch(() => false)) {
            await modal.locator('[data-bs-dismiss="modal"]').first().click();
            await expect(modal).toBeHidden();
        }
    }

    await page.locator('button.mc-action-btn--finish').click();
    await expect(page.locator('#mc-status-badge')).toContainText('Encerrado');
}

test.describe('Mesário — torneio completo com vários jogos offline', () => {
    test('joga duas semifinais e a final sem rede e sincroniza o campeão', async ({ page, context, request }, testInfo) => {
        test.setTimeout(240_000);
        const fixture = await criarChaveFixture(request);
        const dialogs = [];
        const pageErrors = [];
        page.on('dialog', async (dialog) => {
            dialogs.push(dialog.message());
            await dialog.accept();
        });
        page.on('pageerror', (error) => pageErrors.push(error.message));

        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();
        await page.locator('#form_desktop .ipt-matricula').fill('mesario');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/dashboard\.php\?id=\d+/, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).not.toContainText('Download parcial');
        await expect(page.locator('#sgi-offline-ok')).toContainText('Pronto para uso offline', { timeout: 120_000 });
        // O selo pode permanecer visível de uma sessão anterior enquanto uma
        // nova atualização ainda está em andamento. Só desligamos a rede
        // depois que o estado interno confirmar que o preload terminou.
        await expect.poll(() => page.evaluate(() => window.__SGI_SPA__ && window.__SGI_SPA__.status()), { timeout: 120_000 })
            .toMatchObject({ pronto: true, preloading: false });
        await capturarTela(page, testInfo, '01-torneio-preparado-online');

        await context.setOffline(true);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(false);
        await expect(page.locator('#sgi-offline-banner')).toContainText('OFFLINE');

        // Semifinal 1: o vencedor fica localmente aguardando o outro lado.
        await abrirJogo(page, fixture.ids['MM:4:0:N'], fixture.detalhes['MM:4:0:N']);
        await marcarPartida(page, 1);
        await esperarJogoLocal(page, 'MM:4:0:N', 'concluido');
        await capturarTela(page, testInfo, '02-semifinal-1-offline');

        // Semifinal 2: ao concluir, o motor local forma a final negativa.
        await abrirJogo(page, fixture.ids['MM:4:1:N'], fixture.detalhes['MM:4:1:N']);
        await marcarPartida(page, 2);
        await esperarJogoLocal(page, 'MM:4:1:N', 'concluido');
        await esperarJogoLocal(page, 'MM:2:0:N', 'final-formada');
        await capturarTela(page, testInfo, '03-semifinal-2-offline');
        const finalLocal = await page.evaluate(async () => {
            const jogos = await window.SGIDataLayer.read('jogos');
            const jogo = jogos.find((item) => item.nome_jogo === 'MM:2:0:N');
            return { id: jogo && Number(jogo.id_jogo), equipes: jogo && jogo.equipes ? jogo.equipes.length : 0 };
        });
        expect(finalLocal.id).toBeLessThan(0);
        expect(finalLocal.equipes).toBeGreaterThanOrEqual(2);

        // Final derivada com ID temporário negativo: também é operada offline.
        const finalEsperada = {
            jogo: fixture.detalhes['MM:4:0:N'].jogo,
            partidas: [fixture.detalhes['MM:4:0:N'].partidas[0], fixture.detalhes['MM:4:1:N'].partidas[0]]
        };
        await abrirJogo(page, finalLocal.id, finalEsperada);
        await expect(page.locator('#mc-status-badge')).toContainText('Agendado');
        await capturarTela(page, testInfo, '04-final-formada-offline');
        await marcarPartida(page, 3);
        await esperarJogoLocal(page, 'MM:2:0:N', 'concluido');
        await esperarJogoLocal(page, 'MM:1:0:N', 'campeao');
        const estadoFilaOffline = await page.evaluate(() => window.SGIOffline.getState());
        expect(estadoFilaOffline.online).toBe(false);
        expect(estadoFilaOffline.pending).toBeGreaterThanOrEqual(9);
        await capturarTela(page, testInfo, '05-campeao-offline');

        // A reconexão descarrega todas as mutações em ordem, inclusive os
        // resultados dos jogos derivados negativos.
        await context.setOffline(false);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(true);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 60_000 }).toBe(0);
        await expect(page.locator('#sgi-offline-banner')).toHaveClass(/sgi-hidden/);

        const arvore = await page.evaluate(async (idModalidade) => {
            const response = await fetch(`../../../api/chaveamento.php?id_modalidade=${idModalidade}`);
            return response.json();
        }, fixture.idModalidade);
        expect(arvore.success).toBe(true);
        const porTag = Object.fromEntries((arvore.jogos || []).map((jogo) => [jogo.nome_jogo, jogo]));
        for (const tag of ['MM:4:0:N', 'MM:4:1:N', 'MM:2:0:N', 'MM:1:0:N']) {
            expect(porTag[tag]).toBeTruthy();
            expect(porTag[tag].status_jogo).toMatch(/Concluido|Finalizado/);
        }
        expect(Number(porTag['MM:4:0:N'].equipe_vencedora_id)).toBe(fixture.equipeSf1);
        expect(Number(porTag['MM:4:1:N'].equipe_vencedora_id)).toBe(fixture.equipeSf2);
        expect(Number(porTag['MM:2:0:N'].equipe_vencedora_id)).toBe(fixture.equipeSf1);
        expect(Number(porTag['MM:1:0:N'].equipe_vencedora_id)).toBe(fixture.equipeSf1);
        expect(dialogs.some((message) => /Campeão definido offline/i.test(message))).toBeTruthy();
        expect(dialogs.some((message) => /erro|falha/i.test(message))).toBeFalsy();
        expect(pageErrors).toEqual([]);

        await capturarTela(page, testInfo, '06-campeao-sincronizado');
    });
});
