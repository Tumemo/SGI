/* Cliente HTTP comum: acrescenta o token CSRF em mutações same-origin.
 * Deve ser carregado depois do offline-core para que a fila IndexedDB receba
 * o cabeçalho junto com a mutação enfileirada. */
(function () {
    'use strict';

    var token = String(window.SGI_CSRF_TOKEN || '');
    var basePath = String(window.SGI_BASE_PATH || '').replace(/\/+$/, '');

    // Todas as chamadas canônicas continuam usando /api/v1/... no código da
    // tela. Quando a instalação vive em um subdiretório, o prefixo é aplicado
    // aqui, no cliente HTTP compartilhado, antes de sair do navegador.
    function resolveUrl(url) {
        if (typeof url !== 'string' || !basePath || url.indexOf('//') === 0) return url;
        if (url === '/api' || url.indexOf('/api/') === 0) return basePath + url;
        return url;
    }

    function isMutation(method) {
        method = String(method || 'GET').toUpperCase();
        return method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE';
    }

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    }

    var nativeFetch = window.fetch;
    if (typeof nativeFetch === 'function') {
        window.fetch = function (input, init) {
            var request = input instanceof Request ? input : null;
            var method = (init && init.method) || (request && request.method) || 'GET';
            var sourceUrl = request ? request.url : input;
            var resolved = resolveUrl(sourceUrl);
            var normalizedInput = input;
            if (resolved !== sourceUrl) {
                normalizedInput = request ? new Request(resolved, request) : resolved;
            }
            var url = normalizedInput instanceof Request ? normalizedInput.url : normalizedInput;
            if (!isMutation(method) || !isSameOrigin(url)) {
                return nativeFetch.call(this, normalizedInput, init);
            }

            var normalizedRequest = normalizedInput instanceof Request ? normalizedInput : request;
            var headers = new Headers(normalizedRequest ? normalizedRequest.headers : undefined);
            if (init && init.headers) {
                new Headers(init.headers).forEach(function (value, name) { headers.set(name, value); });
            }
            if (!headers.has('X-SGI-CSRF')) headers.set('X-SGI-CSRF', token);

            var options = Object.assign({}, init || {}, { headers: headers });
            return nativeFetch.call(this, normalizedInput, options);
        };
    }

    // Alguns formulários antigos ainda usam XMLHttpRequest diretamente.
    // Mantemos a mesma garantia de CSRF sem substituir o adaptador offline já
    // instalado pelo offline-core.
    var NativeXHR = window.XMLHttpRequest;
    if (typeof NativeXHR === 'function') {
        window.XMLHttpRequest = function () {
            var xhr = new NativeXHR();
            var method = 'GET';
            var url = '';
            var csrfHeaderSet = false;
            var nativeOpen = xhr.open.bind(xhr);
            var nativeSetHeader = xhr.setRequestHeader.bind(xhr);
            var nativeSend = xhr.send.bind(xhr);

            xhr.open = function (requestMethod, requestUrl) {
                method = String(requestMethod || 'GET').toUpperCase();
                url = resolveUrl(String(requestUrl || ''));
                csrfHeaderSet = false;
                var args = Array.prototype.slice.call(arguments);
                args[1] = url;
                return nativeOpen.apply(xhr, args);
            };
            xhr.setRequestHeader = function (name, value) {
                if (String(name).toLowerCase() === 'x-sgi-csrf') csrfHeaderSet = true;
                return nativeSetHeader(name, value);
            };
            xhr.send = function (body) {
                if (isMutation(method) && isSameOrigin(url) && !csrfHeaderSet) {
                    xhr.setRequestHeader('X-SGI-CSRF', token);
                }
                return nativeSend(body);
            };

            return xhr;
        };
        window.XMLHttpRequest.prototype = NativeXHR.prototype;
    }

    if (window.axios && window.axios.interceptors) {
        window.axios.interceptors.request.use(function (config) {
            config.url = resolveUrl(config.url || '');
            if (isMutation(config.method) && isSameOrigin(config.url || window.location.href)) {
                config.headers = config.headers || {};
                if (!config.headers['X-SGI-CSRF']) config.headers['X-SGI-CSRF'] = token;
            }
            return config;
        });
    }
}());
