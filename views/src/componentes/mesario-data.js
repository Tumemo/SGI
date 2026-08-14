/* SGI Mesário — dados estruturados locais (IndexedDB, sem dependência CDN).
   As stores equivalem às tabelas Dexie: jogos, partidas, atletas, turmas,
   modalidades, categorias, locais, ocorrencias, chaveamentos e fila_sincronizacao. */
(function () {
    'use strict';
    if (window.__SGI_MESARIO_DATA__) return;
    window.__SGI_MESARIO_DATA__ = true;

    var DB = 'sgi_mesario_dados', VERSION = 1;
    var STORES = ['jogos', 'partidas', 'atletas', 'turmas', 'modalidades', 'categorias', 'locais',
        'ocorrencias', 'ocorrencias_turmas', 'chaveamentos', 'fila_sincronizacao'];
    var session = String(window.SGI_SESSION_ID || 'anon');
    var dbPromise;

    function open() {
        if (dbPromise) return dbPromise;
        dbPromise = new Promise(function (resolve, reject) {
            var request = indexedDB.open(DB, VERSION);
            request.onupgradeneeded = function (e) {
                var db = e.target.result;
                STORES.forEach(function (name) {
                    if (!db.objectStoreNames.contains(name)) db.createObjectStore(name, { keyPath: 'key' });
                });
            };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
        return dbPromise;
    }
    function key(store, id) { return session + '|' + store + '|' + String(id); }
    function put(store, id, value) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var tx = db.transaction(store, 'readwrite');
            tx.objectStore(store).put({ key: key(store, id), value: value, savedAt: Date.now() });
            tx.oncomplete = resolve; tx.onerror = function () { reject(tx.error); };
        }); });
    }
    function get(store, id) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var r = db.transaction(store, 'readonly').objectStore(store).get(key(store, id));
            r.onsuccess = function () { resolve(r.result && r.result.value); }; r.onerror = function () { reject(r.error); };
        }); });
    }
    function remove(store, id) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var tx = db.transaction(store, 'readwrite'); tx.objectStore(store).delete(key(store, id));
            tx.oncomplete = resolve; tx.onerror = function () { reject(tx.error); };
        }); });
    }
    function all(store) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var r = db.transaction(store, 'readonly').objectStore(store).getAll();
            r.onsuccess = function () { resolve((r.result || []).filter(function (x) { return x.key.indexOf(session + '|') === 0; }).map(function (x) { return x.value; })); };
            r.onerror = function () { reject(r.error); };
        }); });
    }
    function urlInfo(url) { try { var u = new URL(url, location.href); return { file: u.pathname.split('/').pop(), q: u.searchParams }; } catch (_) { return {}; } }
    function idFor(file, row, fallback) {
        var fields = { jogos: 'id_jogo', partidas: 'id_partida', turmas: 'id_turma', modalidades: 'id_modalidade', categorias: 'id_categoria', locais: 'id_local', artilheiro: 'id_artilheiro', ocorrencias: 'id_ocorrencia' };
        return row && (row[fields[file]] || row.id || fallback);
    }
    function capture(url, text) {
        var info = urlInfo(url), file = info.file, data;
        try { data = JSON.parse(text); } catch (_) { return Promise.resolve(); }
        var rows = Array.isArray(data) ? data : (data && (data.dados || data.ranking || data.participantes));
        var store = { 'jogos.php': 'jogos', 'partidas.php': 'partidas', 'turmas.php': 'turmas', 'modalidades.php': 'modalidades', 'categorias.php': 'categorias', 'locais.php': 'locais', 'artilheiro.php': 'atletas', 'ocorrencias.php': 'ocorrencias', 'ocorrencias_turmas.php': 'ocorrencias_turmas', 'chaveamento.php': 'chaveamentos' }[file];
        if (!store) return Promise.resolve();
        if (!Array.isArray(rows)) rows = [data];
        return Promise.all(rows.filter(function (r) { return r && typeof r === 'object'; }).map(function (r, i) {
            var identity = idFor(file.replace('.php', ''), r, url + '#' + i);
            return put(store, identity, r);
        }));
    }
    function bodyOf(item) { try { return typeof item.body === 'string' ? JSON.parse(item.body || '{}') : (item.body || {}); } catch (_) { return {}; } }
    function project(item) {
        var info = urlInfo(item.url), data = bodyOf(item), file = info.file, temporary = 'temp_' + item.id;
        if (file === 'jogos.php' && data.id_jogo) return get('jogos', data.id_jogo).then(function (old) { return put('jogos', data.id_jogo, Object.assign({}, old || { id_jogo: data.id_jogo }, data, { _pendente: true })); });
        if (file === 'lancar_resultado.php' && data.id_jogo) return all('partidas').then(function (partidas) {
            return Promise.all((data.resultados || []).map(function (r, i) {
                var old = partidas.filter(function (p) { return String(p.jogos_id_jogo) === String(data.id_jogo) && String(p.equipes_id_equipe) === String(r.id_equipe); })[0];
                return put('partidas', (old && old.id_partida) || ('temp_partida_' + item.id + '_' + i), Object.assign({}, old || {}, { jogos_id_jogo: data.id_jogo, equipes_id_equipe: r.id_equipe, resultado_partida: r.gols, _pendente: true }));
            }));
        });
        if (file === 'artilheiro.php' && item.method === 'POST') return put('atletas', temporary, Object.assign({ id_artilheiro: temporary, _pendente: true }, data));
        if (file === 'ocorrencias.php' && item.method === 'POST') return put('ocorrencias', temporary, Object.assign({ id_ocorrencia: temporary, _pendente: true }, data));
        if (file === 'ocorrencias_turmas.php' && item.method === 'POST') return put('ocorrencias_turmas', temporary, Object.assign({ id_ocorrencia: temporary, _pendente: true }, data));
        return Promise.resolve();
    }
    function localGet(url) {
        var info = urlInfo(url), file = info.file, store = { 'jogos.php': 'jogos', 'partidas.php': 'partidas', 'turmas.php': 'turmas', 'modalidades.php': 'modalidades', 'categorias.php': 'categorias', 'locais.php': 'locais', 'artilheiro.php': 'atletas', 'ocorrencias.php': 'ocorrencias', 'ocorrencias_turmas.php': 'ocorrencias_turmas', 'chaveamento.php': 'chaveamentos' }[file];
        // Endpoints com "acao" possuem formatos especiais; o cache por URL
        // da camada base preserva exatamente a resposta original nesses casos.
        if (!store || info.q.get('acao')) return Promise.resolve(null);
        return all(store).then(function (rows) {
            var idJogo = info.q.get('id_jogo'), idInter = info.q.get('id_interclasse'), idMod = info.q.get('id_modalidade');
            if (idJogo) rows = rows.filter(function (r) { return String(r.jogos_id_jogo || r.id_jogo) === String(idJogo); });
            if (idInter) rows = rows.filter(function (r) { return String(r.interclasses_id_interclasse || r.id_interclasse) === String(idInter); });
            if (idMod) rows = rows.filter(function (r) { return String(r.modalidades_id_modalidade || r.id_modalidade) === String(idMod); });
            return new Response(JSON.stringify(rows), { status: 200, headers: { 'Content-Type': 'application/json' } });
        });
    }

    // A UI existente continua usando fetch; esta ponte faz as leituras offline
    // virem das tabelas locais e espelha cada GET online nelas.
    var baseFetch = window.fetch && window.fetch.bind(window);
    if (baseFetch) window.fetch = function (input, init) {
        var url = typeof input === 'string' ? input : input.url;
        var method = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();
        if (method === 'GET' && navigator.onLine === false) {
            // A resposta completa por URL é a fonte principal: ela preserva
            // exatamente o formato esperado por cada tela. Só recorremos às
            // tabelas estruturadas quando a rota ainda não possui snapshot.
            return baseFetch(input, init).catch(function () {
                return localGet(url).then(function (res) {
                    if (res) return res;
                    throw new Error('Dados não disponíveis offline para esta consulta.');
                });
            });
        }
        return baseFetch(input, init).then(function (res) {
            if (method === 'GET' && res.ok) return res.clone().text().then(function (text) { return capture(url, text); }).catch(function () {}).then(function () { return res; });
            return res;
        });
    };

    window.SGIDataLayer = {
        onQueued: function (item) { return Promise.all([project(item), put('fila_sincronizacao', item.id, item)]); },
        onSynced: function (item, text) {
            var info = urlInfo(item.url), resposta = {};
            try { resposta = JSON.parse(text || '{}'); } catch (_) {}
            var store = info.file === 'artilheiro.php' ? 'atletas' : (info.file === 'ocorrencias.php' ? 'ocorrencias' : (info.file === 'ocorrencias_turmas.php' ? 'ocorrencias_turmas' : null));
            var temp = 'temp_' + item.id;
            if (store && item.method === 'POST' && resposta.id) {
                return get(store, temp).then(function (row) {
                    if (!row) return remove('fila_sincronizacao', item.id);
                    return Promise.all([put(store, resposta.id, Object.assign({}, row, { id: resposta.id, _pendente: false })), remove(store, temp), remove('fila_sincronizacao', item.id)]);
                });
            }
            return remove('fila_sincronizacao', item.id);
        },
        capture: capture,
        read: function (store) { return all(store); },
        localGet: localGet
    };
})();
