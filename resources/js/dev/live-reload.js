(function () {
    'use strict';

    if (typeof window.EventSource !== 'function') return;

    var source = null;
    var reconnectTimer = null;
    var port = 35729;

    function connect() {
        if (source) source.close();
        var host = window.location.hostname || '127.0.0.1';
        var protocol = window.location.protocol === 'https:' ? 'https:' : 'http:';
        source = new window.EventSource(protocol + '//' + host + ':' + port + '/events');
        source.addEventListener('reload', function () {
            window.location.reload();
        });
        source.onerror = function () {
            source.close();
            if (reconnectTimer !== null) window.clearTimeout(reconnectTimer);
            reconnectTimer = window.setTimeout(connect, 1000);
        };
    }

    connect();
})();
