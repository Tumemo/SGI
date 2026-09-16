window.SGIPage.mount("acesso/login", function (pageConfig, pageScope) {

        const API_BASE = (window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');

        async function realizarLogin(e) {
            e.preventDefault();

            const form = e.target;
            const msgErro = form.querySelector('[id^="msg_erro"]');
            if (!msgErro) return;

            // O bloqueio é no formulário e na função: Enter e cliques repetidos
            // não podem iniciar outra autenticação enquanto a primeira aguarda.
            if (form.dataset.sgiSubmitting === 'true') return;

            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) return;

            form.dataset.sgiSubmitting = 'true';
            form.setAttribute('aria-busy', 'true');
            const buttonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Entrando…';
            msgErro.textContent = "";

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
                    msgErro.textContent = data.mensagem || "Erro ao realizar o login.";
                }
            } catch (err) {
                msgErro.textContent = "Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.";
            } finally {
                form.dataset.sgiSubmitting = 'false';
                form.removeAttribute('aria-busy');
                submitButton.disabled = false;
                submitButton.textContent = buttonText;
            }
        }

        pageScope.listen(document.getElementById('form_mobile'), 'submit', realizarLogin);
        pageScope.listen(document.getElementById('form_desktop'), 'submit', realizarLogin);

        document.querySelectorAll('.login-recovery-button').forEach(function (button) {
            pageScope.listen(button, 'click', function () {
                const guidance = document.getElementById(button.getAttribute('aria-controls'));
                if (!guidance) return;

                const expanded = button.getAttribute('aria-expanded') === 'true';
                guidance.hidden = expanded;
                button.setAttribute('aria-expanded', String(!expanded));
            });
        });

return {realizarLogin};
});
