const { test, expect } = require('./fixtures.cjs');

async function loginMesario(page) {
    await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('mesario');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/dashboard\.php\?id=\d+/, { waitUntil: 'domcontentloaded' });
    await expect.poll(() => page.evaluate(() => Boolean(window.SGIOffline))).toBe(true);
}

async function putLegacyQueueRows(page, rows) {
    await page.evaluate(async (items) => {
        await new Promise((resolve, reject) => {
            const request = indexedDB.open('sgi_offline', 1);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                const db = request.result;
                const tx = db.transaction('mutation_queue', 'readwrite');
                items.forEach((item) => tx.objectStore('mutation_queue').put(item));
                tx.oncomplete = resolve;
                tx.onerror = () => reject(tx.error);
            };
        });
    }, rows);
}

async function readAllQueueRows(page) {
    return page.evaluate(async () => new Promise((resolve, reject) => {
        const request = indexedDB.open('sgi_offline', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const tx = db.transaction('mutation_queue', 'readonly');
            const get = tx.objectStore('mutation_queue').getAll();
            get.onsuccess = () => resolve(get.result || []);
            get.onerror = () => reject(get.error);
        };
    }));
}

test.describe('Compatibilidade de fila e casca offline legadas', () => {
    test('reenvia fila antiga pelo alias, preserva identidade/corpo e isola outro usuário', async ({ page, context }) => {
        await loginMesario(page);

        const session = await page.evaluate(() => String(window.SGI_CACHE_KEY));
        const csrfAtual = await page.evaluate(() => String(window.SGI_CSRF_TOKEN || ''));
        expect(csrfAtual).not.toBe('');
        const mutationId = `${session}-legacy-${Date.now()}`;
        const body = JSON.stringify({ id_jogo: -7001, resultados: [] });
        const url = await page.evaluate(() => new URL('../../../api/lancar_resultado.php', window.location.href).href);
        const legacyRow = {
            id: 771001,
            method: 'POST',
            url,
            body,
            headers: {
                'Content-Type': 'application/json',
                'X-SGI-CSRF': 'csrf-antigo',
                'X-SGI-Mutation-Id': mutationId
            },
            createdAt: Date.now() - 1000,
            tries: 0,
            needsReview: false
            // formato antigo: sem `session`, `projectionPending` e fingerprint
        };
        const foreignRow = {
            id: 771002,
            method: 'POST',
            url,
            body: JSON.stringify({ id_jogo: -7002, resultados: [] }),
            headers: {
                'Content-Type': 'application/json',
                'X-SGI-CSRF': 'csrf-de-outro-usuario',
                'X-SGI-Mutation-Id': 'outro-usuario-mutacao'
            },
            session: 'outro-usuario',
            createdAt: Date.now() - 900,
            tries: 0,
            needsReview: false
        };
        await putLegacyQueueRows(page, [legacyRow, foreignRow]);

        const enviados = [];
        await page.route('**/api/lancar_resultado.php', async (route) => {
            enviados.push({
                headers: route.request().headers(),
                body: route.request().postData()
            });
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                // resposta de cliente antigo: sucesso idempotente sem fingerprint
                body: JSON.stringify({ success: true, idempotent: true })
            });
        });

        await context.setOffline(false);
        await page.evaluate(() => window.SGIOffline.syncNow());
        await expect.poll(() => enviados.length).toBe(1);

        expect(enviados[0].body).toBe(body);
        expect(enviados[0].headers['x-sgi-mutation-id']).toBe(mutationId);
        expect(enviados[0].headers['x-sgi-csrf']).toBe(csrfAtual);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBe(0);

        const rowsAfter = await readAllQueueRows(page);
        expect(rowsAfter.some((row) => Number(row.id) === foreignRow.id)).toBe(true);
        expect(rowsAfter.some((row) => Number(row.id) === legacyRow.id)).toBe(false);
    });

    test('monta uma casca antiga sem pageSources e explicita que não há suporte a cold-open sem casca', async ({ page, context }) => {
        await loginMesario(page);
        const session = await page.evaluate(() => String(window.SGI_CACHE_KEY));
        const idInterclasse = await page.evaluate(() => new URL(window.location.href).searchParams.get('id'));

        await page.evaluate(async ({ sessionKey, id }) => {
            await new Promise((resolve, reject) => {
                const request = indexedDB.open('sgi_pages', 1);
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    const db = request.result;
                    const tx = db.transaction('paginas', 'readwrite');
                    tx.objectStore('paginas').put({
                        key: `${sessionKey}|agenda:${id}`,
                        url: `edicao_agenda.php?id=${id}`,
                        tela: 'agenda',
                        titulo: 'Agenda',
                        html: '<section id="legacy-shell-screen"><strong>Agenda legada v2</strong></section>',
                        css: '#legacy-shell-screen { display: block; }',
                        // formato antigo: script único, sem schemaVersion/pageSources
                        script: "document.getElementById('legacy-shell-screen').setAttribute('data-legacy-ready', '1');",
                        savedAt: Date.now()
                    });
                    tx.oncomplete = resolve;
                    tx.onerror = () => reject(tx.error);
                };
            });
        }, { sessionKey: session, id: idInterclasse });

        await context.setOffline(true);
        await page.evaluate((id) => window.__SGI_SPA__.navegarPara('agenda', { id }), idInterclasse);
        await expect(page.locator('#legacy-shell-screen')).toHaveAttribute('data-legacy-ready', '1');
        await expect(page.locator('#legacy-shell-screen')).toContainText('Agenda legada v2');
        const serviceWorker = await page.evaluate(() => Boolean(navigator.serviceWorker && navigator.serviceWorker.controller));
        expect(serviceWorker).toBe(false);
    });

    test('mantém a fila quando a rede falha no primeiro envio e tenta novamente', async ({ page }) => {
        await loginMesario(page);
        const session = await page.evaluate(() => String(window.SGI_CACHE_KEY));
        const mutationId = `${session}-retry-${Date.now()}`;
        const url = await page.evaluate(() => new URL('../../../api/lancar_resultado.php', window.location.href).href);
        await putLegacyQueueRows(page, [{
            id: 771003,
            session,
            method: 'POST',
            url,
            body: JSON.stringify({ id_jogo: -7003, resultados: [] }),
            headers: {
                'Content-Type': 'application/json',
                'X-SGI-Mutation-Id': mutationId
            },
            createdAt: Date.now(),
            tries: 0,
            needsReview: false
        }]);

        let tentativas = 0;
        await page.route('**/api/lancar_resultado.php', async (route) => {
            tentativas += 1;
            if (tentativas === 1) {
                await route.abort('failed');
                return;
            }
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, idempotent: true })
            });
        });

        await page.evaluate(() => window.SGIOffline.syncNow());
        await expect.poll(() => tentativas).toBe(1);
        await expect.poll(async () => (await readAllQueueRows(page)).some((row) => Number(row.id) === 771003)).toBe(true);
        await expect.poll(() => tentativas, { timeout: 10_000 }).toBe(2);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 10_000 }).toBe(0);
    });
});
