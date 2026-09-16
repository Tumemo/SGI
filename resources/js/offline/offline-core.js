/* ============================================================
   SGI OFFLINE CORE — Offline-first para o painel interno
   (mesario, admin e colaborador)

   Como funciona:
   - GET via fetch/axios/XHR: quando online, a resposta e guardada
     no IndexedDB (por URL). Quando offline, a ultima resposta salva
     e devolvida ao front para a tela continuar funcionando.
   - POST/PUT/DELETE: quando offline, a requisicao entra numa fila
     (IndexedDB). Quando a conexao volta, a fila e enviada em lote.
   - Banner de status fixo no topo avisa "Modo offline / Sincronizando".
   - Nenhuma dependencia externa (IndexedDB puro). Nao toca no banco.
   ============================================================ */
(function () {
    'use strict';
    if (window.__SGI_OFFLINE_CORE__) return;
    window.__SGI_OFFLINE_CORE__ = true;

    var DB_NAME = 'sgi_offline';
    var DB_VERSION = 1;
    var STORE_GET = 'api_get_cache';
    var STORE_QUEUE = 'mutation_queue';
    var MAX_TRIES = 5;
    var RETRY_BASE_MS = 3000;
    var RETRY_MAX_MS = 60000;
    var REQUEST_TIMEOUT_MS = 20000;
    var HEALTH_TIMEOUT_MS = 5000;
    var MAX_IMPORT_BYTES = 2 * 1024 * 1024;
    var MAX_IMPORT_ITEMS = 1000;
    var HEALTH_PATH = '/api/v1/health';
    var COORD_DB_NAME = 'sgi_offline_coord';
    var COORD_DB_VERSION = 1;
    var COORD_STORE = 'leases';
    var COORD_LEASE_MS = 30000;
    var REAUTH_STORAGE_KEY = 'sgi-offline-reauth-v1';

    // Cache GET separado por usuário autenticado: a chave opaca é derivada no
    // servidor e não expõe o PHPSESSID nem reutiliza o ID fixo diretamente.
    // Ela permanece estável no relogin do mesmo operador para recuperar a
    // fila pendente, mas muda entre usuários e na troca de senha.
    var SESSION_KEY = (typeof window !== 'undefined' && window.SGI_CACHE_KEY)
        ? String(window.SGI_CACHE_KEY) : 'anon';
    var COORD_LEASE_ID = 'sync|' + SESSION_KEY;
    var TAB_ID = (function () {
        try {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
        } catch (e) {}
        return 'tab-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
    }());

    function cacheKey(url) {
        return SESSION_KEY + '|' + url;
    }

    function copiarCabecalhos(headers) {
        var copia = {};
        if (!headers) return copia;
        if (typeof headers.forEach === 'function') {
            headers.forEach(function (valor, nome) { copia[nome] = valor; });
            return copia;
        }
        for (var nome in headers) {
            if (Object.prototype.hasOwnProperty.call(headers, nome)) copia[nome] = headers[nome];
        }
        return copia;
    }

    function localizarCabecalho(headers, nome) {
        var alvo = String(nome || '').toLowerCase();
        for (var atual in (headers || {})) {
            if (String(atual).toLowerCase() === alvo) return atual;
        }
        return null;
    }

    function garantirIdMutacao(headers) {
        headers = copiarCabecalhos(headers);
        if (window.SGI_CSRF_TOKEN) {
            Object.keys(headers).forEach(function (name) {
                if (name.toLowerCase() === 'x-sgi-csrf') delete headers[name];
            });
            headers['X-SGI-CSRF'] = window.SGI_CSRF_TOKEN;
        }
        if (!localizarCabecalho(headers, 'X-SGI-Mutation-Id')) {
            headers['X-SGI-Mutation-Id'] = SESSION_KEY + '-' + Date.now().toString(36) + '-' +
                Math.random().toString(36).slice(2, 10);
        }
        return headers;
    }

    function pareceTelaLogin(texto, url) {
        var textoNormalizado = String(texto || '').toLowerCase();
        var urlNormalizada = String(url || '').toLowerCase();
        return urlNormalizada.indexOf('/login') > -1 ||
            textoNormalizado.indexOf('id="form_mobile"') > -1 ||
            textoNormalizado.indexOf('id="form_desktop"') > -1 ||
            textoNormalizado.indexOf('class="ipt-matricula"') > -1 ||
            textoNormalizado.indexOf('sgi - login') > -1;
    }

    function prepararRetornoAutenticacao() {
        var caminho = window.location.pathname + window.location.search + window.location.hash;
        if (!caminho || caminho.charAt(0) !== '/' || caminho.indexOf('//') === 0) caminho = '/painel';
        try {
            window.sessionStorage.setItem(REAUTH_STORAGE_KEY, JSON.stringify({
                userId: window.SGI_SESSION_ID ? String(window.SGI_SESSION_ID) : '',
                path: caminho,
                createdAt: Date.now()
            }));
        } catch (e) {}
        return aplicacaoUrl('/login');
    }

    var dbPromise = null;
    var state = {
        online: navigator.onLine !== false,
        server: 'desconhecido',
        serverCheckedAt: 0,
        serverError: '',
        session: 'desconhecida',
        sessionCheckedAt: 0,
        softOffline: false,
        softOfflineUntil: 0,
        pending: 0,
        needsReview: 0,
        retryablePending: 0,
        lastSyncError: '',
        nextRetryAt: 0
    };
    var listeners = [];
    var retryTimer = null;
    var retryDelay = RETRY_BASE_MS;
    var healthPromise = null;
    var coordDbPromise = null;
    var leaseGeneration = 0;
    var channel = null;

    try {
        if (typeof BroadcastChannel === 'function') {
            channel = new BroadcastChannel('sgi-offline-' + SESSION_KEY);
            channel.onmessage = function (event) {
                var data = event && event.data;
                if (!data || data.owner === TAB_ID) return;
                if (data.type === 'queue-changed' || data.type === 'server-online') {
                    refreshPending().then(notify).catch(noop);
                }
            };
        }
    } catch (e) { channel = null; }

    function noop() {}

    function marcarSoftOffline() {
        state.softOffline = true;
        state.softOfflineUntil = Date.now() + 15000;
        notify();
    }

    function estaSoftOffline() {
        if (state.softOffline && Date.now() > state.softOfflineUntil) {
            state.softOffline = false;
        }
        return state.softOffline;
    }

    function servidorIndisponivel() {
        return state.server === 'indisponivel' || state.server === 'sessao' || estaSoftOffline();
    }

    function avisarAbas(type, extra) {
        if (!channel) return;
        try { channel.postMessage(Object.assign({ type: type, owner: TAB_ID }, extra || {})); } catch (e) {}
    }

    function resolveUrl(url) {
        try {
            var value = String(url || '');
            if (value.indexOf('/api/v1') === 0) value = aplicacaoBasePath() + value;
            return new URL(value, window.location.href).href;
        } catch (e) { return String(url); }
    }

    function healthUrl() {
        return aplicacaoUrl(HEALTH_PATH);
    }

    function aplicacaoBasePath() {
        var base = '';
        if (window.SGI_BASE_PATH) {
            base = '/' + String(window.SGI_BASE_PATH).replace(/^\/+|\/+$/g, '');
        } else {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                var src = scripts[i].src || '';
                var marker = '/assets/';
                var pos = src.indexOf(marker);
                if (pos > -1) {
                    try {
                        base = new URL(src, window.location.href).pathname.split(marker)[0];
                    } catch (e) { base = ''; }
                    break;
                }
            }
        }
        return base;
    }

    function aplicacaoUrl(path) {
        return resolveUrl(aplicacaoBasePath() + path);
    }

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) { return false; }
    }

    function isMutation(method) {
        return method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE';
    }

    function requestUrl(input) {
        if (typeof input === 'string') return input;
        if (input && typeof input.href === 'string') return input.href;
        if (input && typeof input.url === 'string') return input.url;
        return String(input || '');
    }

    function isPasswordChangeEndpoint(url) {
        try {
            var requested = new URL(resolveUrl(url), window.location.href);
            var passwordEndpoint = new URL(resolveUrl('/api/v1/senha'), window.location.href);
            return requested.origin === passwordEndpoint.origin
                && requested.pathname.replace(/\/+$/, '') === passwordEndpoint.pathname.replace(/\/+$/, '');
        } catch (e) {
            return false;
        }
    }

    function lerCorpoRequestParaFila(request) {
        if (!request || request.body === null) return Promise.resolve(null);
        var clone;
        try { clone = request.clone(); }
        catch (err) { return Promise.reject(err); }
        var contentType = '';
        try { contentType = request.headers && request.headers.get('Content-Type') || ''; } catch (e) {}
        if (/^multipart\/form-data(?:\s*;|$)/i.test(contentType) && typeof clone.formData === 'function') {
            return clone.formData();
        }
        if (/^text\//i.test(contentType) || /^application\/x-www-form-urlencoded(?:\s*;|$)/i.test(contentType) ||
            /^application\/(?:[a-z0-9.+-]*\+)?json(?:\s*;|$)/i.test(contentType)) {
            return clone.text();
        }
        return clone.arrayBuffer();
    }

    function fetchComTimeout(input, init, timeoutMs) {
        if (!originalFetch) return Promise.reject(new Error('fetch indisponivel'));
        var controller = typeof AbortController === 'function' ? new AbortController() : null;
        var opcoes = Object.assign({}, init || {});
        var timer = null;
        if (controller) {
            opcoes.signal = controller.signal;
            timer = setTimeout(function () {
                try { controller.abort(); } catch (e) {}
            }, timeoutMs || REQUEST_TIMEOUT_MS);
        }
        return originalFetch(input, opcoes).then(function (res) {
            if (timer) clearTimeout(timer);
            return res;
        }, function (err) {
            if (timer) clearTimeout(timer);
            throw err;
        });
    }

    function verificarServidor(force) {
        if (navigator.onLine === false) {
            state.server = 'indisponivel';
            state.serverError = 'O dispositivo está sem conexão com a rede local.';
            notify();
            return Promise.resolve(false);
        }
        var agora = Date.now();
        if (!force && state.server === 'acessivel' && agora - state.serverCheckedAt < 10000) {
            return Promise.resolve(true);
        }
        if (healthPromise) return healthPromise;
        healthPromise = fetchComTimeout(healthUrl(), {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-SGI-Health-Check': '1' }
        }, HEALTH_TIMEOUT_MS).then(function (res) {
            return res.text().then(function (text) {
                var json = null;
                try { json = text ? JSON.parse(text) : null; } catch (e) {}
                if (!res.ok || !json || json.success !== true || json.status !== 'ok' || json.service !== 'sgi') {
                    throw new Error('O servidor local não confirmou o serviço SGI.');
                }
                state.server = 'acessivel';
                state.serverCheckedAt = Date.now();
                state.serverError = '';
                state.softOffline = false;
                avisarAbas('server-online');
                notify();
                return true;
            });
        }).catch(function (err) {
            state.server = 'indisponivel';
            state.serverCheckedAt = Date.now();
            state.serverError = String((err && err.message) || 'Servidor local indisponível.');
            notify();
            return false;
        }).then(function (result) {
            healthPromise = null;
            return result;
        });
        return healthPromise;
    }

    function verificarSessao(force) {
        if (navigator.onLine === false || servidorIndisponivel()) return Promise.resolve(false);
        var agora = Date.now();
        if (!force && state.session === 'valida' && agora - state.sessionCheckedAt < 30000) {
            return Promise.resolve(true);
        }
        return fetchComTimeout(aplicacaoUrl('/api/v1/session'), {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-SGI-Session-Check': '1' }
        }, HEALTH_TIMEOUT_MS).then(function (res) {
            return res.text().then(function (text) {
                var json = null;
                try { json = text ? JSON.parse(text) : null; } catch (e) {}
                var usuario = json && json.usuario;
                var idAtual = window.SGI_SESSION_ID ? String(window.SGI_SESSION_ID) : '';
                if (!res.ok || !json || json.success !== true || !usuario ||
                    (idAtual && String(usuario.id || '') !== idAtual)) {
                    throw new Error('A sessão do operador expirou ou mudou.');
                }
                state.session = 'valida';
                state.sessionCheckedAt = Date.now();
                if (state.server === 'sessao') state.server = 'acessivel';
                state.serverError = '';
                notify();
                return true;
            });
        }).catch(function (err) {
            state.session = 'expirada';
            state.sessionCheckedAt = Date.now();
            state.server = 'sessao';
            state.serverError = String((err && err.message) || 'A sessão do operador não está disponível.');
            notify();
            return false;
        });
    }

    function verificarAcesso(force) {
        return verificarServidor(force).then(function (ok) {
            return ok ? verificarSessao(force) : false;
        });
    }

    function openCoordDB() {
        if (coordDbPromise) return coordDbPromise;
        coordDbPromise = new Promise(function (resolve, reject) {
            try {
                var req = indexedDB.open(COORD_DB_NAME, COORD_DB_VERSION);
                req.onupgradeneeded = function (event) {
                    var db = event.target.result;
                    if (!db.objectStoreNames.contains(COORD_STORE)) db.createObjectStore(COORD_STORE, { keyPath: 'id' });
                };
                req.onsuccess = function () { resolve(req.result); };
                req.onerror = function () { reject(req.error); };
            } catch (e) { reject(e); }
        });
        return coordDbPromise;
    }

    function adquirirReservaSync() {
        return openCoordDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(COORD_STORE, 'readwrite');
                var store = tx.objectStore(COORD_STORE);
                var req = store.get(COORD_LEASE_ID);
                var agora = Date.now();
                var concedida = false;
                req.onsuccess = function () {
                    var atual = req.result;
                    if (atual && atual.owner !== TAB_ID && Number(atual.expiresAt) > agora) return;
                    leaseGeneration = (Number(atual && atual.generation) || 0) + 1;
                    store.put({ id: COORD_LEASE_ID, owner: TAB_ID, generation: leaseGeneration, expiresAt: agora + COORD_LEASE_MS });
                    concedida = true;
                };
                tx.oncomplete = function () { resolve(concedida); };
                tx.onerror = function () { reject(tx.error); };
                tx.onabort = function () { reject(tx.error || new Error('Reserva de sincronização abortada.')); };
            });
        }).catch(function () {
            // A coordenação é uma proteção adicional. Se o banco auxiliar não
            // puder ser aberto, a fila principal continua disponível na aba.
            return true;
        });
    }

    function liberarReservaSync() {
        return openCoordDB().then(function (db) {
            return new Promise(function (resolve) {
                var tx = db.transaction(COORD_STORE, 'readwrite');
                var store = tx.objectStore(COORD_STORE);
                var req = store.get(COORD_LEASE_ID);
                req.onsuccess = function () {
                    var atual = req.result;
                    if (atual && atual.owner === TAB_ID && Number(atual.generation) === leaseGeneration) store.delete(COORD_LEASE_ID);
                };
                tx.oncomplete = resolve;
                tx.onerror = resolve;
                tx.onabort = resolve;
            });
        }).catch(noop);
    }

    function verificarReservaSync() {
        return openCoordDB().then(function (db) {
            return new Promise(function (resolve) {
                var tx = db.transaction(COORD_STORE, 'readonly');
                var req = tx.objectStore(COORD_STORE).get(COORD_LEASE_ID);
                req.onsuccess = function () {
                    var atual = req.result;
                    resolve(!!(atual && atual.owner === TAB_ID &&
                        Number(atual.generation) === leaseGeneration &&
                        Number(atual.expiresAt) > Date.now()));
                };
                req.onerror = function () { resolve(false); };
            });
        }).catch(function () {
            // Se o banco auxiliar ficou indisponível, a idempotência do
            // servidor continua sendo a última proteção contra replay.
            return true;
        });
    }

    function renovarReservaSync() {
        return openCoordDB().then(function (db) {
            return new Promise(function (resolve) {
                var tx = db.transaction(COORD_STORE, 'readwrite');
                var store = tx.objectStore(COORD_STORE);
                var req = store.get(COORD_LEASE_ID);
                var renovada = false;
                req.onsuccess = function () {
                    var atual = req.result;
                    if (atual && atual.owner === TAB_ID && Number(atual.generation) === leaseGeneration) {
                        store.put({ id: COORD_LEASE_ID, owner: TAB_ID, generation: leaseGeneration,
                            expiresAt: Date.now() + COORD_LEASE_MS });
                        renovada = true;
                    }
                };
                tx.oncomplete = function () { resolve(renovada); };
                tx.onerror = function () { resolve(false); };
                tx.onabort = function () { resolve(false); };
            });
        }).catch(function () { return true; });
    }

    /* ------------------------- IndexedDB ------------------------- */
    function openDB() {
        if (dbPromise) return dbPromise;
        dbPromise = new Promise(function (resolve, reject) {
            try {
                var req = indexedDB.open(DB_NAME, DB_VERSION);
                req.onupgradeneeded = function (e) {
                    var d = e.target.result;
                    if (!d.objectStoreNames.contains(STORE_GET)) {
                        d.createObjectStore(STORE_GET, { keyPath: 'url' });
                    }
                    if (!d.objectStoreNames.contains(STORE_QUEUE)) {
                        d.createObjectStore(STORE_QUEUE, { keyPath: 'id', autoIncrement: true });
                    }
                };
                req.onsuccess = function () { resolve(req.result); };
                req.onerror = function () { reject(req.error); };
                req.onblocked = noop;
            } catch (err) { reject(err); }
        });
        return dbPromise;
    }

    function idbGet(url) {
        var key = cacheKey(url);
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_GET, 'readonly');
                var req = tx.objectStore(STORE_GET).get(key);
                req.onsuccess = function () { resolve(req.result || null); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function idbFindUrl(url) {
        var pathQuery = '';
        var idTurmaMatch = null;
        try {
            var parsed = new URL(url);
            pathQuery = parsed.pathname + parsed.search;
            if (pathQuery.indexOf('acao=listar_atletas') > -1) {
                var matchT = parsed.searchParams.get('id_turma');
                if (matchT) idTurmaMatch = 'id_turma=' + matchT;
            }
        } catch (e) {
            pathQuery = String(url);
        }
        return openDB().then(function (db) {
            return new Promise(function (resolve) {
                var tx = db.transaction(STORE_GET, 'readonly');
                var req = tx.objectStore(STORE_GET).getAll();
                req.onsuccess = function () {
                    var rows = req.result || [];
                    var prefix = SESSION_KEY + '|';
                    var match = rows.find(function (r) {
                        if (!r || !r.url || r.url.indexOf(prefix) !== 0) return false;
                        if (r.url.indexOf(pathQuery) > -1) return true;
                        if (idTurmaMatch && r.url.indexOf('acao=listar_atletas') > -1 && r.url.indexOf(idTurmaMatch) > -1) return true;
                        return false;
                    });
                    resolve(match || null);
                };
                req.onerror = function () { resolve(null); };
            });
        });
    }

    function idbPut(rec) {
        rec.url = cacheKey(rec.url);
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_GET, 'readwrite');
                tx.objectStore(STORE_GET).put(rec);
                tx.oncomplete = resolve;
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function idbQueueAdd(item) {
        if (item && isPasswordChangeEndpoint(item.url)) {
            return Promise.reject(new Error('Troca de senha não pode ser armazenada offline. Conecte-se para continuar.'));
        }
        item.session = SESSION_KEY;
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_QUEUE, 'readwrite');
                var req = tx.objectStore(STORE_QUEUE).add(item);
                // O sucesso do pedido ainda pode ser seguido por aborto da
                // transação. Só confirme o salvamento depois do commit local.
                tx.oncomplete = function () { resolve(req.result); };
                tx.onabort = tx.onerror = function () { reject(tx.error || new Error('A gravação local foi interrompida.')); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function idbQueueAll() {
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                // Limpa eventual mutação antiga: credenciais nunca devem
                // continuar na fila, ser exportadas ou chegar ao sync.
                var tx = db.transaction(STORE_QUEUE, 'readwrite');
                var store = tx.objectStore(STORE_QUEUE);
                var req = store.getAll();
                var todos = [];
                req.onsuccess = function () {
                    (req.result || []).forEach(function (item) {
                        if (!item) return;
                        if (isPasswordChangeEndpoint(item.url)) {
                            store.delete(item.id);
                            return;
                        }
                        if (String(item.session || '') === SESSION_KEY) todos.push(item);
                    });
                };
                req.onerror = function () { reject(req.error); };
                tx.oncomplete = function () { resolve(todos); };
                tx.onerror = function () { reject(tx.error); };
                tx.onabort = function () { reject(tx.error || new Error('A leitura da fila foi interrompida.')); };
            });
        });
    }

    function idbQueueDelete(id) {
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_QUEUE, 'readwrite');
                tx.objectStore(STORE_QUEUE).delete(id);
                tx.oncomplete = resolve;
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function idbQueueUpdate(item) {
        if (item && isPasswordChangeEndpoint(item.url)) {
            return Promise.reject(new Error('Troca de senha não pode ser armazenada offline. Conecte-se para continuar.'));
        }
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_QUEUE, 'readwrite');
                tx.objectStore(STORE_QUEUE).put(item);
                tx.oncomplete = resolve;
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    /* ------------------------- Utils de resposta ------------------------- */
    function fakeResponse(body) {
        var text = typeof body === 'string' ? body : JSON.stringify(body);
        return new Response(text, {
            status: 200,
            headers: { 'Content-Type': 'application/json' }
        });
    }

    /* ------------------------- Estado e notificacao ------------------------- */
    function stateSnapshot() {
        return {
            online: state.online,
            server: state.server,
            serverCheckedAt: state.serverCheckedAt,
            serverError: state.serverError,
            session: state.session,
            sessionCheckedAt: state.sessionCheckedAt,
            softOffline: state.softOffline,
            softOfflineUntil: state.softOfflineUntil,
            pending: state.pending,
            needsReview: state.needsReview,
            retryablePending: state.retryablePending,
            syncing: syncing,
            lastSyncError: state.lastSyncError,
            nextRetryAt: state.nextRetryAt
        };
    }

    function notify() {
        updateBanner();
        for (var i = 0; i < listeners.length; i++) {
            try { listeners[i](stateSnapshot()); } catch (e) { /* noop */ }
        }
    }

    function refreshPending() {
        return idbQueueAll().then(function (q) {
            state.pending = q.length;
            state.needsReview = q.filter(function (item) { return item && item.needsReview; }).length;
            // Uma mutação que exige revisão pode depender das anteriores. Para
            // preservar a ordem, a retomada automática para até o usuário
            // revisar ou pedir uma nova tentativa manual.
            state.retryablePending = state.needsReview ? 0 : q.length;
            if (!state.pending) {
                state.lastSyncError = '';
                clearSyncRetry();
            }
            return q;
        });
    }

    function mutationId(item) {
        var headers = copiarCabecalhos(item && item.headers);
        var nome = localizarCabecalho(headers, 'X-SGI-Mutation-Id');
        return nome ? String(headers[nome] || '') : '';
    }

    function exportPending() {
        return idbQueueAll().then(function (items) {
            return {
                schemaVersion: 1,
                exportedAt: new Date().toISOString(),
                items: (items || []).map(function (item) {
                    var safe = Object.assign({}, item);
                    delete safe.id;
                    delete safe.session;
                    var headers = copiarCabecalhos(safe.headers);
                    var safeHeaders = {};
                    Object.keys(headers).forEach(function (name) {
                        var lower = name.toLowerCase();
                        if (lower === 'x-sgi-mutation-id' || lower === 'content-type' || lower === 'accept') {
                            safeHeaders[name] = headers[name];
                        }
                    });
                    safe.headers = safeHeaders;
                    return safe;
                })
            };
        });
    }

    function downloadPending() {
        return exportPending().then(function (payload) {
            var text = JSON.stringify(payload, null, 2);
            if (typeof Blob === 'undefined' || !window.URL || typeof window.URL.createObjectURL !== 'function') return payload;
            var blob = new Blob([text], { type: 'application/json;charset=utf-8' });
            var url = window.URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'sgi-pendencias-' + new Date().toISOString().replace(/[:.]/g, '-') + '.json';
            link.click();
            setTimeout(function () {
                if (typeof window.URL.revokeObjectURL === 'function') window.URL.revokeObjectURL(url);
            }, 1000);
            return payload;
        });
    }

    function importarPendencias(input) {
        if (typeof input === 'string' && input.length > MAX_IMPORT_BYTES) {
            return Promise.reject(new Error('O arquivo de pendências excede o limite de 2 MB.'));
        }
        if (input && Number(input.size) > MAX_IMPORT_BYTES) {
            return Promise.reject(new Error('O arquivo de pendências excede o limite de 2 MB.'));
        }
        var textoPromise = typeof input === 'string' ? Promise.resolve(input) :
            (input && typeof input.text === 'function' ? input.text() : Promise.reject(new Error('Arquivo de pendências inválido.')));
        return textoPromise.then(function (texto) {
            var payload;
            try { payload = JSON.parse(texto); } catch (e) { throw new Error('O arquivo de pendências não contém JSON válido.'); }
            if (!payload || Number(payload.schemaVersion) !== 1 || !Array.isArray(payload.items)) {
                throw new Error('Versão ou estrutura de pendências não reconhecida.');
            }
            if (payload.items.length > MAX_IMPORT_ITEMS) {
                throw new Error('O arquivo de pendências contém itens demais.');
            }
            if (payload.items.some(function (item) {
                return item && isPasswordChangeEndpoint(item.url);
            })) {
                throw new Error('Arquivos de pendências não podem conter troca de senha. Faça a alteração conectado ao SGI.');
            }
            return idbQueueAll().then(function (atuais) {
                var conhecidos = {};
                (atuais || []).forEach(function (item) { var id = mutationId(item); if (id) conhecidos[id] = true; });
                var aceitos = 0;
                return payload.items.reduce(function (chain, original) {
                    return chain.then(function () {
                        var item = Object.assign({}, original || {});
                        var id = mutationId(item);
                        if (!id || (id !== SESSION_KEY && id.indexOf(SESSION_KEY + '-') !== 0)) {
                            throw new Error('A pendência pertence a outro operador ou não possui identidade.');
                        }
                        item.method = String(item.method || '').toUpperCase();
                        if (!isMutation(item.method)) throw new Error('O arquivo contém uma operação que não pode ser sincronizada.');
                        if (!item.url || !isSameOrigin(item.url)) throw new Error('O arquivo contém uma URL fora do servidor SGI local.');
                        var incomingHeaders = copiarCabecalhos(item.headers);
                        var safeHeaders = {};
                        Object.keys(incomingHeaders).forEach(function (name) {
                            var lower = name.toLowerCase();
                            if (lower === 'x-sgi-mutation-id' || lower === 'content-type' || lower === 'accept') {
                                safeHeaders[name] = incomingHeaders[name];
                            }
                        });
                        item.headers = safeHeaders;
                        if (conhecidos[id]) return;
                        delete item.id;
                        item.session = SESSION_KEY;
                        item.needsReview = !!item.needsReview;
                        item.projectionPending = item.projectionPending !== false;
                        item.tries = Number(item.tries) || 0;
                        return idbQueueAdd(item).then(function () {
                            conhecidos[id] = true;
                            aceitos++;
                        });
                    });
                }, Promise.resolve()).then(function () {
                    return refreshPending().then(function () {
                        notify();
                        avisarAbas('queue-changed');
                        return { imported: aceitos, pending: state.pending };
                    });
                });
            });
        });
    }

    function clearSyncRetry() {
        if (retryTimer) {
            clearTimeout(retryTimer);
            retryTimer = null;
        }
        retryDelay = RETRY_BASE_MS;
        state.nextRetryAt = 0;
    }

    function scheduleSyncRetry() {
        if (retryTimer || !state.online || state.retryablePending <= 0) return;
        var delay = retryDelay;
        retryDelay = Math.min(RETRY_MAX_MS, retryDelay * 2);
        state.nextRetryAt = Date.now() + delay;
        retryTimer = setTimeout(function () {
            retryTimer = null;
            state.nextRetryAt = 0;
            if (navigator.onLine === false) {
                state.online = false;
                notify();
                return;
            }
            syncQueue();
        }, delay);
        notify();
    }

    /* ------------------------- Fila de mutacoes ------------------------- */
    function queueMutation(method, url, body, headers) {
        method = String(method || '').toUpperCase();
        if (isPasswordChangeEndpoint(url)) {
            return Promise.reject(new Error('Troca de senha não pode ser armazenada offline. Conecte-se para continuar.'));
        }
        var storedHeaders = garantirIdMutacao(headers);

        var isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
        var storedBody = body;
        if (body == null) {
            storedBody = null;
        } else if (typeof body === 'string') {
            storedBody = body;
        } else if (isFormData) {
            var formCodificado = new URLSearchParams();
            var possuiArquivo = false;
            body.forEach(function (v, k) {
                if (typeof Blob !== 'undefined' && v instanceof Blob) {
                    possuiArquivo = true;
                    return;
                }
                formCodificado.append(k, String(v));
            });
            if (possuiArquivo) {
                return Promise.reject(new Error('Envios com arquivo não podem ser salvos offline. Conecte-se antes de enviar a foto.'));
            }
            storedBody = formCodificado.toString();
            var contentTypeForm = localizarCabecalho(storedHeaders, 'Content-Type');
            if (contentTypeForm) delete storedHeaders[contentTypeForm];
            storedHeaders['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
        } else if (body instanceof URLSearchParams) {
            storedBody = body.toString();
            var contentTypeParams = localizarCabecalho(storedHeaders, 'Content-Type');
            if (contentTypeParams) delete storedHeaders[contentTypeParams];
            storedHeaders['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
        } else if (body instanceof Blob || body instanceof ArrayBuffer || ArrayBuffer.isView(body)) {
            storedBody = body;
        } else {
            storedBody = JSON.stringify(body);
        }

        var item = {
            method: method,
            url: url,
            body: storedBody,
            headers: storedHeaders,
            createdAt: Date.now(),
            tries: 0,
            needsReview: false,
            projectionPending: true
        };
        var bodyJson = null;
        try { bodyJson = typeof storedBody === 'string' ? JSON.parse(storedBody || '{}') : storedBody; } catch (_) { bodyJson = null; }
        var arquivo = fileFromUrl(url);
        var idTemporario = bodyJson && bodyJson.id_ocorrencia != null ? String(bodyJson.id_ocorrencia) : '';
        var idPontoTemporario = bodyJson && bodyJson.id_ponto != null ? String(bodyJson.id_ponto) : '';
        var arquivoCriacao = arquivo === 'ocorrencias' ? 'ocorrencias' : (arquivo === 'pontos' ? 'pontos' : null);
        var idCriacaoTemporaria = arquivo === 'ocorrencias' ? idTemporario : idPontoTemporario;
        var precisaCriacaoDependencia = method === 'PUT' && arquivoCriacao !== null && /^temp_\d+$/.test(idCriacaoTemporaria);
        var dependencia = precisaCriacaoDependencia
            ? idbQueueAll().then(function (fila) {
                var idPai = Number(idCriacaoTemporaria.slice(5));
                var criacao = (fila || []).filter(function (pendente) {
                    return Number(pendente.id) === idPai && pendente.method === 'POST' && fileFromUrl(pendente.url) === arquivoCriacao;
                })[0];
                if (criacao) {
                    item.dependsOn = { mutationId: criacao.id, tempId: idCriacaoTemporaria, field: arquivoCriacao === 'pontos' ? 'id_ponto' : 'id_ocorrencia' };
                }
            })
            : Promise.resolve();
        return dependencia.then(function () { return idbQueueAdd(item); }).then(function (id) {
            item.id = id;
            var projection = (window.SGIDataLayer && window.SGIDataLayer.onQueued)
                ? window.SGIDataLayer.onQueued(item)
                : null;
            // A projeção precisa terminar antes de liberar a UI para que o
            // chaveamento local não leia um estado parcialmente gravado.
            return Promise.resolve(projection).then(function () {
                item.projectionPending = false;
                delete item.projectionError;
                return idbQueueUpdate(item);
            }).catch(function (err) {
                // A mutação principal continua protegida na fila. Guardamos a
                // falha para reexecutá-la antes do envio ao servidor.
                item.projectionError = String((err && err.message) || err || 'Falha ao projetar os dados locais.');
                return idbQueueUpdate(item).catch(noop);
            });
        }).then(function () {
            return refreshPending();
        }).then(function () {
            notify();
            avisarAbas('queue-changed');
            scheduleSyncRetry();
            return item;
        });
    }

    /* ------------------------- Sincronizacao ------------------------- */
    var syncing = false;

    function bodyAsJson(item) {
        try { return typeof item.body === 'string' ? JSON.parse(item.body || '{}') : (item.body || {}); }
        catch (e) { return {}; }
    }

    function corpoComDependenciaResolvida(item) {
        var dependencia = item && item.dependsOn;
        if (!dependencia) return Promise.resolve({ ok: true, body: item.body });
        if (dependencia.resolvedId == null || dependencia.resolvedId === '') {
            return Promise.resolve({
                ok: false,
                message: 'Esta alteração depende de uma criação que ainda não foi confirmada pelo servidor.'
            });
        }
        var body = bodyAsJson(item);
        if (!body || typeof body !== 'object') {
            return Promise.resolve({ ok: false, message: 'O corpo da edição offline não pôde ser reconstituído.' });
        }
        body[dependencia.field || 'id_ocorrencia'] = dependencia.resolvedId;
        return Promise.resolve({ ok: true, body: JSON.stringify(body) });
    }

    function fileFromUrl(url) {
        try {
            var file = new URL(url, window.location.href).pathname.replace(/\/+$/, '').split('/').pop();
            var recursos = {
                resultados: 'resultados',
                artilheiros: 'artilheiros',
                pontos: 'pontos',
                ocorrencias: 'ocorrencias',
                partidas: 'partidas'
            };
            return recursos[file] || file;
        }
        catch (e) { return String(url || '').split('?')[0].split('/').pop(); }
    }

    // Resultados de uma partida derivada materializam o jogo temporário. Gols,
    // cartões e demais registros desse mesmo jogo precisam ser enviados depois
    // para que a API consiga resolver o ID definitivo.
    function tempGameKey(item) {
        var file = fileFromUrl(item.url);
        var data = bodyAsJson(item);
        var id = null;
        if (file === 'resultados') id = data.id_jogo;
        else if (file === 'artilheiros') id = data.jogos_id_jogo;
        else if (file === 'pontos') id = data.jogos_id_jogo;
        else if (file === 'ocorrencias') id = data.id_jogo;
        else if (file === 'partidas') id = data.jogos_id_jogo;
        if (Number(id) >= 0 || id == null) return null;
        return String(data.id_modalidade || '') + '|' + String(id);
    }

    function materializaJogoTemporario(item) {
        return fileFromUrl(item.url) === 'resultados' && Number(bodyAsJson(item).id_jogo) < 0;
    }

    function ordenarFila(queue) {
        var pendentes = (queue || []).slice().sort(function (a, b) {
            var criadoA = Number(a.createdAt) || 0;
            var criadoB = Number(b.createdAt) || 0;
            if (criadoA !== criadoB) return criadoA - criadoB;
            return (Number(a.id) || 0) - (Number(b.id) || 0);
        });
        var criacoes = {};
        pendentes.forEach(function (item) {
            var key = tempGameKey(item);
            if (key && materializaJogoTemporario(item) && !criacoes[key]) criacoes[key] = item;
        });
        var ordenada = [];
        // Ordenação por pares não é transitiva quando dois jogos se intercalam.
        // Escolha a operação mais antiga cujas dependências já foram enviadas.
        while (pendentes.length) {
            var index = pendentes.findIndex(function (item) {
                var criacao = criacoes[tempGameKey(item)];
                return !criacao || materializaJogoTemporario(item) || pendentes.indexOf(criacao) === -1;
            });
            ordenada.push(pendentes.splice(index, 1)[0]);
        }
        return ordenada;
    }

    function lerRespostaSincronizacao(res) {
        var copia = res && res.clone ? res.clone() : res;
        return Promise.resolve(copia ? copia.text() : '').catch(function () { return ''; }).then(function (texto) {
            var json = null;
            try { json = texto ? JSON.parse(texto) : null; } catch (e) { json = null; }
            var redirecionouParaLogin = pareceTelaLogin(texto, (res && res.url) || '');
            return {
                response: res,
                text: texto,
                json: json,
                httpOk: !!(res && res.ok),
                // HTTP 200 sozinho não confirma uma mutação: pode conter HTML,
                // JSON incompleto ou uma resposta de erro sem campo success.
                semanticOk: !redirecionouParaLogin && !!json && typeof json === 'object' &&
                    !Array.isArray(json) && json.success !== false && json.status !== 'erro' &&
                    (json.success === true || json.status === 'sucesso')
            };
        });
    }

    function mensagemResposta(info) {
        if (info && info.json) {
            var mensagem = info.json.message || info.json.mensagem || info.json.error;
            if (mensagem) return mensagem;
        }
        if (info && info.httpOk && !info.semanticOk) return 'O servidor não confirmou o salvamento. A alteração foi mantida neste dispositivo para revisão.';
        if (info && info.response) return 'HTTP ' + info.response.status;
        return 'Não foi possível alcançar o servidor.';
    }

    function erroInterrompeFila(retryable, message) {
        var erro = new Error(message || 'Sincronização interrompida.');
        erro.__sgiSyncHalt = true;
        erro.retryable = !!retryable;
        return erro;
    }

    function registrarFalha(item, summary, options) {
        options = options || {};
        item.tries = (item.tries || 0) + 1;
        item.lastError = options.message || 'Falha ao sincronizar a alteração.';
        item.lastStatus = options.status || 0;
        item.lastFailedAt = Date.now();
        item.needsReview = !!options.needsReview || item.tries >= MAX_TRIES;
        item.retryable = !item.needsReview;
        state.lastSyncError = item.lastError;
        if (item.needsReview) summary.needsReview += 1;
        else summary.failed += 1;
        return idbQueueUpdate(item).then(function () {
            throw erroInterrompeFila(!item.needsReview, item.lastError);
        });
    }

    function confirmarProjecaoRemota(item, text, json, summary) {
        var projection = (window.SGIDataLayer && window.SGIDataLayer.onSynced)
            ? window.SGIDataLayer.onSynced(item, text, json || {})
            : null;
        function resolverDependentes() {
            var arquivo = fileFromUrl(item.url);
            if ((arquivo !== 'ocorrencias' && arquivo !== 'pontos') || item.method !== 'POST' || !json || !json.id) return Promise.resolve();
            return idbQueueAll().then(function (fila) {
                return Promise.all((fila || []).filter(function (pendente) {
                    return pendente.dependsOn && Number(pendente.dependsOn.mutationId) === Number(item.id);
                }).map(function (pendente) {
                    pendente.dependsOn.resolvedId = json.id;
                    return idbQueueUpdate(pendente);
                }));
            });
        }
        return Promise.resolve(projection).then(resolverDependentes).then(function () {
            return idbQueueDelete(item.id);
        }).then(function () {
            summary.synced += 1;
            state.softOffline = false;
            state.lastSyncError = '';
        }).catch(function (err) {
            // O servidor já confirmou a mutação. Mantemos a entrada com a
            // resposta recebida para concluir somente a projeção local, sem
            // reenviar uma operação que poderia duplicar dados.
            item.remoteCommitted = true;
            item.serverResponse = text || '';
            item.projectionError = String((err && err.message) || err || 'Falha ao atualizar o banco local.');
            item.lastError = item.projectionError;
            item.lastFailedAt = Date.now();
            state.lastSyncError = item.lastError;
            summary.failed += 1;
            return idbQueueUpdate(item).then(function () {
                throw erroInterrompeFila(true, item.lastError);
            });
        });
    }

    function concluirConfirmacaoRemotaPendente(item, summary) {
        var json = null;
        try { json = item.serverResponse ? JSON.parse(item.serverResponse) : {}; } catch (e) { json = {}; }
        return confirmarProjecaoRemota(item, item.serverResponse || '', json, summary);
    }

    function prepararProjecaoPendente(item) {
        if (!item.projectionPending || !(window.SGIDataLayer && window.SGIDataLayer.onQueued)) {
            return Promise.resolve();
        }
        return Promise.resolve(window.SGIDataLayer.onQueued(item)).then(function () {
            item.projectionPending = false;
            delete item.projectionError;
            return idbQueueUpdate(item);
        });
    }

    function processarItemDaFila(item, summary) {
        if (item && isPasswordChangeEndpoint(item.url)) {
            // Defesa adicional para uma linha criada por outra aba antiga.
            return idbQueueDelete(item.id).then(function () {
                summary.failed += 1;
            });
        }
        var reservaValida = verificarReservaSync().then(function (ok) {
            if (!ok) throw erroInterrompeFila(true, 'A reserva desta sincronização foi assumida por outra aba.');
        });
        if (item.remoteCommitted) {
            return reservaValida.then(function () { return concluirConfirmacaoRemotaPendente(item, summary); });
        }
        return reservaValida.then(function () { return prepararProjecaoPendente(item); }).then(function () {
            return corpoComDependenciaResolvida(item);
        }).then(function (corpo) {
            if (!corpo.ok) {
                return registrarFalha(item, summary, { status: 409, needsReview: true, message: corpo.message });
            }
            // A restored queue can outlive the session token that created it.
            // Refresh authentication headers, preserving its mutation identity.
            var headers = garantirIdMutacao(item.headers);
            // Older queue records may not have an identity. Persist the one
            // generated here before sending so a lost response cannot create a
            // different operation on the next retry.
            item.headers = headers;
            return idbQueueUpdate(item).then(function () {
                return fetchComTimeout(item.url, {
                    method: item.method,
                    headers: headers,
                    body: corpo.body == null ? undefined : corpo.body,
                    credentials: 'same-origin'
                }, REQUEST_TIMEOUT_MS);
            });
        }).then(lerRespostaSincronizacao).then(function (info) {
            return verificarReservaSync().then(function (ok) {
                if (!ok) throw erroInterrompeFila(true, 'A reserva desta sincronização foi assumida por outra aba.');
                return info;
            });
        }).then(function (info) {
            if (info.httpOk && info.semanticOk) {
                return confirmarProjecaoRemota(item, info.text, info.json, summary);
            }
            var status = info.response ? info.response.status : 0;
            var needsReview = (status >= 400 && status < 500) || (info.httpOk && !info.semanticOk);
            return registrarFalha(item, summary, {
                status: status,
                needsReview: needsReview,
                message: pareceTelaLogin(info.text, (info.response && info.response.url) || '')
                    ? 'A sessão expirou antes de confirmar esta alteração. Entre novamente e use “Sincronizar agora”.'
                    : (mensagemResposta(info) || (needsReview ? 'O servidor recusou a alteração.' : 'Erro temporário do servidor.'))
            });
        }).catch(function (err) {
            if (err && err.__sgiSyncHalt) throw err;
            marcarSoftOffline();
            return registrarFalha(item, summary, {
                message: String((err && err.message) || err || 'Erro de rede durante a sincronização.')
            });
        });
    }

    function syncQueue(force) {
        var vazio = { synced: 0, failed: 0, needsReview: 0, pending: state.pending, busy: false };
        if (!state.online || syncing) return Promise.resolve(vazio);
        if (servidorIndisponivel()) {
            return verificarAcesso(false).then(function (ok) {
                if (ok) return syncQueue(force);
                vazio.blocked = true;
                return vazio;
            });
        }
        syncing = true;
        notify();

        function done(v) {
            syncing = false;
            notify();
            return v;
        }

        var summary = { synced: 0, failed: 0, needsReview: 0, pending: 0, busy: false };
        var reserva = false;
        var renovacao = null;
        var reservaPerdida = false;
        var run = adquirirReservaSync().then(function (adquirida) {
            if (!adquirida) {
                summary.busy = true;
                return null;
            }
            reserva = true;
            renovacao = setInterval(function () {
                renovarReservaSync().then(function (ok) {
                    if (!ok) reservaPerdida = true;
                });
            }, Math.floor(COORD_LEASE_MS / 3));
            return idbQueueAll();
        }).then(function (queue) {
            if (!queue) return;
            if (!queue.length) return;
            if (!force && queue.some(function (item) { return item.needsReview; })) return;
            queue = ordenarFila(queue);
            var chain = Promise.resolve();
            queue.forEach(function (item) {
                chain = chain.then(function () {
                    // Uma criação pode resolver referências de uma mutação
                    // dependente enquanto a fila já está em execução. Releia
                    // o item antes de enviá-lo para não usar a cópia antiga
                    // capturada pelo primeiro snapshot da fila.
                    return idbQueueAll().then(function (atualizada) {
                        return (atualizada || []).filter(function (pendente) {
                            return Number(pendente.id) === Number(item.id);
                        })[0] || item;
                    }).then(function (atual) {
                        return processarItemDaFila(atual, summary);
                    });
                });
            });
            return chain;
        });

        return run
            .catch(function (err) {
                if (!(err && err.__sgiSyncHalt) && window.console) {
                    console.warn('sgi: erro inesperado na sincronização:', err);
                }
            })
            .then(function () { return refreshPending(); })
            .then(function () {
                summary.pending = state.pending;
                if (state.retryablePending > 0) scheduleSyncRetry();
                if (renovacao) clearInterval(renovacao);
                if (reservaPerdida) {
                    summary.failed += 1;
                    summary.pending = state.pending;
                    return (reserva ? liberarReservaSync() : Promise.resolve()).then(function () {
                        avisarAbas('queue-changed');
                        return done(summary);
                    });
                }
                return (reserva ? liberarReservaSync() : Promise.resolve()).then(function () {
                    avisarAbas('queue-changed');
                    return done(summary);
                });
            }).catch(function (err) {
                syncing = false;
                notify();
                if (renovacao) clearInterval(renovacao);
                if (reserva) liberarReservaSync();
                if (window.console) console.warn('sgi: não foi possível atualizar o estado da fila:', err);
                return summary;
            });
    }

    /* ------------------------- Wrapper do fetch ------------------------- */
    var originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;

    function wrappedFetch(input, init) {
        if (!originalFetch) return Promise.reject(new Error('fetch indisponivel'));
        init = init || {};
        var url = requestUrl(input);
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();

        // Troca de senha é uma operação de autenticação e nunca pode ficar
        // persistida na fila offline nem receber uma resposta simulada.
        if (isMutation(method) && isPasswordChangeEndpoint(url)) {
            return originalFetch(input, init);
        }

        // O mesmo identificador acompanha a tentativa online e uma eventual
        // entrada posterior na fila. Assim a API pode reconhecer tentativas
        // repetidas sem confundir um reenvio com uma nova ação do mesário.
        if (isMutation(method)) {
            init = Object.assign({}, init);
            init.headers = garantirIdMutacao(init.headers || (input && input.headers));
        }

        if (!isSameOrigin(url)) {
            if (navigator.onLine === false || estaSoftOffline() || servidorIndisponivel()) {
                return Promise.resolve(new Response('', { status: 200, headers: { 'Content-Type': 'text/plain' } }));
            }
            return originalFetch(input, init).catch(function () {
                return new Response('', { status: 200, headers: { 'Content-Type': 'text/plain' } });
            });
        }
        var absUrl = resolveUrl(url);

        if (method === 'GET') {
            // Não tente a rede quando o navegador já informou que está
            // desconectado ou quando a rede está inalcançável (softOffline).
            if (navigator.onLine === false || estaSoftOffline() || servidorIndisponivel()) {
                return idbGet(absUrl).then(function (cached) {
                    if (cached) {
                        return new Response(cached.text, {
                            status: cached.status || 200,
                            headers: { 'Content-Type': cached.contentType || 'application/json' }
                        });
                    }
                    return idbFindUrl(absUrl).then(function (alt) {
                        if (alt) {
                            return new Response(alt.text, {
                                status: alt.status || 200,
                                headers: { 'Content-Type': alt.contentType || 'application/json' }
                            });
                        }
                        throw new Error('Dados não disponíveis offline para esta consulta.');
                    });
                });
            }
            return fetchComTimeout(input, init, REQUEST_TIMEOUT_MS).then(function (res) {
                state.softOffline = false;
                if (res && res.ok) {
                    var clone = res.clone();
                    // O preload só é considerado concluído depois que a
                    // resposta exata da API estiver persistida no IndexedDB.
                    return clone.text().then(function (text) {
                        if (pareceTelaLogin(text, res.url || absUrl)) return res;
                        return idbPut({
                            url: absUrl,
                            text: text,
                            status: res.status,
                            contentType: res.headers.get('content-type') || 'application/json',
                            savedAt: Date.now()
                        }).catch(noop).then(function () { return res; });
                    }).catch(function () { return res; });
                }
                if (res && res.status >= 500) {
                    return idbGet(absUrl).then(function (cached) {
                        if (cached) {
                            return new Response(cached.text, {
                                status: cached.status || 200,
                                headers: { 'Content-Type': cached.contentType || 'application/json' }
                            });
                        }
                        return res;
                    });
                }
                return res;
            }).catch(function (err) {
                marcarSoftOffline();
                return idbGet(absUrl).then(function (cached) {
                    if (cached) {
                        return new Response(cached.text, {
                            status: cached.status || 200,
                            headers: { 'Content-Type': cached.contentType || 'application/json' }
                        });
                    }
                    return idbFindUrl(absUrl).then(function (alt) {
                        if (alt) {
                            return new Response(alt.text, {
                                status: alt.status || 200,
                                headers: { 'Content-Type': alt.contentType || 'application/json' }
                            });
                        }
                        throw err;
                    });
                });
            });
        }

        if (isMutation(method)) {
            var temBodyExplicito = Object.prototype.hasOwnProperty.call(init, 'body');
            var entradaRequest = typeof Request === 'function' && input instanceof Request;
            var bodyInitDisponivel = temBodyExplicito && init.body != null;
            var bodyRequestDisponivel = entradaRequest && input.body !== null;
            var bodyParaFila = bodyInitDisponivel
                ? Promise.resolve(init.body)
                : (bodyRequestDisponivel
                    ? lerCorpoRequestParaFila(input)
                    : Promise.resolve(temBodyExplicito ? init.body : null));
            return bodyParaFila.then(function (body) {
                var idJogoTemporario = false;
                if (typeof body === 'string') {
                    try {
                        var payload = JSON.parse(body);
                        idJogoTemporario = !!payload
                            && typeof payload.id_jogo === 'number'
                            && isFinite(payload.id_jogo)
                            && payload.id_jogo < 0;
                    } catch (e) {}
                }
                var ehNegativo = idJogoTemporario || absUrl.indexOf('id_jogo=-') > -1;
                function enfileirar() {
                    return queueMutation(method, absUrl, body, init.headers).then(function (item) {
                        return fakeResponse({ success: true, offline: true, queued: true, mutation_id: item.id, mensagem: 'Salvo localmente. Sera sincronizado quando houver conexao.' });
                    });
                }
                if (!state.online || navigator.onLine === false || estaSoftOffline() || servidorIndisponivel() || ehNegativo || state.pending > 0 || syncing) {
                    return enfileirar();
                }
                return fetchComTimeout(input, init, REQUEST_TIMEOUT_MS).catch(function () {
                    return enfileirar();
                });
            });
        }

        return originalFetch(input, init);
    }

    if (originalFetch) window.fetch = wrappedFetch;

    /* ------------------------- Wrapper do XMLHttpRequest ------------------------- */
    function patchXHR() {
        var NativeXHR = window.XMLHttpRequest;
        if (!NativeXHR) return;

        window.XMLHttpRequest = function () {
            var xhr = new NativeXHR();
            var _method = 'GET';
            var _url = '';
            var _body = null;
            var _headers = null;

            var open = xhr.open.bind(xhr);
            var send = xhr.send.bind(xhr);
            var realSetHeader = xhr.setRequestHeader.bind(xhr);

            xhr.open = function (method, url) {
                _method = String(method || 'GET').toUpperCase();
                _url = url;
                _headers = null;
                return open.apply(xhr, arguments);
            };

            xhr.setRequestHeader = function (k, v) {
                _headers = _headers || {};
                _headers[k] = v;
                return realSetHeader(k, v);
            };

            xhr.send = function (body) {
                _body = body;
                if (!isSameOrigin(_url)) return send.call(xhr, body);

                var absUrl = resolveUrl(_url);

                // Mesmo que outro cliente escolha XHR, senha não é mutação
                // sincronizável nem pode ter confirmação sintética.
                if (isMutation(_method) && isPasswordChangeEndpoint(absUrl)) {
                    return send.call(xhr, body);
                }

                if (_method === 'GET') {
                    var h = function () {
                        if (xhr.readyState === 4) {
                            xhr.removeEventListener('readystatechange', h);
                            if (xhr.status >= 200 && xhr.status < 300 && xhr.responseText) {
                                idbPut({
                                    url: absUrl,
                                    text: xhr.responseText,
                                    status: xhr.status,
                                    contentType: xhr.getResponseHeader('content-type') || 'application/json',
                                    savedAt: Date.now()
                                }).catch(noop);
                            }
                        }
                    };
                    xhr.addEventListener('readystatechange', h);
                    return send.call(xhr, body);
                }

                if (isMutation(_method) && (!state.online || state.pending > 0 || syncing || servidorIndisponivel())) {
                    queueMutation(_method, absUrl, _body, _headers).then(function () {
                        fakeXHRResponse(xhr);
                    });
                    return undefined;
                }

                return send.call(xhr, body);
            };

            return xhr;
        };

        window.XMLHttpRequest.prototype = NativeXHR.prototype;
        ['DONE', 'HEADERS_RECEIVED', 'LOADING', 'OPENED', 'UNSENT'].forEach(function (k) {
            if (k in NativeXHR) window.XMLHttpRequest[k] = NativeXHR[k];
        });
    }

    function fakeXHRResponse(xhr) {
        var payload = JSON.stringify({ success: true, offline: true, queued: true, mensagem: 'Salvo localmente. Sera sincronizado quando houver conexao.' });
        try {
            Object.defineProperty(xhr, 'readyState', { configurable: true, get: function () { return 4; } });
            Object.defineProperty(xhr, 'status', { configurable: true, get: function () { return 200; } });
            Object.defineProperty(xhr, 'responseText', { configurable: true, get: function () { return payload; } });
            Object.defineProperty(xhr, 'response', { configurable: true, get: function () { return payload; } });
            Object.defineProperty(xhr, 'statusText', { configurable: true, get: function () { return 'OK'; } });
            xhr.getResponseHeader = function () { return 'application/json'; };
            xhr.getAllResponseHeaders = function () { return 'Content-Type: application/json\r\n'; };
        } catch (e) { /* noop */ }

        setTimeout(function () {
            var evt = { target: xhr };
            if (typeof xhr.onreadystatechange === 'function') xhr.onreadystatechange.call(xhr, evt);
            if (typeof xhr.onload === 'function') xhr.onload.call(xhr, evt);
            if (typeof xhr.onloadend === 'function') xhr.onloadend.call(xhr, evt);
        }, 0);
    }

    /* ------------------------- Axios -> fetch ------------------------- */
    function patchAxios() {
        var axios = window.axios;
        if (!axios || !axios.defaults) return;

        axios.defaults.adapter = function (config) {
            var method = (config.method || 'get').toUpperCase();
            var url;
            try {
                url = config.baseURL ? new URL(config.url, config.baseURL).href : resolveUrl(config.url);
            } catch (e) {
                url = resolveUrl(config.url);
            }

            var headers = {};
            var rawHeaders = config.headers || {};
            if (typeof rawHeaders.forEach === 'function') {
                rawHeaders.forEach(function (v, k) { headers[k] = v; });
            } else if (typeof rawHeaders === 'object') {
                for (var k in rawHeaders) headers[k] = rawHeaders[k];
            }

            var data = config.data;
            var body;
            if (data == null) {
                body = undefined;
            } else if (typeof FormData !== 'undefined' && data instanceof FormData) {
                body = data;
                delete headers['Content-Type'];
            } else if (typeof data === 'string') {
                body = data;
            } else if (data instanceof URLSearchParams) {
                body = data;
            } else {
                body = JSON.stringify(data);
                if (!headers['Content-Type'] && !headers['content-type']) {
                    headers['Content-Type'] = 'application/json';
                }
            }

            return wrappedFetch(url, {
                method: method,
                headers: headers,
                body: body,
                credentials: config.withCredentials ? 'include' : 'same-origin'
            }).then(function (res) {
                return res.text().then(function (text) {
                    var parsed = null;
                    try { parsed = text ? JSON.parse(text) : {}; } catch (e) { parsed = text; }
                    return {
                        data: parsed,
                        status: res.status,
                        statusText: res.statusText,
                        headers: {},
                        config: config,
                        request: res
                    };
                });
            });
        };
    }

    /* ------------------------- Banner de status ------------------------- */
    function createBanner() {
        if (document.getElementById('sgi-offline-banner')) return;
        var b = document.createElement('div');
        b.id = 'sgi-offline-banner';
        b.className = 'sgi-offline-banner bg-danger text-white d-none sgi-hidden';
        b.innerHTML =
            '<div class="container-fluid d-flex align-items-center justify-content-center gap-2 py-2 px-3 flex-wrap text-center">' +
            '<span class="sgi-offline-banner-status d-inline-flex align-items-center gap-2" role="status" aria-live="polite" aria-atomic="true">' +
            '<span class="sgi-offline-banner-tag badge rounded-pill text-bg-light small fw-bold">SEM CONEXÃO</span>' +
            '<span class="sgi-offline-banner-text small"></span>' +
            '</span>' +
            '<div class="sgi-offline-banner-actions d-flex align-items-center justify-content-center gap-2 flex-wrap">' +
            '<button type="button" class="sgi-offline-banner-btn btn btn-sm btn-outline-light rounded-pill fw-semibold d-none sgi-hidden">Sincronizar agora</button>' +
            '<button type="button" class="sgi-offline-banner-details-toggle btn btn-sm btn-outline-light rounded-pill fw-semibold" aria-controls="sgi-offline-banner-details" aria-expanded="false">Ver pendências</button>' +
            '<div id="sgi-offline-banner-details" class="sgi-offline-banner-details d-flex align-items-center justify-content-center gap-2 flex-wrap">' +
            '<button id="sgi-offline-banner-export" type="button" class="sgi-offline-banner-export btn btn-sm btn-outline-light rounded-pill fw-semibold d-none sgi-hidden">Exportar pendências</button>' +
            '<button id="sgi-offline-banner-import" type="button" class="sgi-offline-banner-import btn btn-sm btn-outline-light rounded-pill fw-semibold">Importar pendências</button>' +
            '<input type="file" class="sgi-offline-banner-file d-none sgi-hidden" accept="application/json,.json">' +
            '</div>' +
            '</div>' +
            '</div>';
        document.body.appendChild(b);

        function atualizarAlturaBanner() {
            var altura = b.getBoundingClientRect().height;
            document.documentElement.style.setProperty('--sgi-offline-banner-height', altura + 'px');
        }
        atualizarAlturaBanner();
        if (typeof ResizeObserver === 'function') {
            var observer = new ResizeObserver(atualizarAlturaBanner);
            observer.observe(b);
        }

        var btn = b.querySelector('.sgi-offline-banner-btn');
        var detailsToggle = b.querySelector('.sgi-offline-banner-details-toggle');
        if (detailsToggle) {
            detailsToggle.addEventListener('click', function () {
                var expanded = b.classList.toggle('sgi-offline-banner--details-open');
                detailsToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }
        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                var sessaoExpirada = state.session === 'expirada' || state.server === 'sessao';
                if (sessaoExpirada) {
                    window.location.assign(prepararRetornoAutenticacao());
                    return;
                }
                btn.disabled = true;
                window.SGIOffline.syncNow().then(function () {
                    btn.disabled = false;
                }).catch(function () {
                    btn.disabled = false;
                });
            });
        }
        var exportBtn = b.querySelector('.sgi-offline-banner-export');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                exportBtn.disabled = true;
                downloadPending().then(function () {
                    exportBtn.disabled = false;
                    notificarUsuario('Arquivo de pendências exportado.');
                }).catch(function (err) {
                    exportBtn.disabled = false;
                    notificarUsuario((err && err.message) || 'Não foi possível exportar as pendências.');
                });
            });
        }
        var importBtn = b.querySelector('.sgi-offline-banner-import');
        var importFile = b.querySelector('.sgi-offline-banner-file');
        if (importBtn && importFile) {
            importBtn.addEventListener('click', function () { importFile.click(); });
            importFile.addEventListener('change', function () {
                var file = importFile.files && importFile.files[0];
                if (!file) return;
                importBtn.disabled = true;
                window.SGIOffline.importPending(file).then(function (result) {
                    notificarUsuario(result.imported
                        ? result.imported + ' pendência(s) importada(s).'
                        : 'Nenhuma pendência nova foi importada.');
                }).catch(function (err) {
                    notificarUsuario((err && err.message) || 'Não foi possível importar as pendências.');
                }).then(function () {
                    importBtn.disabled = false;
                    importFile.value = '';
                });
            });
        }
    }

    function ensureBanner() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { createBanner(); notify(); });
        } else {
            createBanner();
            notify();
        }
    }

    function notificarUsuario(msg) {
        var el = document.getElementById('sgi-aviso');
        if (!el) {
            if (window.console && console.info) console.info(msg);
            return;
        }
        el.textContent = msg;
        el.style.opacity = '1';
        setTimeout(function () { el.style.opacity = '0'; }, 4200);
    }

    function updateBanner() {
        var b = document.getElementById('sgi-offline-banner');
        if (!b) return;
        var tag = b.querySelector('.sgi-offline-banner-tag');
        var text = b.querySelector('.sgi-offline-banner-text');
        var btn = b.querySelector('.sgi-offline-banner-btn');
        var exportBtn = b.querySelector('.sgi-offline-banner-export');
        var detailsToggle = b.querySelector('.sgi-offline-banner-details-toggle');

        var sessaoExpirada = state.session === 'expirada' || state.server === 'sessao';
        var servidorOffline = state.server === 'indisponivel' || state.softOffline;
        var revisao = state.needsReview > 0;
        var offline = !state.online;
        var enviando = syncing;
        var visivel = offline || sessaoExpirada || servidorOffline || state.pending > 0;
        function definirTexto(elemento, valor) {
            if (elemento && elemento.textContent !== valor) elemento.textContent = valor;
        }

        if (!visivel) {
            b.classList.add('d-none', 'sgi-hidden');
            document.body.classList.remove('sgi-offline-active');
            return;
        }

        b.classList.remove('d-none', 'sgi-hidden');
        document.body.classList.add('sgi-offline-active');

        var tomEscuro = !offline && !sessaoExpirada && !servidorOffline;
        b.classList.toggle('bg-warning', tomEscuro);
        b.classList.toggle('text-dark', tomEscuro);
        b.classList.toggle('bg-danger', !tomEscuro);
        b.classList.toggle('text-white', !tomEscuro);

        if (detailsToggle) {
            var detalhesAbertos = b.classList.contains('sgi-offline-banner--details-open');
            definirTexto(detailsToggle, detalhesAbertos
                ? 'Ocultar pendências'
                : (state.pending > 0 ? 'Ver pendências' : 'Mais ações offline'));
            detailsToggle.setAttribute('aria-expanded', detalhesAbertos ? 'true' : 'false');
            detailsToggle.classList.toggle('btn-outline-dark', tomEscuro);
            detailsToggle.classList.toggle('btn-outline-light', !tomEscuro);
        }

        if (tag && text) {
            if (sessaoExpirada) {
                definirTexto(tag, 'SESSÃO EXPIRADA');
                definirTexto(text, 'A sessão do operador expirou. As pendências permanecem salvas neste dispositivo. Entre novamente com o mesmo usuário para retomar o envio.');
            } else if (offline) {
                definirTexto(tag, revisao ? 'SEM CONEXÃO · REVISÃO' : 'SEM CONEXÃO');
                definirTexto(text, revisao
                    ? 'Sem conexão com a rede local. Há alteração que exige revisão antes de reenviar; exporte as pendências, que permanecem salvas neste dispositivo.'
                    : 'Sem conexão com a rede local. ' + (state.pending > 0
                        ? state.pending + (state.pending === 1 ? ' alteração pendente.' : ' alterações pendentes.')
                        : 'Os dados preparados nesta aba continuam disponíveis.'));
            } else if (servidorOffline) {
                definirTexto(tag, revisao ? 'SERVIDOR INDISPONÍVEL · REVISÃO' : 'SERVIDOR INDISPONÍVEL');
                definirTexto(text, revisao
                    ? 'O servidor local está indisponível. Há alteração que exige revisão antes de reenviar; exporte as pendências, que permanecem neste dispositivo.'
                    : 'O servidor local está indisponível. As pendências permanecem salvas neste dispositivo.');
            } else if (enviando) {
                definirTexto(tag, 'SINCRONIZANDO');
                definirTexto(text, 'Enviando alterações ao servidor.');
            } else if (revisao) {
                definirTexto(tag, 'REVISÃO NECESSÁRIA');
                definirTexto(text, (state.lastSyncError || 'Há alteração que precisa de revisão.') + ' Exporte ou revise antes de tentar novamente.');
            } else {
                definirTexto(tag, 'PENDÊNCIAS');
                definirTexto(text, state.pending === 1
                    ? '1 alteração pendente, aguardando envio.'
                    : state.pending + ' alterações pendentes, aguardando envio.');
            }
        }

        if (btn) {
            btn.classList.toggle('btn-outline-dark', tomEscuro);
            btn.classList.toggle('btn-outline-light', !tomEscuro);
            definirTexto(btn, enviando
                ? 'Enviando…'
                : (sessaoExpirada ? 'Atualizar acesso' : (revisao ? 'Tentar novamente' : 'Sincronizar agora')));
            btn.disabled = enviando;
            var syncDisponivel = state.pending > 0;
            btn.classList.toggle('d-none', !syncDisponivel);
            btn.classList.toggle('sgi-hidden', !syncDisponivel);
        }
        if (exportBtn) {
            var exportDisponivel = state.pending > 0;
            exportBtn.classList.toggle('btn-outline-dark', tomEscuro);
            exportBtn.classList.toggle('btn-outline-light', !tomEscuro);
            exportBtn.classList.toggle('d-none', !exportDisponivel);
            exportBtn.classList.toggle('sgi-hidden', !exportDisponivel);
        }
        var importBtn = b.querySelector('.sgi-offline-banner-import');
        if (importBtn) {
            importBtn.classList.toggle('btn-outline-dark', tomEscuro);
            importBtn.classList.toggle('btn-outline-light', !tomEscuro);
        }
    }

    /* ------------------------- Envio de formulario (uso opcional) ------------------------- */
    function submit(url, method, payload) {
        var absUrl = resolveUrl(url);
        var m = (method || 'POST').toUpperCase();
        var body = JSON.stringify(payload || {});
        var headers = garantirIdMutacao({ 'Content-Type': 'application/json' });
        return wrappedFetch(absUrl, {
            method: m,
            headers: headers,
            body: body
        }).then(function (res) {
            return res.json().catch(function () { return {}; });
        });
    }

    /* ------------------------- Eventos ------------------------- */
    window.addEventListener('online', function () {
        state.online = true;
        notify();
        verificarAcesso(true).then(function (ok) {
            if (ok) syncQueue();
        });
    });
    window.addEventListener('offline', function () {
        state.online = false;
        state.server = 'indisponivel';
        state.serverError = 'O dispositivo está sem conexão com a rede local.';
        notify();
    });
    window.addEventListener('focus', function () {
        if (navigator.onLine) verificarAcesso(true).then(function (ok) {
            if (ok && state.pending > 0) syncQueue();
        });
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && navigator.onLine) verificarAcesso(true).then(function (ok) {
            if (ok && state.pending > 0) syncQueue();
        });
    });

    /* ------------------------- API publica ------------------------- */
    window.SGIOffline = {
        isOnline: function () { return state.online; },
        getState: stateSnapshot,
        hasPending: function () { return state.pending > 0; },
        onStateChange: function (cb) {
            listeners.push(cb);
            return function () {
                var i = listeners.indexOf(cb);
                if (i > -1) listeners.splice(i, 1);
            };
        },
        // A ação explícita do usuário pode tentar novamente uma entrada que
        // ficou em revisão; o envio automático nunca a descarta silenciosamente.
        syncNow: function () {
            return verificarAcesso(true).then(function (ok) {
                if (ok) return syncQueue(true);
                return {
                    synced: 0,
                    failed: 0,
                    needsReview: 0,
                    pending: state.pending,
                    busy: false,
                    blocked: true
                };
            });
        },
        // O cliente pode solicitar o envio automático depois de persistir uma
        // mutação, sem ignorar entradas que exigem revisão humana.
        sync: function () { return syncQueue(false); },
        queueMutation: queueMutation,
        updatePending: function (item) {
            return idbQueueUpdate(item).then(function () { return refreshPending(); }).then(function () {
                notify();
                return item;
            });
        },
        submit: submit,
        getCached: idbGet,
        getPendingList: function () { return idbQueueAll(); },
        checkServer: function (force) { return verificarServidor(force !== false); },
        checkSession: function (force) { return verificarSessao(force !== false); },
        checkAccess: function (force) { return verificarAcesso(force !== false); },
        getTabId: function () { return TAB_ID; },
        exportPending: exportPending,
        downloadPending: downloadPending,
        importPending: importarPendencias
    };

    /* ------------------------- Inicializacao ------------------------- */
    patchXHR();
    patchAxios();

    ensureBanner();

    openDB().then(function () {
        return refreshPending();
    }).then(function () {
        notify();
        if (state.online && state.pending > 0) {
            verificarAcesso(true).then(function (ok) {
                if (ok) syncQueue();
            });
        }
    }).catch(function (e) {
        if (window.console) console.warn('SGI Offline: IndexedDB indisponivel.', e);
    });
})();
