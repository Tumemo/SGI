const playwright = require('@playwright/test');

// API preparation uses the same session token as the real browser. Explicit
// headers remain untouched so security tests can send missing/invalid tokens.
function withCsrf(context) {
    let csrf = null;
    return new Proxy(context, {
        get(target, name) {
            const value = target[name];
            if (typeof value !== 'function') return value;
            if (!['get', 'post', 'put', 'patch', 'delete', 'head', 'fetch'].includes(name)) return value.bind(target);
            return async (url, options = {}) => {
                const headers = { ...options.headers };
                const method = String(options.method || name).toUpperCase();
                if (csrf && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)
                    && !Object.keys(headers).some(key => key.toLowerCase() === 'x-sgi-csrf')) {
                    headers['X-SGI-CSRF'] = csrf;
                }
                const response = await value.call(target, url, { ...options, headers });
                if (/\/(?:login\.php|v1\/login)(?:\?|$)/.test(url)) {
                    const payload = await response.json().catch(() => ({}));
                    csrf = payload.csrf_token || null;
                }
                return response;
            };
        }
    });
}

const test = playwright.test.extend({
    request: async ({ request }, use) => use(withCsrf(request)),
});

module.exports = {
    test,
    expect: playwright.expect,
    request: { newContext: async options => withCsrf(await playwright.request.newContext(options)) },
};
