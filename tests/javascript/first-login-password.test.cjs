const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');

const source = fs.readFileSync('resources/js/pages/aluno/trocar-senha.js', 'utf8');

test('troca inicial usa a base da aplicação e envia o token CSRF da sessão', async () => {
    const form = {};
    const passwordInput = { value: 'SenhaNova#2026', focus() {} };
    const confirmationInput = { value: 'SenhaNova#2026', focus() {} };
    const message = { className: '', classList: { add() {} }, textContent: '' };
    const submitButton = { disabled: false };
    const elements = {
        formPrimeiroAcesso: form,
        novaSenhaPrimeiroAcesso: passwordInput,
        confirmarSenhaPrimeiroAcesso: confirmationInput,
        msgPrimeiroAcesso: message,
        btnSalvarSenhaPrimeiroAcesso: submitButton,
    };
    let submitHandler;
    let request;
    const window = {
        SGI_API_BASE: '/sgi/api/v1/',
        SGI_CSRF_TOKEN: 'csrf-da-sessao',
        location: { origin: 'http://sgi.test', href: 'http://sgi.test/sgi/aluno/trocar-senha', reload() {} },
        SGIPage: {
            mount(_name, callback) {
                callback({}, { listen(_element, _eventName, handler) { submitHandler = handler; } });
            },
            ready(callback) { callback(); },
        },
    };
    const context = {
        window,
        document: { getElementById(id) { return elements[id]; } },
        navigator: { onLine: true },
        URL,
        fetch: async (url, init) => {
            request = { url, init };
            return {
                ok: true,
                async json() { return { success: true, redirect: '/sgi/aluno/termos' }; },
            };
        },
    };

    vm.runInNewContext(source, context);
    assert.equal(typeof submitHandler, 'function');
    await submitHandler({ preventDefault() {} });

    assert.equal(request.url, 'http://sgi.test/sgi/api/v1/senha');
    assert.equal(request.init.headers['X-SGI-CSRF'], 'csrf-da-sessao');
    assert.equal(JSON.parse(request.init.body).nova_senha, 'SenhaNova#2026');
    assert.equal(window.location.href, '/sgi/aluno/termos');
});
