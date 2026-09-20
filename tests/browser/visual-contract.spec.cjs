const { test, expect } = require('./fixtures.cjs');

for (const [name, viewport] of [
    ['desktop', { width: 1440, height: 900 }],
    ['mobile', { width: 390, height: 844 }],
]) {
    test(`contrato visual do acesso em ${name}`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await page.goto('login');
        const form = page.locator(name === 'desktop' ? '#form_desktop' : '#form_mobile');
        await expect(form).toBeVisible();
        await page.evaluate(() => document.fonts.ready);
        await expect(page).toHaveScreenshot(`acesso-${name}.png`, { fullPage: true, animations: 'disabled', maxDiffPixelRatio: 0.01 });
        // Stable visual fixture. Real authentication is exercised by auth-rbac.
        await page.route('**/api/v1/login', route => route.fulfill({
            status: 401, contentType: 'application/json',
            body: JSON.stringify({ status: 'erro', mensagem: 'Matrícula ou Senha incorretos.' }),
        }));
        await form.locator('.ipt-matricula').fill('nao-existe');
        await form.locator('.ipt-senha').fill('senha-invalida');
        await form.locator('button[type="submit"]').click();
        await expect(page.locator(`#msg_erro_${name === 'desktop' ? 'desktop' : 'mobile'}`)).toBeVisible();
        await expect(page).toHaveScreenshot(`acesso-erro-${name}.png`, { fullPage: true, animations: 'disabled', maxDiffPixelRatio: 0.01 });
    });
}
