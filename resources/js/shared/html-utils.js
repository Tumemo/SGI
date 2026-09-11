(function (global) {
    'use strict';

    const entities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, (character) => entities[character]);
    }

    function setText(element, value) {
        if (element) element.textContent = value == null ? '' : String(value);
        return element;
    }

    global.SGIHtml = Object.freeze({ escape: escapeHtml, setText });
})(window);
