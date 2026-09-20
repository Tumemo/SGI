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

test('carregarJogoLocalTemporario inicializa ocorrencias e popula turmas na final', async () => {
    const source = fs.readFileSync('resources/js/pages/competicoes/placar.js', 'utf8');
    let api;
    const elements = {};
    function getOrCreate(id) {
        if (!elements[id]) {
            elements[id] = { id, innerHTML: '', value: '', disabled: false, style: {}, appendChild: () => {}, classList: { add: () => {}, remove: () => {} }, isConnected: true, options: [], reset: function () { this.value = ''; } };
        }
        return elements[id];
    }
    const document = {
        getElementById: (id) => getOrCreate(id),
        createElement: () => ({
            _text: '',
            set textContent(v) { this._text = v; this.innerHTML = v; },
            get textContent() { return this._text; },
            innerHTML: ''
        }),
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: () => {},
        removeEventListener: () => {},
        referrer: '',
    };
    const partidasStore = [
        { id_partida: 'mm_local_-2_1', jogos_id_jogo: -2, equipes_id_equipe: 1, id_turma: 10, nome_turma: '3º A' },
        { id_partida: 'mm_local_-2_2', jogos_id_jogo: -2, equipes_id_equipe: 2, turmas_id_turma: 20, nome_turma: '3º B' },
    ];
    const jogosStore = [
        { id_jogo: -2, nome_jogo: 'MM:2:0:N', tipo_competicao: 'mata_mata', status_jogo: 'Agendado' },
    ];
    const window = {
        location: { search: '?id_jogo=-2' },
        SGIPage: { mount: (_name, factory) => { api = factory({}, { listen: () => {} }); }, ready: () => {} },
        SGICronometro: {},
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show: () => {}, hide: () => {} }), getInstance: () => ({ show: () => {}, hide: () => {} }) } },
        SGIDataLayer: {
            read: async (store) => {
                if (store === 'jogos') return jogosStore;
                if (store === 'partidas') return partidasStore;
                return [];
            },
        },
        SGI: { alert: () => {}, confirm: async () => true },
    };
    vm.runInNewContext(source, { window, document, bootstrap: window.bootstrap, SGI: window.SGI, URL, URLSearchParams, fetch: async () => ({ ok: true, text: async () => '[]' }), navigator: { onLine: false }, setTimeout, clearTimeout, console });

    const select = document.getElementById('filtroTurmaOcorrencia');
    await api.carregarDados();

    assert.ok(select.innerHTML.includes('<option value="10">'), 'turma 10 deve estar no select');
    assert.ok(select.innerHTML.includes('<option value="20">'), 'turma 20 deve estar no select');

    // abrirModalOcorrencia também garante o select atualizado
    select.innerHTML = '<option value="">Selecione a turma</option>';
    api.abrirModalOcorrencia();
    assert.ok(select.innerHTML.includes('<option value="10">'), 'turma 10 deve estar disponível ao abrir o modal');
    assert.ok(select.innerHTML.includes('<option value="20">'), 'turma 20 deve estar disponível ao abrir o modal');
});

test('chaveamento-engine preserva id_turma ao projetar equipes locais em partidas', () => {
    const source = fs.readFileSync('resources/js/offline/chaveamento-engine.js', 'utf8');
    assert.ok(source.includes('id_turma: p.id_turma != null ? Number(p.id_turma) : null'), 'chaveamento-engine deve mapear id_turma a partir de partidasLocais');
});
