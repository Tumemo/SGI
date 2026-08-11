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

    // Cache GET separado por sessão: evita que dados de um admin (ou outro
    // usuário) vazem para quem usar o mesmo navegador depois. A página expõe
    // window.SGI_SESSION_ID (definido no head.php); 'anon' para quem não tem.
    var SESSION_KEY = (typeof window !== 'undefined' && window.SGI_SESSION_ID)
        ? String(window.SGI_SESSION_ID) : 'anon';

    function cacheKey(url) {
        return SESSION_KEY + '|' + url;
    }

    var dbPromise = null;
    var state = { online: navigator.onLine !== false, pending: 0 };
    var listeners = [];

    function noop() {}

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
    function notify() {
        updateBanner();
        for (var i = 0; i < listeners.length; i++) {
            try { listeners[i]({ online: state.online, pending: state.pending }); } catch (e) { /* noop */ }
        }
    }

    function refreshPending() {
        return idbQueueAll().then(function (q) { state.pending = q.length; });
    }

    /* ------------------------- Fila de mutacoes ------------------------- */
    function queueMutation(method, url, body, headers) {
        var storedHeaders = {};
        if (headers) {
            if (typeof headers.forEach === 'function') {
                headers.forEach(function (v, k) { storedHeaders[k] = v; });
            } else if (typeof headers === 'object') {
                for (var k in headers) storedHeaders[k] = headers[k];
            }
        }

        var isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
        var storedBody = body;
        if (body == null) {
            storedBody = null;
        } else if (typeof body === 'string') {
            storedBody = body;
        } else if (isFormData) {
            storedBody = body;
            delete storedHeaders['Content-Type'];
        } else if (body instanceof URLSearchParams || body instanceof Blob || body instanceof ArrayBuffer || ArrayBuffer.isView(body)) {
            storedBody = body;
        } else {
            storedBody = JSON.stringify(body);
        }

        return idbQueueAdd({
            method: method,
            url: url,
            body: storedBody,
            headers: storedHeaders,
            createdAt: Date.now(),
            tries: 0,
            needsReview: false
        }).then(function () {
            state.pending += 1;
            notify();
        });
    }

    /* ------------------------- Sincronizacao ------------------------- */
    var syncing = false;

    function syncQueue() {
        if (!state.online || syncing) return Promise.resolve(0);
        syncing = true;

        function done(v) { syncing = false; return v; }

        var run = idbQueueAll().then(function (queue) {
            if (!queue.length) return;
            var chain = Promise.resolve();
            queue.forEach(function (item) {
                chain = chain.then(function () {
                    var headers = {};
                    for (var k in (item.headers || {})) headers[k] = item.headers[k];
                    return originalFetch(item.url, {
                        method: item.method,
                        headers: headers,
                        body: item.body == null ? undefined : item.body
                    });
                }).then(function (res) {
                    if (res && res.ok) return idbQueueDelete(item.id);
                    item.tries = (item.tries || 0) + 1;
                    if (item.tries >= MAX_TRIES) item.needsReview = true;
                    return idbQueueUpdate(item);
                }).catch(function () {
                    item.tries = (item.tries || 0) + 1;
                    if (item.tries >= MAX_TRIES) item.needsReview = true;
                    return idbQueueUpdate(item).then(function () {
                        throw new Error('sgi: sync interrompido');
                    });
                });
            });
            return chain;
        });

        return run
            .then(function () { return refreshPending(); })
            .catch(function () { return refreshPending(); })
            .then(function () { notify(); return done(0); });
    }

    /* ------------------------- Wrapper do fetch ------------------------- */
    var originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;

    function wrappedFetch(input, init) {
        if (!originalFetch) return Promise.reject(new Error('fetch indisponivel'));
        init = init || {};
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();

        if (!isSameOrigin(url)) return originalFetch(input, init);
        var absUrl = resolveUrl(url);

        if (method === 'GET') {
            return originalFetch(input, init).then(function (res) {
                if (res && res.ok) {
                    var clone = res.clone();
                    clone.text().then(function (text) {
                        idbPut({
                            url: absUrl,
                            text: text,
                            status: res.status,
                            contentType: res.headers.get('content-type') || 'application/json',
                            savedAt: Date.now()
                        }).catch(noop);
                    }).catch(noop);
                    return res;
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
                return idbGet(absUrl).then(function (cached) {
                    if (cached) {
                        return new Response(cached.text, {
                            status: cached.status || 200,
                            headers: { 'Content-Type': cached.contentType || 'application/json' }
                        });
                    }
                    throw err;
                });
            });
        }

        if (isMutation(method)) {
            if (!state.online) {
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
            if (tag) tag.textContent = 'SINCRONIZANDO';
            if (text) text.textContent = state.pending === 1
                ? '1 alteracao aguardando envio.'
                : state.pending + ' alteracoes aguardando envio.';
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
        getState: function () { return { online: state.online, pending: state.pending }; },
        hasPending: function () { return state.pending > 0; },
        onStateChange: function (cb) {
            listeners.push(cb);
            return function () {
                var i = listeners.indexOf(cb);
                if (i > -1) listeners.splice(i, 1);
            };
        },
        syncNow: function () { return syncQueue(); },
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
