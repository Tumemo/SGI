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

    // Cache GET separado por usuário autenticado: a chave opaca é derivada no
    // servidor e não expõe o PHPSESSID nem reutiliza o ID fixo diretamente.
    // Ela permanece estável no relogin do mesmo operador para recuperar a
    // fila pendente, mas muda entre usuários e na troca de senha.
    var SESSION_KEY = (typeof window !== 'undefined' && window.SGI_CACHE_KEY)
        ? String(window.SGI_CACHE_KEY) : 'anon';

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
        if (!localizarCabecalho(headers, 'X-SGI-Mutation-Id')) {
            headers['X-SGI-Mutation-Id'] = SESSION_KEY + '-' + Date.now().toString(36) + '-' +
                Math.random().toString(36).slice(2, 10);
        }
        return headers;
    }

    function pareceTelaLogin(texto, url) {
        var textoNormalizado = String(texto || '').toLowerCase();
        var urlNormalizada = String(url || '').toLowerCase();
        return urlNormalizada.indexOf('/views/index.php') > -1 ||
            textoNormalizado.indexOf('id="form_mobile"') > -1 ||
            textoNormalizado.indexOf('id="form_desktop"') > -1 ||
            textoNormalizado.indexOf('class="ipt-matricula"') > -1 ||
            textoNormalizado.indexOf('sgi - login') > -1;
    }

    var dbPromise = null;
    var state = {
        online: navigator.onLine !== false,
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

    function noop() {}

    function marcarSoftOffline() {
        state.softOffline = true;
        state.softOfflineUntil = Date.now() + 15000;
    }

    function estaSoftOffline() {
        if (state.softOffline && Date.now() > state.softOfflineUntil) {
            state.softOffline = false;
        }
        return state.softOffline;
    }

    function resolveUrl(url) {
        try { return new URL(url, window.location.href).href; } catch (e) { return String(url); }
    }

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) { return false; }
    }

    function isMutation(method) {
        return method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE';
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
        item.session = SESSION_KEY;
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_QUEUE, 'readwrite');
                var req = tx.objectStore(STORE_QUEUE).add(item);
                req.onsuccess = function () { resolve(req.result); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function idbQueueAll() {
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE_QUEUE, 'readonly');
                var req = tx.objectStore(STORE_QUEUE).getAll();
                req.onsuccess = function () {
                    var todos = req.result || [];
                    resolve(todos.filter(function (i) {
                        return (i.session || 'anon') === SESSION_KEY;
                    }));
                };
                req.onerror = function () { reject(req.error); };
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
        return idbQueueAdd(item).then(function (id) {
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

    function fileFromUrl(url) {
        try { return new URL(url, window.location.href).pathname.split('/').pop(); }
        catch (e) { return String(url || '').split('?')[0].split('/').pop(); }
    }

    // Resultados de uma partida derivada materializam o jogo temporário. Gols,
    // cartões e demais registros desse mesmo jogo precisam ser enviados depois
    // para que a API consiga resolver o ID definitivo.
    function tempGameKey(item) {
        var file = fileFromUrl(item.url);
        var data = bodyAsJson(item);
        var id = null;
        if (file === 'lancar_resultado.php') id = data.id_jogo;
        else if (file === 'artilheiro.php') id = data.jogos_id_jogo;
        else if (file === 'ocorrencias.php') id = data.id_jogo;
        else if (file === 'partidas.php') id = data.jogos_id_jogo;
        if (Number(id) >= 0 || id == null) return null;
        return String(data.id_modalidade || '') + '|' + String(id);
    }

    function materializaJogoTemporario(item) {
        return fileFromUrl(item.url) === 'lancar_resultado.php' && Number(bodyAsJson(item).id_jogo) < 0;
    }

    function ordenarFila(queue) {
        return (queue || []).slice().sort(function (a, b) {
            var ka = tempGameKey(a);
            var kb = tempGameKey(b);
            if (ka && ka === kb) {
                var aMaterializa = materializaJogoTemporario(a);
                var bMaterializa = materializaJogoTemporario(b);
                if (aMaterializa !== bMaterializa) return aMaterializa ? -1 : 1;
            }
            var criadoA = Number(a.createdAt) || 0;
            var criadoB = Number(b.createdAt) || 0;
            if (criadoA !== criadoB) return criadoA - criadoB;
            return (Number(a.id) || 0) - (Number(b.id) || 0);
        });
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
                // Uma API que devolve a página de login após redirecionamento
                // também não confirmou a alteração, mesmo que o HTTP seja 200.
                semanticOk: !redirecionouParaLogin && (!json || json.success !== false)
            };
        });
    }

    function mensagemResposta(info) {
        if (info && info.json) {
            return info.json.message || info.json.mensagem || info.json.error || '';
        }
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
        return Promise.resolve(projection).then(function () {
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
        if (item.remoteCommitted) {
            return concluirConfirmacaoRemotaPendente(item, summary);
        }
        return prepararProjecaoPendente(item).then(function () {
            var headers = {};
            for (var k in (item.headers || {})) headers[k] = item.headers[k];
            return originalFetch(item.url, {
                method: item.method,
                headers: headers,
                body: item.body == null ? undefined : item.body,
                credentials: 'same-origin'
            });
        }).then(lerRespostaSincronizacao).then(function (info) {
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
        var vazio = { synced: 0, failed: 0, needsReview: 0, pending: state.pending };
        if (!state.online || syncing) return Promise.resolve(vazio);
        syncing = true;

        function done(v) { syncing = false; return v; }

        var summary = { synced: 0, failed: 0, needsReview: 0, pending: 0 };
        var run = idbQueueAll().then(function (queue) {
            if (!queue.length) return;
            if (!force && queue.some(function (item) { return item.needsReview; })) return;
            queue = ordenarFila(queue);
            var chain = Promise.resolve();
            queue.forEach(function (item) {
                chain = chain.then(function () {
                    return processarItemDaFila(item, summary);
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
                notify();
                return done(summary);
            }).catch(function (err) {
                syncing = false;
                if (window.console) console.warn('sgi: não foi possível atualizar o estado da fila:', err);
                return summary;
            });
    }

    /* ------------------------- Wrapper do fetch ------------------------- */
    var originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;

    function wrappedFetch(input, init) {
        if (!originalFetch) return Promise.reject(new Error('fetch indisponivel'));
        init = init || {};
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();

        // O mesmo identificador acompanha a tentativa online e uma eventual
        // entrada posterior na fila. Assim a API pode reconhecer tentativas
        // repetidas sem confundir um reenvio com uma nova ação do mesário.
        if (isMutation(method)) {
            init = Object.assign({}, init);
            init.headers = garantirIdMutacao(init.headers || (input && input.headers));
        }

        if (!isSameOrigin(url)) {
            if (navigator.onLine === false || estaSoftOffline()) {
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
            if (navigator.onLine === false || estaSoftOffline()) {
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
            return originalFetch(input, init).then(function (res) {
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
            var bodyStr = init.body ? String(init.body) : '';
            var ehNegativo = bodyStr.indexOf('"id_jogo":-') > -1 || absUrl.indexOf('id_jogo=-') > -1;
            if (!state.online || ehNegativo) {
                return queueMutation(method, absUrl, init.body, init.headers).then(function () {
                    return fakeResponse({ success: true, offline: true, queued: true, mensagem: 'Salvo localmente. Sera sincronizado quando houver conexao.' });
                });
            }
            return originalFetch(input, init).catch(function () {
                return queueMutation(method, absUrl, init.body, init.headers).then(function () {
                    return fakeResponse({ success: true, offline: true, queued: true, mensagem: 'Salvo localmente. Sera sincronizado quando houver conexao.' });
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

                if (isMutation(_method) && !state.online) {
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
        b.className = 'sgi-offline-banner sgi-hidden';
        b.innerHTML =
            '<div class="sgi-offline-banner-inner">' +
            '<span class="sgi-offline-banner-tag">OFFLINE</span>' +
            '<span class="sgi-offline-banner-text"></span>' +
            '<button type="button" class="sgi-offline-banner-btn sgi-hidden">Sincronizar agora</button>' +
            '</div>';
        document.body.appendChild(b);

        var btn = b.querySelector('.sgi-offline-banner-btn');
        if (btn) {
            btn.addEventListener('click', function () {
                btn.disabled = true;
                window.SGIOffline.syncNow().then(function () {
                    btn.disabled = false;
                }).catch(function () {
                    btn.disabled = false;
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

    function updateBanner() {
        var b = document.getElementById('sgi-offline-banner');
        if (!b) return;
        var tag = b.querySelector('.sgi-offline-banner-tag');
        var text = b.querySelector('.sgi-offline-banner-text');
        var btn = b.querySelector('.sgi-offline-banner-btn');

        if (state.online && state.pending === 0) {
            b.classList.add('sgi-hidden');
            document.body.classList.remove('sgi-offline-active');
            return;
        }

        b.classList.remove('sgi-hidden');
        document.body.classList.add('sgi-offline-active');

        if (state.online) {
            b.classList.remove('sgi-offline-banner--offline');
            b.classList.add('sgi-offline-banner--syncing');
            if (state.needsReview > 0) {
                if (tag) tag.textContent = 'REVISAR';
                if (text) text.textContent = state.lastSyncError ||
                    'Há alteração(ões) que precisam ser confirmadas antes de reenviar.';
            } else {
                if (tag) tag.textContent = 'SINCRONIZANDO';
                if (text) text.textContent = state.pending === 1
                    ? '1 alteração aguardando envio.'
                    : state.pending + ' alterações aguardando envio.';
            }
            if (btn) btn.classList.remove('sgi-hidden');
        } else {
            b.classList.add('sgi-offline-banner--offline');
            b.classList.remove('sgi-offline-banner--syncing');
            if (tag) tag.textContent = 'OFFLINE';
            if (text) {
                text.textContent = 'Modo offline — os dados podem estar desatualizados.' +
                    (state.pending > 0 ? ' ' + state.pending + ' alteracao(oes) aguardando envio.' : '');
            }
            if (btn) btn.classList.toggle('sgi-hidden', state.pending === 0);
        }
    }

    /* ------------------------- Envio de formulario (uso opcional) ------------------------- */
    function submit(url, method, payload) {
        var absUrl = resolveUrl(url);
        var m = (method || 'POST').toUpperCase();
        var body = JSON.stringify(payload || {});
        if (!state.online) {
            return queueMutation(m, absUrl, body, { 'Content-Type': 'application/json' }).then(function () {
                return { success: true, offline: true, queued: true };
            });
        }
        return originalFetch(absUrl, {
            method: m,
            headers: { 'Content-Type': 'application/json' },
            body: body
        }).then(function (res) {
            return res.json().catch(function () { return {}; });
        }).catch(function () {
            return queueMutation(m, absUrl, body, { 'Content-Type': 'application/json' }).then(function () {
                return { success: true, offline: true, queued: true };
            });
        });
    }

    /* ------------------------- Eventos ------------------------- */
    window.addEventListener('online', function () {
        state.online = true;
        state.softOffline = false;
        notify();
        syncQueue();
    });
    window.addEventListener('offline', function () {
        state.online = false;
        notify();
    });
    window.addEventListener('focus', function () {
        if (navigator.onLine && state.pending > 0) syncQueue();
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && navigator.onLine && state.pending > 0) syncQueue();
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
        syncNow: function () { return syncQueue(true); },
        queueMutation: queueMutation,
        submit: submit,
        getCached: idbGet,
        getPendingList: function () { return idbQueueAll(); }
    };

    /* ------------------------- Inicializacao ------------------------- */
    patchXHR();
    patchAxios();

    ensureBanner();

    openDB().then(function () {
        return refreshPending();
    }).then(function () {
        notify();
        if (state.online && state.pending > 0) syncQueue();
    }).catch(function (e) {
        if (window.console) console.warn('SGI Offline: IndexedDB indisponivel.', e);
    });
})();
