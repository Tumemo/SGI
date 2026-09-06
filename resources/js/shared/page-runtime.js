(function (global) {
    'use strict';
    if (global.SGIPage) return;
    var activeScope = null;
    function createScope() {
        var listeners = new WeakMap();
        var globalListeners = [];
        var cleanups = [];
        var scope = {
            active: true,
            listen: function (target, type, callback, options) {
                // Some controls only exist for a particular profile or layout.
                if (target == null) return;
                var records = listeners.get(target) || new Map();
                listeners.set(target, records);
                var capture = typeof options === 'boolean' ? options : !!(options && options.capture);
                var key = type + ':' + capture + ':' + String(callback);
                var previous = records.get(key);
                if (previous) target.removeEventListener(type, previous.callback, previous.options);
                var record = { target: target, type: type, callback: callback, options: options };
                records.set(key, record);
                if (target === global || target === document) {
                    var index = globalListeners.indexOf(previous);
                    if (index >= 0) globalListeners[index] = record;
                    else globalListeners.push(record);
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
            }
        };
        return scope;
    }
    function ready(callback) {
        if (global.__SGI_SPA__ && global.__SGI_SPA__.registrarInit) {
            global.__SGI_SPA__.registrarInit(callback);
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }
    function mount(name, factory) {
        var element = document.querySelector('script[data-sgi-config="' + name + '"]');
        var config = element ? JSON.parse(element.textContent) : {};
        var actions = {};
        var scope = createScope();
        ready(function () { scope.activate(); Object.assign(global, actions); });
        actions = factory(config, scope) || {};
        // HTML event attributes remain compatible while each screen's state stays
        // in its own closure. Reactivation restores that screen's actions.
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
