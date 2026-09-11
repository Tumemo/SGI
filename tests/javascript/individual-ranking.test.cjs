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

function carregarChaveamento() {
    const source = fs.readFileSync('resources/js/pages/competicoes/chaveamento.js', 'utf8');
    let api;
    const document = {
        createElement: () => ({ textContent: '', innerHTML: '' }),
        getElementById: () => null,
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: () => {},
        removeEventListener: () => {},
    };
    const window = {
        location: { search: '?id=' },
        SGIPage: {
            mount: (_name, factory) => { api = factory({ value3: 0 }, { listen: () => {}, onDeactivate: () => {} }); },
            ready: () => {},
        },
        SGIInterclasse: {
            getInterclasseById: async () => ({ id_interclasse: 10, nome_interclasse: 'Fixture' }),
            getActiveInterclasse: async () => null,
        },
        SGI: { alert: async () => {}, confirm: async () => true },
    };
    vm.runInNewContext(source, {
        window,
        document,
        URLSearchParams,
        URL,
        Event,
        SGI: window.SGI,
        fetch: async () => ({ ok: true, json: async () => [] }),
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

test('placar mantém tipo desconhecido bloqueado em vez de inferir pelo ID legado', () => {
    const placar = carregarPlacar();
    assert.equal(placar.resolverTipoCompeticao({ tipos_modalidades_id_tipo_modalidade: 2 }), null);
    assert.equal(placar.jogoEhIndividual({ tipos_modalidades_id_tipo_modalidade: 2 }), false);
});

test('chaveamento classifica pelo nome cadastrado e bloqueia FK legada sem semântica', () => {
    const chaveamento = carregarChaveamento();
    assert.equal(chaveamento.resolverTipoCompeticao({ nome_tipo_modalidade: 'Individual', tipos_modalidades_id_tipo_modalidade: 37 }), 'individual');
    assert.equal(chaveamento.resolverTipoCompeticao({ nome_tipo_modalidade: 'Mata-Mata', tipos_modalidades_id_tipo_modalidade: 91 }), 'mata_mata');
    assert.equal(chaveamento.resolverTipoCompeticao({ nome_tipo_modalidade: 'Mata-Mata (Eliminatória)', tipos_modalidades_id_tipo_modalidade: 91 }), 'mata_mata');
    assert.equal(chaveamento.resolverTipoCompeticao({ tipos_modalidades_id_tipo_modalidade: 2 }), null);
});
