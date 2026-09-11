(function (global) {
    'use strict';
    if (global.SGIPage) return;
    var activeScope = null;
    var constructionScope = null;
    function createScope() {
        var listeners = new WeakMap();
        var globalListeners = [];
        var cleanups = [];
        var registrationBatch = 0;
        var scope = {
            active: true,
            beginBatch: function () { registrationBatch += 1; },
            listen: function (target, type, callback, options) {
                // Some controls only exist for a particular profile or layout.
                if (target == null) return;
                var records = listeners.get(target) || new Map();
                listeners.set(target, records);
                var capture = typeof options === 'boolean' ? options : !!(options && options.capture);
                // A function's source text is not its identity: two closures can
                // have the same source and still capture different state. Keep
                // one binding per callback/options pair while allowing both
                // legitimate handlers to coexist.
                var key = type + ':' + capture;
                var callbacks = records.get(key) || [];
                var obsolete = callbacks.filter(function (record) { return record.batch < registrationBatch; });
                obsolete.forEach(function (record) {
                    target.removeEventListener(type, record.callback, record.options);
                    var globalIndex = globalListeners.indexOf(record);
                    if (globalIndex >= 0) globalListeners.splice(globalIndex, 1);
                });
                callbacks = callbacks.filter(function (record) { return record.batch >= registrationBatch; });
                var previous = callbacks.find(function (record) {
                    return record.callback === callback && record.options === options;
                });
                if (previous) {
                    target.removeEventListener(type, previous.callback, previous.options);
                    callbacks = callbacks.filter(function (record) { return record !== previous; });
                }
                var record = { target: target, type: type, callback: callback, options: options, batch: registrationBatch };
                callbacks.push(record);
                records.set(key, callbacks);
                if (target === global || target === document) {
                    var index = globalListeners.indexOf(previous);
                    if (index >= 0) {
                        globalListeners[index] = record;
                    } else {
                        globalListeners.push(record);
                    }
                }
                target.addEventListener(type, callback, options);
            },
            onDeactivate: function (callback) { cleanups.push(callback); },
            activate: function () {
                scope.active = true;
                globalListeners.forEach(function (r) { r.target.addEventListener(r.type, r.callback, r.options); });
                activeScope = scope;
            },
            deactivate: function () {
                scope.active = false;
                globalListeners.forEach(function (r) { r.target.removeEventListener(r.type, r.callback, r.options); });
                cleanups.forEach(function (callback) { callback(); });
                if (global.SGI && typeof global.SGI.cancelDialogs === 'function') global.SGI.cancelDialogs();
            }
        };
        return scope;
    }
    function ready(callback, explicitScope) {
        var scope = explicitScope || constructionScope;
        var invoke = function () {
            if (scope) scope.beginBatch();
            callback();
        };
        if (global.__SGI_SPA__ && global.__SGI_SPA__.registrarInit) {
            global.__SGI_SPA__.registrarInit(invoke);
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', invoke, { once: true });
        } else {
            invoke();
        }
    }
    function mount(name, factory) {
        var element = document.querySelector('script[data-sgi-config="' + name + '"]');
        var config = element ? JSON.parse(element.textContent) : {};
        var actions = {};
        var scope = createScope();
        ready(function () { scope.activate(); Object.assign(global, actions); }, null);
        constructionScope = scope;
        actions = factory(config, scope) || {};
        constructionScope = null;
        // Atributos de eventos HTML continuam disponíveis enquanto o estado de
        // cada tela permanece isolado em seu próprio closure.
        Object.assign(global, actions);
    }
    function prepareModal(element) {
        if (!element || element.dataset.sgiModalLifecycle === '1') return;
        element.dataset.sgiModalLifecycle = '1';
        var opening = false;
        var closeRequested = false;
        element.addEventListener('show.bs.modal', function () { opening = true; });
        element.addEventListener('shown.bs.modal', function () {
            opening = false;
            if (closeRequested) {
                closeRequested = false;
                global.bootstrap.Modal.getOrCreateInstance(element).hide();
            }
        });
        element.addEventListener('click', function (event) {
            if (opening && event.target.closest('[data-bs-dismiss="modal"]')) {
                closeRequested = true;
            }
        });
    }
    global.SGIPage = {
        ready: ready, mount: mount, prepareModal: prepareModal,
        deactivate: function () { if (activeScope) activeScope.deactivate(); activeScope = null; }
    };
})(window);
