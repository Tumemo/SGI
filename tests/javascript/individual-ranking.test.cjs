const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function carregarPlacar() {
    const source = fs.readFileSync('resources/js/pages/competicoes/placar.js', 'utf8');
    let api;
    const document = {
        getElementById: () => null,
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: () => {},
        removeEventListener: () => {},
        referrer: '',
    };
    const window = {
        location: { search: '?id_jogo=20' },
        SGIPage: {
            mount: (_name, factory) => { api = factory({}, { listen: () => {} }); },
            ready: () => {},
        },
        SGICronometro: {},
    };
    vm.runInNewContext(source, {
        window,
        document,
        URL,
        URLSearchParams,
        fetch: () => Promise.reject(new Error('não esperado neste teste')),
        navigator: { onLine: true },
        setTimeout,
        clearTimeout,
        console,
    });
    return api;
}

test('placar escolhe prova individual pelo tipo semântico, mesmo com FK diferente de 2', () => {
    const placar = carregarPlacar();
    assert.equal(placar.jogoEhIndividual({ tipo_competicao: 'individual', tipos_modalidades_id_tipo_modalidade: 37 }), true);
    assert.equal(placar.jogoEhIndividual({ tipo_competicao: 'mata_mata', nome_jogo: 'IND:20', tipos_modalidades_id_tipo_modalidade: 37 }), false);
});

test('placar não transforma tag antiga em prova coletiva quando o tipo semântico é individual', () => {
    const placar = carregarPlacar();
    assert.equal(placar.jogoEhIndividual({ tipo_competicao: 'individual', nome_jogo: 'MM:2:0:N' }), true);
});
