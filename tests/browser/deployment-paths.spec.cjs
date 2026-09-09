const { test, expect } = require('./fixtures.cjs');

test('instalação preserva o prefixo nos caminhos de páginas, arquivos e APIs', async ({ page, baseURL }) => {
    const base = new URL(baseURL);
    const prefix = base.pathname.replace(/\/?$/, '/');
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('response', response => {
        if (new URL(response.url()).origin === base.origin && response.status() >= 400) {
            failures.push(`${response.status()} ${response.url()}`);
        }
    });

    await page.goto(base.href);
    expect(new URL(page.url()).pathname).toBe(`${prefix}login`);
    const login = await page.request.post(new URL('api/v1/login', base).href, {
        data: { matricula: 'admin', senha: '123' }
    });
    expect((await login.json()).status).toBe('sucesso');

    await page.goto(new URL('edicoes', base).href);
    await page.waitForLoadState('networkidle');
    expect(new URL(page.url()).pathname).toBe(`${prefix}edicoes`);
    const previous = await page.request.get(new URL('api/v1/equipes', base).href);
    const versioned = await page.request.get(new URL('api/v1/equipes', base).href);
    expect(previous.status()).toBe(200);
    expect(versioned.status()).toBe(200);
    expect(await versioned.json()).toEqual(await previous.json());
    expect(failures).toEqual([]);
});
