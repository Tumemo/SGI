const fs = require('node:fs');
const path = require('node:path');
const { test, expect } = require('./fixtures.cjs');

const evidenceDirectory = path.resolve(__dirname, '..', '..', 'test-results', 'ui-ux-audit-20260913');

test.describe('E04 — login responsivo e recuperação de acesso', () => {
    test('mantém título, rótulos e alvos de 48px em retrato, paisagem e desktop', async ({ page }) => {
        fs.mkdirSync(evidenceDirectory, { recursive: true });

        for (const viewport of [
            { width: 320, height: 568 },
            { width: 390, height: 844 },
            { width: 640, height: 360 },
            { width: 1024, height: 1366 },
            { width: 1199, height: 844 },
            { width: 1200, height: 900 },
            { width: 1440, height: 900 },
        ]) {
            await page.setViewportSize(viewport);
            await page.goto('login', { waitUntil: 'domcontentloaded' });

            const mobile = viewport.width < 1200;
            const form = page.locator(mobile ? '#form_mobile' : '#form_desktop');
            await expect(form).toBeVisible();
            await expect(form.getByRole('heading', { name: 'Acesso ao sistema' })).toBeVisible();

            const matricula = form.getByLabel('Matrícula (RA/NIF)');
            const senha = form.getByLabel('Senha');
            await expect(matricula).toBeVisible();
            await expect(senha).toBeVisible();
            await expect(matricula).toHaveAttribute('autocomplete', 'username');
            await expect(matricula).toHaveAttribute('type', 'text');
            await expect(senha).toHaveAttribute('autocomplete', 'current-password');

            const geometry = await form.evaluate((element) => {
                const input = element.querySelector('.ipt-matricula');
                const button = element.querySelector('button[type="submit"]');
                const inputStyle = getComputedStyle(input);
                return {
                    inputHeight: input.getBoundingClientRect().height,
                    inputFontSize: Number.parseFloat(inputStyle.fontSize),
                    submitHeight: button.getBoundingClientRect().height,
                    horizontalOverflow: document.documentElement.scrollWidth - window.innerWidth,
                    bannerHeight: document.querySelector('.login-mobile-banner')?.getBoundingClientRect().height ?? null,
                    viewportHeight: window.innerHeight,
                };
            });
            expect(geometry.inputHeight, `matrícula em ${viewport.width}×${viewport.height}`).toBeGreaterThanOrEqual(48);
            expect(geometry.inputFontSize, `fonte da matrícula em ${viewport.width}×${viewport.height}`).toBeGreaterThanOrEqual(16);
            expect(geometry.submitHeight, `botão em ${viewport.width}×${viewport.height}`).toBeGreaterThanOrEqual(48);
            expect(geometry.horizontalOverflow, `overflow horizontal em ${viewport.width}×${viewport.height}`).toBeLessThanOrEqual(0);

            if (mobile && viewport.height > viewport.width) {
                expect(geometry.bannerHeight, `banner em ${viewport.width}×${viewport.height}`)
                    .toBeLessThanOrEqual(geometry.viewportHeight * 0.4 + 1);
            }

            if (viewport.width === 320 || viewport.width === 390) {
                const file = path.join(evidenceDirectory, `E04-login-mobile-${viewport.width}.png`);
                await page.screenshot({ path: file, fullPage: true, animations: 'disabled' });
            }
        }
    });

    test('abre ajuda local no mobile e no desktop sem navegar ou chamar API', async ({ page }) => {
        for (const viewport of [
            { width: 390, height: 844, formId: '#form_mobile', helpId: '#login_recovery_mobile' },
            { width: 1440, height: 900, formId: '#form_desktop', helpId: '#login_recovery_desktop' },
        ]) {
            await page.setViewportSize({ width: viewport.width, height: viewport.height });
            await page.goto('login', { waitUntil: 'domcontentloaded' });

            const urlBeforeHelp = page.url();
            const form = page.locator(viewport.formId);
            const helpButton = form.getByRole('button', { name: 'Como recuperar o acesso?' });
            const guidance = page.locator(viewport.helpId);

            await expect(helpButton).toHaveAttribute('aria-expanded', 'false');
            await expect(guidance).toBeHidden();
            await helpButton.click();
            await expect(helpButton).toHaveAttribute('aria-expanded', 'true');
            await expect(guidance).toContainText('Procure a organização responsável pelo Interclasses');
            await expect(page).toHaveURL(urlBeforeHelp);

            await helpButton.click();
            await expect(helpButton).toHaveAttribute('aria-expanded', 'false');
            await expect(guidance).toBeHidden();
        }
    });

    test('bloqueia envio duplicado, informa falha de rede e permite tentar de novo', async ({ page }) => {
        let requests = 0;
        let releaseFirstRequest;
        const firstRequestMayFail = new Promise((resolve) => { releaseFirstRequest = resolve; });

        await page.route('**/api/v1/login', async (route) => {
            requests += 1;
            if (requests === 1) {
                await firstRequestMayFail;
                await route.abort('failed');
                return;
            }

            await route.fulfill({
                status: 401,
                contentType: 'application/json',
                body: JSON.stringify({ status: 'erro', mensagem: 'Matrícula ou Senha incorretos.' }),
            });
        });

        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('login', { waitUntil: 'domcontentloaded' });

        const form = page.locator('#form_mobile');
        const matricula = form.getByLabel('Matrícula (RA/NIF)');
        const senha = form.getByLabel('Senha');
        // O nome acessível muda temporariamente para “Entrando…” durante a
        // requisição; o seletor pelo tipo mantém a referência ao mesmo botão.
        const submit = form.locator('button[type="submit"]');
        const feedback = page.locator('#msg_erro_mobile');

        await matricula.fill('aluno_123A');
        await senha.fill('senha de teste');
        await submit.click();

        await expect.poll(() => requests).toBe(1);
        await expect(submit).toBeDisabled();
        await expect(submit).toHaveText('Entrando…');
        await expect(form).toHaveAttribute('aria-busy', 'true');

        // Dispara outro submit como Enter repetido durante a requisição.
        await form.evaluate((element) => element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true,
        })));
        await expect.poll(() => requests).toBe(1);

        releaseFirstRequest();
        await expect(feedback).toContainText('Não foi possível conectar ao servidor');
        await expect(feedback).toContainText('tente novamente');
        await expect(submit).toBeEnabled();
        await expect(submit).toHaveText('Entrar');
        await expect(form).not.toHaveAttribute('aria-busy');
        await expect(matricula).toHaveValue('aluno_123A');

        await submit.click();
        await expect.poll(() => requests).toBe(2);
        await expect(feedback).toContainText('Matrícula ou Senha incorretos.');
        await expect(feedback).not.toContainText('aluno_123A');
        await expect(submit).toBeEnabled();
        await expect(form).not.toHaveAttribute('aria-busy');
        await expect(matricula).toHaveValue('aluno_123A');
    });

    test('retoma o placar do mesmo mesário após reautenticação', async ({ page }) => {
        const baseURL = process.env.SGI_BASE_URL || 'http://localhost/SGI/';
        await page.route('**/api/v1/login', route => route.fulfill({
            json: { status: 'sucesso', redirect: '/painel' },
        }));
        await page.route('**/api/v1/session', route => route.fulfill({
            json: { success: true, usuario: { id: 7, nivel: 2 } },
        }));
        await page.route('**/jogos/placar', route => route.fulfill({
            contentType: 'text/html',
            body: '<!doctype html><html><body>placar</body></html>',
        }));

        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(new URL('login', baseURL).href, { waitUntil: 'domcontentloaded' });
        await page.evaluate(() => window.sessionStorage.setItem('sgi-offline-reauth-v1', JSON.stringify({
            userId: '7',
            path: '/jogos/placar',
            createdAt: Date.now(),
        })));

        const form = page.locator('#form_desktop');
        await form.getByLabel('Matrícula (RA/NIF)').fill('mesario');
        await form.getByLabel('Senha').fill('senha de teste');
        await form.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/jogos\/placar$/);
        await expect.poll(() => page.evaluate(() => window.sessionStorage.getItem('sgi-offline-reauth-v1')))
            .toBeNull();
    });
});
