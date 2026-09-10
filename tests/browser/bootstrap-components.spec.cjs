const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

for (const width of [390, 1440]) {
    test('native button and switch states survive context CSS at ' + width, async ({ page }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.route('https://fonts.googleapis.com/**', route => route.fulfill({ contentType: 'text/css', body: '' }));
        const template = read('resources/views/pages/competicoes/chaveamento.php');
        const button = template.match(/<button[^>]*id="btnGerarChaveamento"[^>]*>/)[0];
        await page.setContent(button + 'Gerar</button><input type="checkbox" class="form-check-input status-switch" aria-label="Edição ativa">');
        await page.addStyleTag({ content: read('public/assets/css/bootstrap-theme.css') });
        const action = page.locator('#btnGerarChaveamento');
        const toggle = page.getByRole('checkbox');
        const style = locator => locator.evaluate(el => {
            const s = getComputedStyle(el);
            return { color: s.color, background: s.backgroundColor, border: s.borderColor, shadow: s.boxShadow, opacity: s.opacity };
        });
        const reference = {};
        reference.normal = await style(action);
        await action.hover(); reference.hover = await style(action);
        await action.evaluate(el => el.disabled = true); reference.disabled = await style(action);
        await toggle.check(); await toggle.focus(); reference.checked = await style(toggle);
        for (const file of ['shared', 'admin']) await page.addStyleTag({ content: read('public/assets/css/' + file + '.css') });
        await expect.poll(() => style(toggle)).toEqual(reference.checked);
        await expect.poll(() => style(action)).toEqual(reference.disabled);
        await action.evaluate(el => el.disabled = false);
        await action.hover();
        await expect.poll(() => style(action)).toEqual(reference.hover);
        await page.mouse.move(width - 1, 899);
        await expect.poll(() => style(action)).toEqual(reference.normal);
        await toggle.uncheck(); await expect(toggle).not.toBeChecked();
        await expect(action).toBeEnabled();
    });
}
