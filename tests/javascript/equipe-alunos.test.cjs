const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/js/pages/competicoes/equipe-alunos.js', 'utf8');

function loadPageActions() {
    let factory = null;
    const window = {
        SGIHtml: {
            escape: value => String(value == null ? '' : value)
                .replace(/[&<>"']/g, character => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                })[character]),
        },
        SGIPage: {
            mount: (_name, callback) => { factory = callback; },
        },
    };
    const document = { getElementById: () => null };
    vm.runInNewContext(source, { window, document, URLSearchParams });
    assert.equal(typeof factory, 'function');
    return factory({}, { listen: () => {} });
}

test('a tela de adicionar atletas renderiza nome e matrícula sem globals de outra página', () => {
    const page = loadPageActions();
    const card = page.cardAluno({
        id_usuario: 41,
        nome_usuario: '<img src=x onerror=alert(1)>',
        matricula_usuario: 'RM<&"41',
        genero_usuario: 'MASC',
        inscrito: 0,
    });

    assert.match(card, /&lt;img src=x onerror=alert\(1\)&gt;/);
    assert.match(card, /RM&lt;&amp;&quot;41/);
    assert.doesNotMatch(card, /<img src=x/);
});
