window.SGIPage.mount("aluno/trocar-senha", function (pageConfig, pageScope) {
    const form = document.getElementById('formPrimeiroAcesso');
    const passwordInput = document.getElementById('novaSenhaPrimeiroAcesso');
    const confirmationInput = document.getElementById('confirmarSenhaPrimeiroAcesso');
    const message = document.getElementById('msgPrimeiroAcesso');
    const submitButton = document.getElementById('btnSalvarSenhaPrimeiroAcesso');
    const apiBase = new URL(window.SGI_API_BASE || '/api/v1/', window.location.origin);
    if (!apiBase.pathname.endsWith('/')) apiBase.pathname += '/';
    const passwordEndpoint = new URL('senha', apiBase).href;

    function showMessage(text, type) {
        if (!message) return;
        message.className = 'small mb-3';
        if (type === 'error') message.classList.add('text-danger');
        if (type === 'success') message.classList.add('text-success');
        if (type === 'pending') message.classList.add('text-secondary');
        message.textContent = text;
    }

    function togglePassword(button) {
        const input = document.getElementById(button.dataset.target);
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', show);
            icon.classList.toggle('bi-eye-slash', !show);
        }
        button.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
        button.setAttribute('aria-pressed', String(show));
    }

    async function submitFirstPassword(event) {
        event.preventDefault();
        if (!form || !passwordInput || !confirmationInput || !submitButton) return;

        const newPassword = passwordInput.value;
        const confirmation = confirmationInput.value;

        if (newPassword.length < 6) {
            showMessage('A nova senha deve ter pelo menos 6 caracteres.', 'error');
            passwordInput.focus();
            return;
        }
        if (newPassword !== confirmation) {
            showMessage('As senhas não coincidem.', 'error');
            confirmationInput.focus();
            return;
        }

        submitButton.disabled = true;
        showMessage('Salvando sua nova senha...', 'pending');

        try {
            const response = await fetch(passwordEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-SGI-CSRF': window.SGI_CSRF_TOKEN || ''
                },
                body: JSON.stringify({
                    nova_senha: newPassword,
                    confirmar_senha: confirmation
                })
            });

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }

            if (!response.ok || !data || data.success !== true) {
                const errorMessage = data && typeof data.message === 'string'
                    ? data.message
                    : 'Não foi possível salvar sua senha. Tente novamente.';
                throw new Error(errorMessage);
            }

            showMessage(data.message || 'Senha alterada com sucesso.', 'success');
            if (typeof data.redirect === 'string' && data.redirect !== '') {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }
        } catch (error) {
            const offlineMessage = !navigator.onLine
                ? 'Conecte-se à internet para salvar sua senha.'
                : 'Erro ao conectar com o servidor. Tente novamente.';
            showMessage(
                !navigator.onLine
                    ? offlineMessage
                    : (error instanceof Error && error.message ? error.message : offlineMessage),
                'error'
            );
            submitButton.disabled = false;
        }
    }

    window.SGIPage.ready(function () {
        if (form) pageScope.listen(form, 'submit', submitFirstPassword);
        document.querySelectorAll('.password-visibility-toggle').forEach(function (button) {
            pageScope.listen(button, 'click', function () {
                togglePassword(button);
            });
        });
    });

    return { submitFirstPassword, togglePassword };
});
