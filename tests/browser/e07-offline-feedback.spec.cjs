const { test, expect } = require('./fixtures.cjs');
const { agendarBloco, jsonOrThrow } = require('./agenda-helper.cjs');

async function criarJogoNocaute(request) {
    const login = await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' },
    });
    await jsonOrThrow(login, 'login administrativo do fixture E07');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'edições do fixture E07',
    );
    const edicao = edicoes.find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('O fixture E07 precisa de uma edição ativa.');

    const idInterclasse = Number(edicao.id_interclasse);
    const [modalidades, equipes] = await Promise.all([
        request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`).then((res) => jsonOrThrow(res, 'modalidades')),
        request.get(`api/v1/equipes?id_interclasse=${idInterclasse}`).then((res) => jsonOrThrow(res, 'equipes')),
    ]);
    const modalidade = modalidades.find((item) =>
        String(item.nome_tipo_modalidade || '').toLowerCase().includes('mata')
    );
    if (!modalidade) throw new Error('O fixture E07 precisa de uma modalidade mata-mata.');

    const equipesDaModalidade = equipes.filter((item) =>
        String(item.modalidades_id_modalidade) === String(modalidade.id_modalidade)
    );
    if (equipesDaModalidade.length < 2) {
        throw new Error('O fixture E07 precisa de duas equipes na modalidade mata-mata.');
    }
    await garantirAtletas(request, idInterclasse, equipesDaModalidade.slice(0, 2));

    const nomeJogo = `E07 Feedback ${Date.now()} ${Math.random().toString(36).slice(2, 7)}`;
    await jsonOrThrow(await request.post('api/v1/sincronizacao/chaveamento', {
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
    }), 'criação do jogo mata-mata E07');

    const jogos = await jsonOrThrow(
        await request.get(`api/v1/jogos?id_modalidade=${Number(modalidade.id_modalidade)}`),
        'consulta do jogo mata-mata E07',
    );
    const jogo = jogos.find((item) => String(item.nome_jogo) === nomeJogo);
    const idJogo = Number(jogo && jogo.id_jogo);
    if (!idJogo) throw new Error(`A API não retornou o jogo E07: ${JSON.stringify(jogo)}`);

    await agendarBloco(request, {
        idInterclasse,
        idModalidade: Number(modalidade.id_modalidade),
        jogos: [{ id_jogo: idJogo }],
        label: 'E07-offline-feedback',
    });

    return { idJogo, nomeJogo };
}

async function garantirAtletas(request, idInterclasse, equipes) {
    const base = process.env.SGI_BASE_URL || 'http://localhost/SGI/';
    const api = (value) => new URL(value, base).href;
    for (let index = 0; index < equipes.length; index += 1) {
        const equipe = equipes[index];
        const membros = await jsonOrThrow(
            await request.get(api(`api/v1/equipes?id_equipe=${Number(equipe.id_equipe)}`)),
            `membros da equipe ${equipe.id_equipe}`,
        );
        if (membros.some((membro) => Number(membro.id_usuario) > 0)) continue;
        const alunos = await jsonOrThrow(
            await request.get(api(`api/v1/usuarios?acao=listar_competidores&id_turma=${Number(equipe.turmas_id_turma)}&id_interclasse=${idInterclasse}`)),
            `alunos da equipe ${equipe.id_equipe}`,
        );
        let idUsuario = (alunos.competidores || []).find((aluno) => String(aluno.status_usuario || '1') === '1')?.id_usuario;
        if (!Number(idUsuario)) {
            const criado = await jsonOrThrow(await request.post(api('api/v1/usuarios?acao=criar_aluno'), {
                data: {
                    nome_usuario: `Atleta E07 ${index + 1} ${Date.now()}`,
                    matricula_usuario: `E07${Date.now()}${index}`,
                    data_nasc_usuario: '2010-01-01',
                    genero_usuario: 'MASC',
                    turmas_id_turma: Number(equipe.turmas_id_turma),
                },
            }), `criação do atleta ${equipe.id_equipe}`);
            idUsuario = criado.id_usuario || criado.id;
        }
        await jsonOrThrow(await request.post(api('api/v1/equipes'), {
            data: { acao: 'adicionar_usuarios', id_equipe: Number(equipe.id_equipe), usuarios: [Number(idUsuario)] },
        }), `vínculo do atleta ${equipe.id_equipe}`);
    }
}

async function importarPendenciaInerte(page, sufixo, needsReview = false) {
    return page.evaluate(async ({ sufixo: id, needsReview: revisar }) => {
        const sessionKey = String(window.SGI_CACHE_KEY || '');
        const apiBase = String(window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');
        const url = new URL(`${apiBase}e07-feedback-pending`, window.location.href).href;
        const mutationId = `${sessionKey}-e07-${id}`;
        return window.SGIOffline.importPending(JSON.stringify({
            schemaVersion: 1,
            items: [{
                method: 'POST',
                url,
                body: JSON.stringify({ marker: id }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-SGI-Mutation-Id': mutationId,
                },
                createdAt: Date.now(),
                tries: 0,
                needsReview: revisar,
            }],
        }));
    }, { sufixo, needsReview });
}

function calcularContraste(texto, fundo) {
    function rgb(css) {
        const match = String(css).match(/rgba?\(([^)]+)\)/i);
        if (!match) throw new Error(`Cor computada não reconhecida: ${css}`);
        const parts = match[1].split(',').map((part) => Number.parseFloat(part.trim()));
        return {
            channels: parts.slice(0, 3).map((value) => value / 255),
            alpha: parts.length > 3 ? parts[3] : 1,
        };
    }
    function luminance(color) {
        const channels = color.map((value) => value <= 0.04045
            ? value / 12.92
            : ((value + 0.055) / 1.055) ** 2.4);
        return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
    }

    const foreground = rgb(texto).channels;
    const backgroundLayer = rgb(fundo);
    const background = backgroundLayer.alpha < 1
        ? backgroundLayer.channels.map((channel, index) =>
            channel * backgroundLayer.alpha + (1 - backgroundLayer.alpha) * 1
        )
        : backgroundLayer.channels;
    const lighter = Math.max(luminance(foreground), luminance(background));
    const darker = Math.min(luminance(foreground), luminance(background));
    return (lighter + 0.05) / (darker + 0.05);
}

test('distingue estado do placar e da sincronização com ações acessíveis em 320px', async ({ page, context, request }) => {
    test.setTimeout(180_000);
    const fixture = await criarJogoNocaute(request);
    const dialogs = [];
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.message());
        await dialog.accept();
    });

    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('mesario');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/painel\?id=\d+/, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#sgi-offline-ok')).toBeVisible({ timeout: 120_000 });

    // Só começamos a controlar respostas depois que a sessão real do mesário
    // e as telas/dados para esta edição já foram preparados.
    await page.locator('#linkAgenda:visible').first().click();
    await expect(page.locator('#lista-eventos')).toBeVisible();
    const banner = page.locator('#sgi-offline-banner');
    await expect(banner).toBeAttached();
    await expect(page.locator('#sgi-offline-ok')).toBeVisible();
    await page.evaluate(() => {
        window.__e07OfflineBannerBefore = document.querySelector('#sgi-offline-banner .sgi-offline-banner-status');
    });

    let responseMode = 'success';
    let nextGate = null;
    await page.route('**/api/v1/e07-feedback-pending', async (route) => {
        const mode = responseMode;
        const gate = nextGate;
        if (gate) {
            nextGate = null;
            gate.markStarted();
            await gate.released;
        }
        if (mode === 'html') {
            await route.fulfill({
                status: 200,
                contentType: 'text/html; charset=utf-8',
                body: '<!doctype html><html><body>Resposta de teste sem confirmação JSON</body></html>',
            });
            return;
        }
        await route.fulfill({ status: 200, json: { success: true } });
    });

    const retryButton = page.locator('.sgi-offline-banner-btn');
    const exportButton = page.getByRole('button', { name: 'Exportar pendências' });
    const importButton = page.getByRole('button', { name: 'Importar pendências' });

    // Import is a supported queue-recovery path. It creates an online but idle
    // pending state without an unrelated game write; the endpoint is mocked.
    await importarPendenciaInerte(page, `idle-${Date.now()}`);
    const idleSnapshot = await page.evaluate(() => window.SGIOffline.getState());
    expect(idleSnapshot.online).toBe(true);
    expect(idleSnapshot.pending).toBe(1);
    expect(idleSnapshot.syncing).toBe(false);
    await expect(page.locator('#sgi-offline-ok')).toBeHidden();
    expect.soft(await banner.innerText()).toMatch(/1 alteração pendente.*aguardando envio/i);
    const statusRegion = banner.locator('.sgi-offline-banner-status');
    expect.soft(await statusRegion.getAttribute('role')).toBe('status');
    expect.soft(await statusRegion.getAttribute('aria-live')).toBe('polite');
    expect.soft(await page.evaluate(() =>
        document.querySelector('#sgi-offline-banner .sgi-offline-banner-status') === window.__e07OfflineBannerBefore
    )).toBe(true);
    await expect(retryButton).toBeVisible();
    await expect(retryButton).toBeEnabled();
    await expect(exportButton).toBeVisible();
    await expect(importButton).toBeVisible();

    const warningContrast = await page.evaluate(() => {
        const bannerElement = document.getElementById('sgi-offline-banner');
        const button = bannerElement.querySelector('.sgi-offline-banner-btn');
        return {
            background: getComputedStyle(bannerElement).backgroundColor,
            foreground: getComputedStyle(button).color,
        };
    });
    expect.soft(calcularContraste(warningContrast.foreground, warningContrast.background))
        .toBeGreaterThanOrEqual(4.5);

    // A held request is a real in-flight queue sync. The label should change
    // only while this request is executing, not merely because pending > 0.
    let markStarted;
    let releaseRequest;
    const started = new Promise((resolve) => { markStarted = resolve; });
    const released = new Promise((resolve) => { releaseRequest = resolve; });
    nextGate = { markStarted, released };
    await retryButton.click();
    await started;
    const sendingSnapshot = await page.evaluate(() => window.SGIOffline.getState());
    expect(sendingSnapshot.online).toBe(true);
    expect(sendingSnapshot.pending).toBe(1);
    expect(sendingSnapshot.syncing).toBe(true);
    expect.soft(await banner.innerText()).toMatch(/Enviando alterações/i);
    releaseRequest();
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(0);
    await expect(page.locator('#sgi-offline-ok')).toBeVisible();

    // HTTP 200 with HTML must remain a persistent, actionable review state.
    responseMode = 'html';
    await importarPendenciaInerte(page, `review-${Date.now()}`);
    await retryButton.click();
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().needsReview)).toBe(1);
    const reviewSnapshot = await page.evaluate(() => window.SGIOffline.getState());
    expect(reviewSnapshot.pending).toBe(1);
    expect(reviewSnapshot.syncing).toBe(false);
    expect.soft(await banner.innerText()).toMatch(/Revisão necessária/i);
    await expect(retryButton).toBeVisible();
    await expect(exportButton).toBeVisible();
    await expect(importButton).toBeVisible();

    await context.setOffline(true);
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().online)).toBe(false);
    expect.soft(await banner.innerText()).toMatch(/SEM CONEXÃO.*REVISÃO/i);
    expect.soft(await banner.innerText()).toMatch(/revisão.*exporte as pendências/i);
    await context.setOffline(false);
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().online)).toBe(true);
    await expect(banner).toContainText('REVISÃO NECESSÁRIA');

    // The operator can retry after reviewing; the accepted response removes
    // only this test mutation and must not touch the database.
    responseMode = 'success';
    await retryButton.click();
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(0);

    // Session expiry is separate from network loss. The route is installed
    // after the real session has been established and preload has completed.
    await importarPendenciaInerte(page, `session-${Date.now()}`);
    const expiredSessionRoute = (route) => route.fulfill({
        status: 401,
        json: { success: false, message: 'A sessão de teste expirou.' },
    });
    await page.route('**/api/v1/session', expiredSessionRoute);
    await page.evaluate(() => window.SGIOffline.checkAccess(true));
    const sessionSnapshot = await page.evaluate(() => window.SGIOffline.getState());
    expect(sessionSnapshot.online).toBe(true);
    expect(sessionSnapshot.session).toBe('expirada');
    expect(sessionSnapshot.pending).toBe(1);
    expect.soft(await banner.innerText()).toMatch(/Sessão expirada/i);
    await page.unroute('**/api/v1/session', expiredSessionRoute);
    await page.evaluate(() => window.SGIOffline.checkAccess(true));
    await retryButton.click();
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(0);

    const gameCard = page.locator('#lista-eventos .ag-event-card:visible').filter({ hasText: fixture.nomeJogo }).first();
    await expect(gameCard).toBeVisible();
    await gameCard.locator('.iniciar-jogo-btn').click();
    await expect(gameCard.locator('a[href*="jogos/placar"]')).toBeVisible();
    await gameCard.locator('a[href*="jogos/placar"]').click();
    await expect(page.locator('#placar-conteudo')).toBeVisible();
    await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');
    await expect(page.locator('#mc-sync-status')).toBeHidden();

    // Online feedback says the server confirmed the point; this is distinct
    // from the offline-only message tested in mesario-offline.spec.cjs.
    await page.locator('.btn-score-plus').first().click();
    await expect(page.locator('#modalArtilheiro')).toBeVisible();
    await expect.poll(() => page.locator('#selectAlunoArtilheiro option').count()).toBeGreaterThan(1);
    await page.locator('#selectAlunoArtilheiro').selectOption({ index: 1 });
    await page.locator('#btnSalvarArtilheiro').click();
    await expect(page.locator('#msgArtilheiro')).toContainText(/Ponto registrado e confirmado pelo servidor/i);
    await expect(page.locator('#placar-status-announcer')).toContainText(/Ponto registrado e confirmado pelo servidor/i);
    await expect(page.locator('#modalArtilheiro')).toBeHidden({ timeout: 10_000 });

    // Occurrences have the same explicit server-confirmed vs local-only split.
    await page.locator('#btnNovaOcorrencia').click();
    await expect(page.locator('#modalOcorrencia')).toBeVisible();
    const yellowCard = page.getByRole('radio', { name: 'Amarelo' });
    await yellowCard.focus();
    await yellowCard.press('Space');
    await expect(yellowCard).toBeChecked();
    await expect.poll(() => page.locator('#filtroTurmaOcorrencia option').count()).toBeGreaterThan(1);
    await page.locator('#filtroTurmaOcorrencia').selectOption({ index: 1 });
    await expect(page.locator('#selectAlunoOcorrencia')).toBeEnabled();
    await expect.poll(() => page.locator('#selectAlunoOcorrencia option').count()).toBeGreaterThan(1);
    await page.locator('#selectAlunoOcorrencia').selectOption({ index: 1 });
    await page.locator('#descricaoOcorrencia').fill('E07 ocorrência online confirmada');
    await page.locator('#btnSalvarOcorrencia').click();
    await expect(page.locator('#msgOcorrencia')).toContainText(/Ocorrência registrada, confirmada pelo servidor/i);
    await expect(page.locator('#placar-status-announcer')).toContainText(/Ocorrência registrada, confirmada pelo servidor/i);
    await expect(page.locator('#modalOcorrencia')).toBeHidden({ timeout: 10_000 });
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(0);

    // Exercise the offline banner on a real knockout match in the same
    // prepared mesário tab, with pending recovery data.
    await page.setViewportSize({ width: 320, height: 568 });
    await context.setOffline(true);
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().online)).toBe(false);
    await importarPendenciaInerte(page, `offline-layout-${Date.now()}`);
    await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(1);

    const offlineSnapshot = await page.evaluate(() => window.SGIOffline.getState());
    expect(offlineSnapshot.online).toBe(false);
    expect(offlineSnapshot.pending).toBe(1);
    await expect(page.locator('#sgi-offline-ok')).toBeHidden();
    expect.soft(await banner.innerText()).toMatch(/Sem conexão/i);
    await expect(retryButton).toBeVisible();
    const detailsToggle = page.getByRole('button', { name: 'Ver pendências' });
    await expect(detailsToggle).toBeVisible();
    await expect(retryButton).toBeVisible();
    await expect(exportButton).toBeHidden();
    await expect(importButton).toBeHidden();

    await expect(page.locator('#placar-conteudo')).toBeVisible();
    await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');
    await expect(page.locator('#mc-sync-status')).toBeHidden();

    const layout = await page.evaluate(() => {
        const bannerElement = document.getElementById('sgi-offline-banner');
        const content = bannerElement.querySelector('.container-fluid');
        const contentBounds = content.getBoundingClientRect();
        const actions = Array.from(content.querySelectorAll('button'))
            .filter((button) => getComputedStyle(button).display !== 'none')
            .map((button) => {
                const bounds = button.getBoundingClientRect();
                return { left: bounds.left, right: bounds.right };
            });
        return {
            documentClientWidth: document.documentElement.clientWidth,
            documentScrollWidth: document.documentElement.scrollWidth,
            contentClientWidth: content.clientWidth,
            contentScrollWidth: content.scrollWidth,
            contentLeft: contentBounds.left,
            contentRight: contentBounds.right,
            actions,
        };
    });
    expect.soft(layout.contentScrollWidth).toBeLessThanOrEqual(layout.contentClientWidth + 1);
    expect.soft(layout.actions.every((bounds) =>
        bounds.left >= layout.contentLeft - 1 && bounds.right <= layout.contentRight + 1
    )).toBe(true);
    expect(dialogs).toEqual([]);
    if (process.env.E07_CAPTURE === '1') {
        await page.screenshot({
            path: 'test-results/ui-ux-audit-20260913/E07-banner-offline-320.png',
            animations: 'disabled',
        });
    }
    await detailsToggle.click();
    await expect(detailsToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(exportButton).toBeVisible();
    await expect(importButton).toBeVisible();
    const expandedLayout = await page.evaluate(() => {
        const content = document.querySelector('#sgi-offline-banner .container-fluid');
        const bounds = content.getBoundingClientRect();
        const buttons = Array.from(content.querySelectorAll('button'))
            .filter((button) => getComputedStyle(button).display !== 'none');
        return {
            clientWidth: content.clientWidth,
            scrollWidth: content.scrollWidth,
            controlsWithinBanner: buttons.every((button) => {
                const rect = button.getBoundingClientRect();
                return rect.left >= bounds.left - 1 && rect.right <= bounds.right + 1;
            }),
        };
    });
    expect.soft(expandedLayout.scrollWidth).toBeLessThanOrEqual(expandedLayout.clientWidth + 1);
    expect.soft(expandedLayout.controlsWithinBanner).toBe(true);
    await detailsToggle.click();
    await expect(detailsToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(exportButton).toBeHidden();
    await expect(importButton).toBeHidden();
});
