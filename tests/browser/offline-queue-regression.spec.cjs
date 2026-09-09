const { test, expect } = require('@playwright/test');
const path = require('node:path');

// Exercita o IndexedDB real sem depender de fixtures ou alterar o banco SQL.
test.beforeEach(async ({ page }) => {
    await page.route('https://offline.sgi.test/**', route => route.fulfill({
        contentType: 'text/html', body: '<html><body></body></html>',
    }));
    await page.goto('https://offline.sgi.test/');
    await page.evaluate(() => { window.SGI_CACHE_KEY = 'queue-audit'; });
    await page.addScriptTag({ path: path.resolve(__dirname, '../../resources/js/offline/offline-core.js') });
    await page.evaluate(() => window.SGIOffline.getPendingList());
});

test('alteração nova respeita a fila ainda pendente após reconexão', async ({ page }) => {
    const enviados = [];
    await page.route('**/api/v1/partidas', route => {
        enviados.push(route.request().postDataJSON().resultado_partida);
        return route.fulfill({ json: { success: true } });
    });
    await page.evaluate(async () => {
        const url = '/api/v1/partidas';
        const headers = { 'Content-Type': 'application/json' };
        await SGIOffline.queueMutation('PUT', url, JSON.stringify({ id_partida: 1, resultado_partida: 1 }), headers);
        await fetch(url, { method: 'PUT', headers, body: JSON.stringify({ id_partida: 1, resultado_partida: 2 }) });
        await SGIOffline.syncNow();
    });
    expect(enviados).toEqual([1, 2]);
});

for (const body of ['<html>Servidor em manutenção</html>', '', '{"success":', '{}', '{"status":"erro","mensagem":"Dados inválidos"}']) {
    test(`resposta sem confirmação JSON conserva a mutação: ${JSON.stringify(body)}`, async ({ page }) => {
        await page.route('**/api/v1/resultados', route => route.fulfill({ status: 200, body }));
        const resultado = await page.evaluate(async () => {
            await SGIOffline.queueMutation('POST', '/api/v1/resultados', '{"id_jogo":1}', {});
            await SGIOffline.syncNow();
            return SGIOffline.getPendingList();
        });
        expect(resultado).toHaveLength(1);
        expect(resultado[0].needsReview).toBe(true);
    });
}

test('confirmação no formato status sucesso continua compatível', async ({ page }) => {
    await page.route('**/api/v1/usuarios', route => route.fulfill({ json: { status: 'sucesso' } }));
    const fila = await page.evaluate(async () => {
        await SGIOffline.queueMutation('PUT', '/api/v1/usuarios', '{"id_usuario":1}', {});
        await SGIOffline.syncNow();
        return SGIOffline.getPendingList();
    });
    expect(fila).toEqual([]);
});

test('edição de ocorrência temporária resolve o ID nas rotas v1', async ({ page }) => {
    const enviados = [];
    await page.route('**/api/v1/ocorrencias', route => {
        enviados.push(route.request().postDataJSON());
        return route.fulfill({ json: { success: true, id: 321 } });
    });
    await page.evaluate(async () => {
        const url = '/api/v1/ocorrencias';
        const item = await SGIOffline.queueMutation('POST', url, '{"descricao_ocorrencia":"original"}', {});
        await SGIOffline.queueMutation('PUT', url, JSON.stringify({ id_ocorrencia: 'temp_' + item.id, descricao_ocorrencia: 'editada' }), {});
        await SGIOffline.syncNow();
    });
    expect(enviados).toHaveLength(2);
    expect(enviados[1].id_ocorrencia).toBe(321);
});

