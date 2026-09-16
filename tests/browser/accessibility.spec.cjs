const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    const formulario = page.locator('#form_desktop');
    await expect(formulario).toBeVisible();
    await formulario.locator('.ipt-matricula').fill('admin');
    await formulario.locator('.ipt-senha').fill('123');
    await formulario.locator('button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:\?|$)/, { timeout: 15_000 });
}

async function obterEdicao(page) {
    const edicoes = await page.evaluate(async () => {
        const resposta = await fetch(`${window.SGI_API_BASE}edicoes?regulamento=true`);
        if (!resposta.ok) throw new Error(`Falha ao consultar edições: HTTP ${resposta.status}`);
        return resposta.json();
    });
    const lista = Array.isArray(edicoes) ? edicoes : [];
    const edicao = lista.find((item) => String(item.status_interclasse) === '1') || lista[0];
    if (!edicao) throw new Error('O fixture não contém uma edição para a regressão de acessibilidade.');
    return Number(edicao.id_interclasse);
}

test.describe('E11 — regressões transversais de nome, teclado e impressão', () => {
    test('login mantém nomes dos campos, ajuda acionável e envio por teclado', async ({ page }) => {
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        const formulario = page.locator('#form_desktop');
        const matricula = formulario.getByLabel('Matrícula (RA/NIF)');
        const senha = formulario.getByLabel('Senha');
        await expect(matricula).toHaveAccessibleName('Matrícula (RA/NIF)');
        await expect(senha).toHaveAccessibleName('Senha');

        const ajuda = formulario.getByRole('button', { name: 'Como recuperar o acesso?' });
        await ajuda.focus();
        await expect(ajuda).toBeFocused();
        await page.keyboard.press('Enter');
        await expect(formulario.locator('#login_recovery_desktop')).toBeVisible();
        await expect(ajuda).toHaveAttribute('aria-expanded', 'true');

        await matricula.fill('usuario-inexistente-e11');
        await senha.fill('senha-invalida');
        await senha.press('Enter');
        await expect(page.locator('#msg_erro_desktop')).toContainText(/Matrícula ou Senha incorretos|incorretos/i);
        await expect(matricula).toHaveValue('usuario-inexistente-e11');
    });

    test('menu compacto abre por teclado e devolve o foco ao gatilho', async ({ page }) => {
        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 390, height: 844 });
        const gatilho = page.getByRole('button', { name: 'Abrir menu' });
        await expect(gatilho).toBeVisible();
        await gatilho.focus();
        await page.keyboard.press('Enter');
        const menu = page.locator('#sgiMobileMenu');
        await expect(menu).toHaveClass(/show/);
        await expect(menu.getByRole('link').first()).toBeVisible();
        await menu.focus();
        await page.keyboard.press('Escape');
        await expect(menu).toBeHidden();
        await expect(gatilho).toBeFocused();
    });

    test('impressão do ranking mantém uma variante, contexto e lista filtrada', async ({ page }) => {
        await entrarComoAdmin(page);
        const idEdicao = await obterEdicao(page);
        await page.goto(`ranking?id=${idEdicao}`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { level: 1, name: 'Ranking de Turmas' })).toBeVisible();
        await page.emulateMedia({ media: 'print' });

        await expect(page.locator('.sgi-ranking-page.d-md-none')).toBeHidden();
        await expect(page.locator('.sgi-ranking-page.d-none.d-md-block')).toBeVisible();
        await expect(page.locator('.sgi-ranking-page.d-none.d-md-block').getByRole('heading', { level: 1, name: 'Ranking de Turmas' })).toBeVisible();
        await expect(page.locator('.sidebar-nav')).toBeHidden();
        const botoesImprimir = page.locator('.btn-imprimir');
        await expect(botoesImprimir).toHaveCount(2);
        await expect(botoesImprimir.nth(0)).toBeHidden();
        await expect(botoesImprimir.nth(1)).toBeHidden();
    });
});
