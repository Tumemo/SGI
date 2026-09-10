(function (global) {
    'use strict';

    const variants = {
        success: { className: 'success', icon: 'bi-check-circle-fill' },
        error: { className: 'danger', icon: 'bi-exclamation-circle-fill' },
        warning: { className: 'warning', icon: 'bi-exclamation-triangle-fill' },
        info: { className: 'info', icon: 'bi-info-circle-fill' }
    };

    function getContainer() {
        let container = document.getElementById('sgiToastContainer');
        if (container) return container;

        container = document.createElement('div');
        container.id = 'sgiToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
        return container;
    }

    function mostrarToast(mensagem, tipo, opcoes) {
        const variant = variants[tipo] || variants.info;
        const options = opcoes || {};
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${variant.className} border-0`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');

        const row = document.createElement('div');
        row.className = 'd-flex';
        const body = document.createElement('div');
        body.className = 'toast-body d-flex align-items-center gap-2';
        const icon = document.createElement('i');
        icon.className = `bi ${variant.icon}`;
        const message = document.createElement('span');
        message.textContent = mensagem == null ? '' : String(mensagem);
        body.append(icon, message);

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Fechar');
        row.append(body, close);
        toast.appendChild(row);
        getContainer().appendChild(toast);

        if (global.bootstrap && global.bootstrap.Toast) {
            toast.addEventListener('hidden.bs.toast', () => toast.remove(), { once: true });
            global.bootstrap.Toast.getOrCreateInstance(toast, {
                autohide: options.autohide !== false,
                delay: Number.isFinite(options.delay) ? options.delay : 3500
            }).show();
        } else {
            toast.classList.add('show');
            global.setTimeout(() => toast.remove(), Number.isFinite(options.delay) ? options.delay : 3500);
        }
        return toast;
    }

    global.SGI = global.SGI || {};
    global.SGI.showToast = mostrarToast;
}(window));