test('jogos temporários intercalados enviam resultado antes de seus gols', async ({ page }) => {
    const enviados = [];
    await page.route('**/api/v1/*', route => {
        const dados = route.request().postDataJSON();
        enviados.push(`${route.request().url().split('/').pop()}:${dados.id_jogo || dados.jogos_id_jogo}`);
        return route.fulfill({ json: { success: true } });
    });
    await page.evaluate(async () => {
        for (const id of [-1, -2]) {
            await SGIOffline.queueMutation('POST', '/api/v1/artilheiros', JSON.stringify({ jogos_id_jogo: id, id_modalidade: 1 }), {});
        }
        for (const id of [-1, -2]) {
            await SGIOffline.queueMutation('POST', '/api/v1/resultados', JSON.stringify({ id_jogo: id, id_modalidade: 1 }), {});
        }
        await SGIOffline.syncNow();
    });
    for (const id of [-1, -2]) {
        expect(enviados.indexOf(`resultados:${id}`)).toBeLessThan(enviados.indexOf(`artilheiros:${id}`));
    }
});

test('aborto da transação local não anuncia salvamento nem projeta dados', async ({ page }) => {
    const resultado = await page.evaluate(async () => {
        let projecoes = 0;
        window.SGIDataLayer = { onQueued: () => { projecoes += 1; } };
        const add = IDBObjectStore.prototype.add;
        IDBObjectStore.prototype.add = function (...args) {
            const req = add.apply(this, args);
            if (this.name === 'mutation_queue') req.addEventListener('success', () => this.transaction.abort());
            return req;
        };
        let rejeitada = false;
        try { await SGIOffline.queueMutation('PUT', '/api/v1/partidas', '{"id_partida":1}', {}); }
        catch (_) { rejeitada = true; }
        return { rejeitada, projecoes, fila: await SGIOffline.getPendingList() };
    });
    expect(resultado).toEqual({ rejeitada: true, projecoes: 0, fila: [] });
});

test('chaveamento aplica resultado pendente enviado pela rota v1', async ({ page }) => {
    await page.route('**/api/v1/chaveamentos?*', route => route.fulfill({ json: { success: true, jogos: [{
        id_jogo: 7, nome_jogo: 'MM:2:0:N', status_jogo: 'Iniciado',
        equipes: [{ id_equipe: 1, gols: 0 }, { id_equipe: 2, gols: 0 }],
    }] } }));
    await page.addScriptTag({ path: path.resolve(__dirname, '../../resources/js/offline/chaveamento-engine.js') });
    const jogo = await page.evaluate(async () => {
        await SGIOffline.queueMutation('POST', '/api/v1/resultados', JSON.stringify({
            id_jogo: 7, resultados: [{ id_equipe: 1, gols: 2 }, { id_equipe: 2, gols: 1 }],
        }), {});
        const arvore = await SGIChaveamento.carregarArvore(1);
        return arvore.jogos.find(j => j.id_jogo === 7);
    });
    expect(jogo.status_jogo).toBe('Concluido');
    expect(jogo.equipe_vencedora_id).toBe(1);
});

test('distingue servidor SGI acessível de navegador conectado', async ({ page }) => {
    let disponivel = true;
    await page.route('**/api/v1/health', route => {
        if (!disponivel) return route.fulfill({ status: 200, contentType: 'text/html', body: '<html>login</html>' });
        return route.fulfill({ json: { success: true, status: 'ok', service: 'sgi' } });
    });
    expect(await page.evaluate(() => SGIOffline.checkServer(true))).toBe(true);
    expect(await page.evaluate(() => SGIOffline.getState().server)).toBe('acessivel');
    disponivel = false;
    expect(await page.evaluate(() => SGIOffline.checkServer(true))).toBe(false);
    expect(await page.evaluate(() => SGIOffline.getState().server)).toBe('indisponivel');
});

test('separa servidor acessível de sessão expirada', async ({ page }) => {
    let sessaoValida = true;
    await page.route('**/api/v1/health', route => route.fulfill({
        json: { success: true, status: 'ok', service: 'sgi' },
    }));
    await page.route('**/api/v1/session', route => sessaoValida
        ? route.fulfill({ json: { success: true, usuario: { id: 7, nivel: 2 } } })
        : route.fulfill({ status: 401, json: { success: false, message: 'Sessão expirada' } }));
    expect(await page.evaluate(() => SGIOffline.checkAccess(true))).toBe(true);
    expect(await page.evaluate(() => SGIOffline.getState().session)).toBe('valida');
    sessaoValida = false;
    expect(await page.evaluate(() => SGIOffline.checkAccess(true))).toBe(false);
    expect(await page.evaluate(() => SGIOffline.getState().server)).toBe('sessao');
});

