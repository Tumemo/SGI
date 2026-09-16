const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:[/?#]|$)/, { timeout: 15_000 });
}

test.describe('E09 — recuperação da lista administrativa de jogos', () => {
    test('falha de carregamento não vira lista vazia e retry recupera preservando foco', async ({ page }) => {
        await entrarComoAdmin(page);
        const idEdicao = await page.evaluate(async () => {
            const response = await fetch(`${window.SGI_API_BASE}edicoes?regulamento=true`);
            const edicoes = await response.json();
            const edicao = edicoes.find((item) => String(item.status_interclasse) === '1');
            return Number(edicao.id_interclasse);
        });

        let tentativas = 0;
        await page.route(/\/api\/v1\/jogos\?x=1&id_interclasse=\d+$/, async (route) => {
            tentativas += 1;
            if (tentativas === 1) {
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
                    id_jogo: 99001,
                    nome_modalidade: 'Basquete E09',
                    equipes_nomes: 'Lobos x Tigres',
                    status_jogo: 'Agendado',
                    data_jogo: '2026-09-15',
                    inicio_jogo: '10:00:00',
                    nome_local: 'Quadra E09',
                }]),
            });
        });

        await page.goto(`jogos?id=${idEdicao}`, { waitUntil: 'domcontentloaded' });
        const lista = page.locator('#listaJogos');
        await expect(lista).toContainText(/não foi possível carregar os jogos/i);
        await expect(lista).not.toContainText(/nenhum jogo encontrado/i);

        const retry = page.getByRole('button', { name: /tentar novamente/i });
        await retry.focus();
        await retry.click();
        await expect(lista).toContainText('Basquete E09');
        await expect(lista).toContainText('Lobos x Tigres');
        await expect.poll(() => tentativas).toBe(2);
        await expect(page.locator('#listaJogos a[href*="id_jogo=99001"]')).toBeFocused();
    });
});
