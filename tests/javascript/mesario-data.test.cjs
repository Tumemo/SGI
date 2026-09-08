const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/js/offline/mesario-data.js', 'utf8');

function criarIndexedDbFake() {
    const stores = new Map();
    const nomes = { contains: (nome) => stores.has(nome) };
    const banco = {
        objectStoreNames: nomes,
        createObjectStore(nome) {
            stores.set(nome, new Map());
            return {};
        },
        transaction(nome) {
            const dados = stores.get(nome);
            const tx = { oncomplete: null, onerror: null };
            const concluir = () => setTimeout(() => tx.oncomplete && tx.oncomplete(), 0);
            tx.objectStore = function () {
                    return {
                        put(valor) {
                            const request = {};
                            dados.set(valor.key, valor);
                            setTimeout(() => {
                                request.result = valor;
                                if (request.onsuccess) request.onsuccess();
                                concluir();
                            }, 0);
                            return request;
                        },
                        get(chave) {
                            const request = {};
                            setTimeout(() => {
                                request.result = dados.get(chave);
                                if (request.onsuccess) request.onsuccess();
                            }, 0);
                            return request;
                        },
                        getAll() {
                            const request = {};
                            setTimeout(() => {
                                request.result = [...dados.values()];
                                if (request.onsuccess) request.onsuccess();
                            }, 0);
                            return request;
                        },
                        delete(chave) {
                            const request = {};
                            dados.delete(chave);
                            setTimeout(() => {
                                if (request.onsuccess) request.onsuccess();
                                concluir();
                            }, 0);
                            return request;
                        },
                    };
                };
            return tx;
        },
    };
    return {
        open() {
            const request = {};
            setTimeout(() => {
                request.result = banco;
                if (request.onupgradeneeded) request.onupgradeneeded({ target: { result: banco } });
                if (request.onsuccess) request.onsuccess();
            }, 0);
            return request;
        },
    };
}

async function carregarDataLayer() {
    const window = { SGI_CACHE_KEY: 't11-test' };
    const context = {
        window,
        indexedDB: criarIndexedDbFake(),
        URL,
        Response,
        location: { href: 'https://sgi.test/dashboard.php' },
        navigator: { onLine: true },
        fetch: () => Promise.reject(new Error('não usado neste teste')),
        Promise,
        setTimeout,
        clearTimeout,
        console,
    };
    vm.runInNewContext(source, context);
    await window.SGIDataLayer.upsert('ocorrencias', 10, {
        id_ocorrencia: 10,
        descricao_ocorrencia: '[JOGO:7][TURMA:3]Primeira',
        id_jogo: 7,
    });
    await window.SGIDataLayer.upsert('ocorrencias', 11, {
        id_ocorrencia: 11,
        descricao_ocorrencia: '[JOGO:7][TURMA:3]Segunda',
        id_jogo: 7,
        status_ocorrencia: '1',
    });
    await window.SGIDataLayer.upsert('ocorrencias', 12, {
        id_ocorrencia: 12,
        descricao_ocorrencia: '[JOGO:7][TURMA:3]Inativa',
        id_jogo: 7,
        status_ocorrencia: '0',
    });
    await window.SGIDataLayer.upsert('ocorrencias_turmas', 21, {
        id_ocorrencia_turma: 21,
        turmas_id_turma: 3,
        descricao_ocorrencia_turma: 'Turma certa',
    });
    return window.SGIDataLayer;
}

test('consulta de ocorrência versionada filtra por ID e não transforma ausência em lista inteira', async () => {
    const layer = await carregarDataLayer();
    const exata = await layer.localGet('https://sgi.test/api/v1/ocorrencias?id_ocorrencia=11');
    assert.deepEqual(await exata.json(), [{
        id_ocorrencia: 11,
        descricao_ocorrencia: '[JOGO:7][TURMA:3]Segunda',
        id_jogo: 7,
        status_ocorrencia: '1',
    }]);
    const ausente = await layer.localGet('https://sgi.test/api/ocorrencias.php?id_ocorrencia=999');
    assert.deepEqual(await ausente.json(), []);
    const ativas = await layer.localGet('https://sgi.test/api/v1/ocorrencias?status_ocorrencia=1');
    assert.deepEqual((await ativas.json()).map((row) => row.id_ocorrencia), [11]);
    const turma = await layer.localGet('https://sgi.test/api/v1/ocorrencias-turmas?id_ocorrencia_turma=21');
    assert.deepEqual((await turma.json()).map((row) => row.id_ocorrencia_turma), [21]);
});

test('projeção PUT preserva referências e POST temporário preserva os aliases de turma e jogo', async () => {
    const layer = await carregarDataLayer();
    await layer.onQueued({
        id: 12,
        method: 'PUT',
        url: 'https://sgi.test/api/v1/ocorrencias',
        body: JSON.stringify({ id_ocorrencia: 11, descricao_ocorrencia: 'Segunda editada' }),
    });
    const editada = (await layer.read('ocorrencias')).find((row) => row.id_ocorrencia === 11);
    assert.equal(editada.descricao_ocorrencia, '[JOGO:7][TURMA:3]Segunda editada');
    assert.equal(editada._pendente, true);

    await layer.onQueued({
        id: 13,
        method: 'POST',
        url: 'https://sgi.test/api/ocorrencias.php',
        body: JSON.stringify({ id_jogo: -5, id_turma: 3, usuarios_id_usuario: 20, descricao_ocorrencia: 'Temporária' }),
    });
    const temporaria = (await layer.read('ocorrencias')).find((row) => row.id_ocorrencia === 'temp_13');
    assert.equal(temporaria.turmas_id_turma, 3);
    assert.equal(temporaria.jogos_id_jogo, -5);
    assert.equal(temporaria.id_usuario, 20);
});