test('falha de GET entra em soft-offline e exibe o estado no banner', async ({ page }) => {
    await page.route('**/api/consulta-offline', route => route.abort());
    const estado = await page.evaluate(async () => {
        try { await fetch('/api/consulta-offline'); } catch (_) {}
        return {
            softOffline: SGIOffline.getState().softOffline,
            bannerOculto: document.getElementById('sgi-offline-banner').classList.contains('sgi-hidden'),
        };
    });
    expect(estado.softOffline).toBe(true);
    expect(estado.bannerOculto).toBe(false);
});

test('duas abas do mesmo operador não sincronizam a fila simultaneamente', async ({ page, context }) => {
    const enviados = [];
    const responder = async route => {
        enviados.push(route.request().postDataJSON().resultado_partida);
        await new Promise(resolve => setTimeout(resolve, 120));
        await route.fulfill({ json: { success: true } });
    };
    await page.route('**/api/v1/partidas', responder);
    const segundaAba = await context.newPage();
    await segundaAba.route('https://offline.sgi.test/**', route => route.fulfill({
        contentType: 'text/html', body: '<html><body></body></html>',
    }));
    await segundaAba.goto('https://offline.sgi.test/');
    await segundaAba.evaluate(() => { window.SGI_CACHE_KEY = 'queue-audit'; });
    await segundaAba.addScriptTag({ path: path.resolve(__dirname, '../../resources/js/offline/offline-core.js') });
    await segundaAba.route('**/api/v1/partidas', responder);
    await page.evaluate(() => SGIOffline.queueMutation('PUT', '/api/v1/partidas', JSON.stringify({ id_partida: 9, resultado_partida: 3 }), {}));
    const resultados = await Promise.all([
        page.evaluate(() => SGIOffline.syncNow()),
        segundaAba.evaluate(() => SGIOffline.syncNow()),
    ]);
    expect(enviados).toEqual([3]);
    expect(resultados.some((item) => item.busy === true)).toBe(true);
});

test('exportação de pendências remove identificador local e credencial CSRF', async ({ page }) => {
    const exportado = await page.evaluate(async () => {
        await SGIOffline.queueMutation('POST', '/api/v1/resultados', '{"id_jogo":11}', {
            'Content-Type': 'application/json',
            'X-SGI-CSRF': 'segredo-de-teste',
        });
        return SGIOffline.exportPending();
    });
    expect(exportado.schemaVersion).toBe(1);
    expect(exportado.items).toHaveLength(1);
    expect(exportado.items[0].id).toBeUndefined();
    expect(exportado.items[0].session).toBeUndefined();
    expect(exportado.items[0].headers['X-SGI-CSRF']).toBeUndefined();
    expect(exportado.items[0].headers['X-SGI-Mutation-Id']).toMatch(/^queue-audit-/);
});

test('importação de pendências é idempotente e rejeita outra sessão', async ({ page }) => {
    const resultado = await page.evaluate(async () => {
        const item = await SGIOffline.queueMutation('POST', '/api/v1/resultados', '{"id_jogo":12}', {});
        const payload = await SGIOffline.exportPending();
        const importado = await SGIOffline.importPending(JSON.stringify(payload));
        let rejeitado = false;
        try {
            await SGIOffline.importPending(JSON.stringify({
                schemaVersion: 1,
                items: [{ ...payload.items[0], headers: { 'X-SGI-Mutation-Id': 'outro-operador-abc' } }],
            }));
        } catch (_) { rejeitado = true; }
        return { importado, rejeitado, fila: await SGIOffline.getPendingList(), id: item.id };
    });
    expect(resultado.importado.imported).toBe(0);
    expect(resultado.rejeitado).toBe(true);
    expect(resultado.fila).toHaveLength(1);
    expect(resultado.fila[0].id).toBe(resultado.id);
});
