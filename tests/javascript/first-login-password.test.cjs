const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');

const source = fs.readFileSync('resources/js/pages/aluno/trocar-senha.js', 'utf8');

function createPageContext() {
    const form = {};
    const passwordInput = { id: 'novaSenhaPrimeiroAcesso', value: 'SenhaNova#2026', type: 'password', focus() {} };
    const confirmationInput = { id: 'confirmarSenhaPrimeiroAcesso', value: 'SenhaNova#2026', type: 'password', focus() {} };
    const message = { className: '', classList: { add() {} }, textContent: '' };
    const submitButton = { disabled: false };
    const toggleButton = (target) => {
        const classes = new Set(['bi', 'bi-eye-slash']);
        const icon = {
            classList: {
                toggle(name, force) {
                    if (force) classes.add(name);
                    else classes.delete(name);
                },
            },
        };
        return {
            dataset: { target },
            attributes: { 'aria-label': 'Mostrar senha', 'aria-pressed': 'false' },
            icon,
            classes,
            querySelector(selector) { return selector === 'i' ? this.icon : null; },
            setAttribute(name, value) { this.attributes[name] = value; },
        };
    };
    const toggles = [
        toggleButton(passwordInput.id),
        toggleButton(confirmationInput.id),
    ];
    const elements = {
        formPrimeiroAcesso: form,
        novaSenhaPrimeiroAcesso: passwordInput,
        confirmarSenhaPrimeiroAcesso: confirmationInput,
        msgPrimeiroAcesso: message,
        btnSalvarSenhaPrimeiroAcesso: submitButton,
    };
    const listeners = [];
    let request;
    const window = {
        SGI_API_BASE: '/sgi/api/v1/',
        SGI_CSRF_TOKEN: 'csrf-da-sessao',
        location: { origin: 'http://sgi.test', href: 'http://sgi.test/sgi/aluno/trocar-senha', reload() {} },
        SGIPage: {
            mount(_name, callback) {
                callback({}, { listen(element, eventName, handler) { listeners.push({ element, eventName, handler }); } });
            },
            ready(callback) { callback(); },
        },
    };
    const context = {
        window,
        document: {
            getElementById(id) { return elements[id]; },
            querySelectorAll(selector) {
                return selector === '.password-visibility-toggle' ? toggles : [];
            },
        },
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
    return {
        elements,
        listeners,
        request: () => request,
        toggles,
        window,
        get submitHandler() {
            return listeners.find(({ element, eventName }) => element === form && eventName === 'submit')?.handler;
        },
        click(toggle) {
            return listeners.find(({ element, eventName }) => element === toggle && eventName === 'click')?.handler();
        },
    };
}

test('troca inicial usa a base da aplicação e envia o token CSRF da sessão', async () => {
    const page = createPageContext();
    assert.equal(typeof page.submitHandler, 'function');
    await page.submitHandler({ preventDefault() {} });

    assert.equal(page.request().url, 'http://sgi.test/sgi/api/v1/senha');
    assert.equal(page.request().init.headers['X-SGI-CSRF'], 'csrf-da-sessao');
    assert.equal(JSON.parse(page.request().init.body).nova_senha, 'SenhaNova#2026');
    assert.equal(page.window.location.href, '/sgi/aluno/termos');
});

test('toggles do primeiro acesso mostram a senha do campo associado sem alterar o outro campo', async () => {
    const page = createPageContext();
    const { elements, toggles } = page;

    await page.click(toggles[0]);
    assert.equal(elements.novaSenhaPrimeiroAcesso.type, 'text');
    assert.equal(elements.confirmarSenhaPrimeiroAcesso.type, 'password');
    assert.equal(toggles[0].attributes['aria-label'], 'Ocultar senha');
    assert.equal(toggles[0].attributes['aria-pressed'], 'true');
    assert.equal(toggles[0].classes.has('bi-eye'), true);
    assert.equal(toggles[0].classes.has('bi-eye-slash'), false);

    await page.click(toggles[1]);
    assert.equal(elements.novaSenhaPrimeiroAcesso.type, 'text');
    assert.equal(elements.confirmarSenhaPrimeiroAcesso.type, 'text');
    assert.equal(toggles[1].attributes['aria-label'], 'Ocultar senha');
    assert.equal(toggles[1].attributes['aria-pressed'], 'true');
    assert.equal(toggles[1].classes.has('bi-eye'), true);
    assert.equal(toggles[1].classes.has('bi-eye-slash'), false);

    await page.click(toggles[0]);
    assert.equal(elements.novaSenhaPrimeiroAcesso.type, 'password');
    assert.equal(elements.confirmarSenhaPrimeiroAcesso.type, 'text');
    assert.equal(toggles[0].attributes['aria-label'], 'Mostrar senha');
    assert.equal(toggles[0].attributes['aria-pressed'], 'false');
    assert.equal(toggles[0].classes.has('bi-eye'), false);
    assert.equal(toggles[0].classes.has('bi-eye-slash'), true);
    assert.equal(toggles[0].icon, toggles[0].querySelector('i'));
});
