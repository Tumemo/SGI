const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:[/?#]|$)/, { timeout: 15_000 });
}

test.describe('E09 — listas auxiliares de administração', () => {
    test('edições diferencia erro HTTP de lista vazia e permite tentar novamente', async ({ page }) => {
        await entrarComoAdmin(page);
        let consultas = 0;
        let recuperacaoPermitida = false;
        await page.route((url) => {
            const parsed = new URL(url);
            return parsed.pathname.endsWith('/api/v1/edicoes')
                && parsed.searchParams.get('regulamento') === 'true';
        }, async (route) => {
            consultas += 1;
            if (!recuperacaoPermitida) {
                await route.fulfill({
                    status: 503,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária.' }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([{
                    id_interclasse: 98120,
                    nome_interclasse: 'Edição E09 recuperada',
                    ano_interclasse: '2026-01-01',
                    status_interclasse: '0',
                }]),
            });
        });

        await page.reload({ waitUntil: 'domcontentloaded' });
        const lista = page.locator('#listaDesktop');
        await expect(lista).toContainText(/não foi possível carregar/i);
        await expect(lista).not.toContainText(/nenhum interclasse encontrado/i);
        const consultasAntesRetry = consultas;
        recuperacaoPermitida = true;
        await page.getByRole('button', { name: /tentar novamente/i }).click();
        await expect(lista).toContainText('Edição E09 recuperada');
        await expect.poll(() => consultas).toBeGreaterThan(consultasAntesRetry);
    });

    test('colaboradores preserva busca, oferece retry e separa vazio de busca sem resultados', async ({ page }) => {
        await entrarComoAdmin(page);
        let consultas = 0;
        await page.route(/\/api\/v1\/usuarios\?acao=listar_colaboradores$/, async (route) => {
            consultas += 1;
            if (consultas === 1) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ status: 'erro', mensagem: 'Falha temporária.' }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    status: 'sucesso',
                    colaboradores: [{
                        id_usuario: 98121,
                        nome_usuario: 'Colaboradora E09',
                        matricula_usuario: 'E09-98121',
                        nivel_usuario: 1,
                    }],
                }),
            });
        });

        await page.goto('colaboradores', { waitUntil: 'domcontentloaded' });
        const lista = page.locator('#listaColaboradoresDesktop');
        await expect(lista).toContainText(/não foi possível carregar os colaboradores/i);
        await expect(lista).not.toContainText(/nenhum colaborador encontrado/i);
        const busca = page.locator('#buscaColabDesk');
        await busca.fill('E09');
        await page.getByRole('button', { name: /tentar novamente/i }).click();
        await expect(lista).toContainText('Colaboradora E09');
        await expect(busca).toHaveValue('E09');
        await expect.poll(() => consultas).toBe(2);

        await busca.fill('não existe');
        await expect(lista).toContainText(/nenhum colaborador corresponde/i);
        await expect(lista).toContainText(/limpar busca e filtros/i);
        await expect(lista).not.toContainText(/adicionar colaborador/i);
        await page.getByRole('button', { name: /limpar busca e filtros/i }).click();
        await expect(lista).toContainText('Colaboradora E09');
        await expect(busca).toHaveValue('');
    });
});
