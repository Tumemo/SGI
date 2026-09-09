window.SGIPage.mount("acesso/login", function (pageConfig, pageScope) {

        const API_BASE = (window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');

        async function realizarLogin(e) {
            e.preventDefault();

            const form = e.target;
            const msgErro = form.querySelector('[id^="msg_erro"]');

            msgErro.innerText = "";

            const matriculaInput = form.querySelector('.ipt-matricula');
            const senhaInput = form.querySelector('.ipt-senha');

            const payload = {
                matricula: matriculaInput.value.trim(),
                senha: senhaInput.value.trim()
            };

            try {
                const response = await fetch(API_BASE + 'login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.status === 'sucesso') {
                    window.location.href = data.redirect;
                } else {
                    msgErro.innerText = data.mensagem || "Erro ao realizar o login.";
                }
            } catch (err) {
                msgErro.innerText = "Erro ao conectar com o servidor.";
            }
        }

        pageScope.listen(document.getElementById('form_mobile'), 'submit', realizarLogin);
        pageScope.listen(document.getElementById('form_desktop'), 'submit', realizarLogin);

return {realizarLogin};
});
