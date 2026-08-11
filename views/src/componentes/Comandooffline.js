/* ============================================================
   SGI COMANDO OFFLINE — Fila de formularios HTML

   Intercepta o envio de formularios HTML que possuem o atributo
   data-sgi-offline (opcao "opt-in"). Quando o dispositivo esta
   offline, o formulario e salvo localmente (IndexedDB via
   window.SGIOffline) e enviado automaticamente quando a conexao
   voltar. Requer que o offline-core.js esteja carregado antes.

   Para um formulario funcionar offline basta:
       <form action="../api/..." method="POST" data-sgi-offline> ...
   ============================================================ */
(function () {
    'use strict';
    if (window.__SGI_OFFLINE_FORM__) return;
    window.__SGI_OFFLINE_FORM__ = true;

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.hasAttribute('data-sgi-offline')) return;
        if (form.hasAttribute('no-offline')) return;
        if (!window.SGIOffline) return;

        e.preventDefault();

        var payload = {};
        new FormData(form).forEach(function (value, key) {
            if (payload[key] !== undefined) {
                if (!Array.isArray(payload[key])) payload[key] = [payload[key]];
                payload[key].push(value);
            } else {
                payload[key] = value;
            }
        });

        var url = form.getAttribute('action') || window.location.href;
        var method = (form.getAttribute('method') || 'POST').toUpperCase();

        var botao = form.querySelector('[type="submit"]');
        if (botao) botao.disabled = true;

        window.SGIOffline.submit(url, method, payload)
            .then(function (res) {
                if (res && res.offline) {
                    alert('Sem conexao: dados salvos localmente. Serao enviados quando houver conexao.');
                } else {
                    alert('Dados enviados com sucesso!');
                }
                form.reset();
            })
            .catch(function () {
                alert('Falha ao enviar os dados. Tente novamente.');
            })
            .finally(function () {
                if (botao) botao.disabled = false;
            });
    });
})();
