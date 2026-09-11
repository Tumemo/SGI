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

    // Feedback modal compartilhado para alertas e confirmações da aplicação.
    const dialogVariants = {
        success: { icon: 'bi-check-circle-fill', tone: 'success' },
        error: { icon: 'bi-exclamation-circle-fill', tone: 'danger' },
        warning: { icon: 'bi-exclamation-triangle-fill', tone: 'warning' },
        info: { icon: 'bi-info-circle-fill', tone: 'primary' }
    };
    const dialogQueue = [];
    let activeDialog = null;
    let dialogPumpScheduled = false;

    function normalizarDialogo(input, confirmacao) {
        const options = typeof input === 'string' ? { mensagem: input } : Object.assign({}, input || {});
        options.mensagem = options.mensagem == null ? '' : String(options.mensagem);
        options.titulo = options.titulo == null
            ? (confirmacao ? 'Confirme a ação' : 'Atenção')
            : String(options.titulo);
        if (!dialogVariants[options.tipo]) {
            const texto = options.mensagem.toLowerCase();
            options.tipo = confirmacao ? 'warning' : (/erro|falha|não foi|não é possível|não pode|não encontrado|não disponível/.test(texto) ? 'error' : (/sucesso|salvo|removid|enviad|avançou|definido|concluíd/.test(texto) ? 'success' : 'info'));
        }
        options.textoConfirmar = options.textoConfirmar == null ? (confirmacao ? 'Confirmar' : 'Entendi') : String(options.textoConfirmar);
        options.textoCancelar = options.textoCancelar == null ? 'Cancelar' : String(options.textoCancelar);
        options.restoreModal = options.restoreModal !== false;
        return options;
    }

    function criarDialogo(entry) {
        const options = entry.options;
        const variant = dialogVariants[options.tipo];
        const id = 'sgiDialog' + (++dialogSequence);
        const modal = document.createElement('div');
        modal.className = 'modal fade sgi-feedback-modal';
        modal.id = id;
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('data-bs-backdrop', 'static');
        modal.setAttribute('data-bs-keyboard', 'true');

        const dialog = document.createElement('div');
        dialog.className = 'modal-dialog modal-dialog-centered modal-dialog-scrollable';
        const content = document.createElement('div');
        content.className = 'modal-content border-0 rounded-4 shadow-lg';
        const header = document.createElement('div');
        header.className = 'modal-header border-0 pb-0 align-items-start';
        const icon = document.createElement('span');
        icon.className = 'sgi-feedback-modal__icon text-' + variant.tone;
        icon.setAttribute('aria-hidden', 'true');
        const iconGlyph = document.createElement('i');
        iconGlyph.className = 'bi ' + variant.icon;
        icon.appendChild(iconGlyph);
        const title = document.createElement('h2');
        title.className = 'modal-title fs-5 fw-semibold flex-grow-1 ms-3';
        title.id = id + 'Title';
        title.textContent = options.titulo;
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('aria-label', 'Fechar');
        header.append(icon, title, close);

        const body = document.createElement('div');
        body.className = 'modal-body pt-3';
        body.id = id + 'Description';
        body.setAttribute('aria-live', 'polite');
        const message = document.createElement('p');
        message.className = 'sgi-feedback-modal__message mb-0';
        message.textContent = options.mensagem;
        body.appendChild(message);

        const footer = document.createElement('div');
        footer.className = 'modal-footer border-0 pt-0 gap-2';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-outline-secondary';
        cancel.textContent = options.textoCancelar;
        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-' + (options.destrutivo ? 'danger' : 'primary');
        confirm.textContent = options.textoConfirmar;
        if (entry.confirmacao) footer.append(cancel, confirm);
        else footer.append(confirm);

        content.append(header, body, footer);
        dialog.appendChild(content);
        modal.appendChild(dialog);
        modal.setAttribute('aria-labelledby', title.id);
        modal.setAttribute('aria-describedby', body.id);

        entry.modal = modal;
        entry.confirmButton = confirm;
        entry.cancelButton = entry.confirmacao ? cancel : close;
        entry.returnFocus = entry.returnFocus || (document.activeElement instanceof HTMLElement ? document.activeElement : null);
        entry.result = false;
        entry.settled = false;
        document.body.appendChild(modal);

        const finish = () => {
            if (entry.settled) return;
            entry.settled = true;
            if (entry.bootstrapInstance) entry.bootstrapInstance.dispose();
            if (entry.keydownHandler) document.removeEventListener('keydown', entry.keydownHandler, true);
            modal.remove();
            if (entry.suspendedModal && options.restoreModal && document.contains(entry.suspendedModal)) {
                const sourceInstance = global.bootstrap.Modal.getOrCreateInstance(entry.suspendedModal);
                const restoreFocus = () => {
                    if (entry.returnFocus && document.contains(entry.returnFocus)) entry.returnFocus.focus();
                };
                entry.suspendedModal.addEventListener('shown.bs.modal', restoreFocus, { once: true });
                sourceInstance.show();
            } else if (!entry.suspendedModal && entry.returnFocus && document.contains(entry.returnFocus)) {
                entry.returnFocus.focus();
            }
            entry.resolve(entry.confirmacao ? entry.result : undefined);
            if (activeDialog === entry) activeDialog = null;
            pumpDialogQueue();
        };
        entry.finish = finish;
        modal.addEventListener('hidden.bs.modal', finish, { once: true });
        close.addEventListener('click', () => fecharDialogo(entry, false));
        if (entry.confirmacao) {
            cancel.addEventListener('click', () => fecharDialogo(entry, false));
            confirm.addEventListener('click', () => fecharDialogo(entry, true));
        } else {
            confirm.addEventListener('click', () => fecharDialogo(entry, false));
        }
        return modal;
    }

    let dialogSequence = 0;

    function fecharDialogo(entry, confirmado) {
        if (!entry || entry !== activeDialog || entry.settled) return;
        entry.result = confirmado === true;
        [entry.confirmButton, entry.cancelButton].filter(Boolean).forEach(button => { button.disabled = true; });
        if (entry.bootstrapInstance) {
            entry.bootstrapInstance.hide();
        } else {
            entry.modal.classList.remove('show');
            entry.modal.style.display = 'none';
            entry.finish();
        }
    }

    function pumpDialogQueue() {
        if (activeDialog || !dialogQueue.length) return;
        if (!document.body || document.readyState === 'loading') {
            if (dialogPumpScheduled) return;
            dialogPumpScheduled = true;
            document.addEventListener('DOMContentLoaded', () => {
                dialogPumpScheduled = false;
                pumpDialogQueue();
            }, { once: true });
            return;
        }
        const entry = dialogQueue.shift();
        activeDialog = entry;
        const sourceModal = document.querySelector('.modal.show:not(.sgi-feedback-modal)');
        if (sourceModal && global.bootstrap && global.bootstrap.Modal) {
            const sourceInstance = global.bootstrap.Modal.getOrCreateInstance(sourceModal);
            if (sourceInstance._isShown || sourceInstance._isTransitioning) {
                entry.suspendedModal = sourceModal;
                entry.returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
                sourceModal.addEventListener('hidden.bs.modal', () => abrirDialogo(entry), { once: true });
                if (sourceModal.contains(document.activeElement) && document.activeElement.blur) document.activeElement.blur();
                if (sourceInstance._isShown) sourceInstance.hide();
                return;
            }
        }
        abrirDialogo(entry);
    }

    function abrirDialogo(entry) {
        if (entry.cancelled) {
            if (activeDialog === entry) activeDialog = null;
            pumpDialogQueue();
            return;
        }
        const modal = criarDialogo(entry);
        if (global.bootstrap && global.bootstrap.Modal) {
            entry.bootstrapInstance = global.bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: 'static', keyboard: true, focus: true
            });
            modal.addEventListener('shown.bs.modal', () => {
                (entry.confirmacao ? entry.cancelButton : entry.confirmButton).focus();
            }, { once: true });
            entry.bootstrapInstance.show();
        } else {
            entry.keydownHandler = event => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    fecharDialogo(entry, false);
                }
            };
            document.addEventListener('keydown', entry.keydownHandler, true);
            modal.style.display = 'block';
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('aria-modal', 'true');
            modal.classList.add('show');
            (entry.confirmacao ? entry.cancelButton : entry.confirmButton).focus();
        }
    }

    function enfileirarDialogo(input, confirmacao) {
        return new Promise(resolve => {
            dialogQueue.push({
                options: normalizarDialogo(input, confirmacao),
                confirmacao,
                resolve
            });
            pumpDialogQueue();
        });
    }

    function cancelarDialogos() {
        while (dialogQueue.length) {
            const entry = dialogQueue.shift();
            entry.resolve(entry.confirmacao ? false : undefined);
        }
        if (activeDialog) {
            if (activeDialog.modal) {
                fecharDialogo(activeDialog, false);
            } else {
                activeDialog.cancelled = true;
                activeDialog.resolve(activeDialog.confirmacao ? false : undefined);
                activeDialog = null;
            }
        }
    }

    global.SGI = global.SGI || {};
    global.SGI.showToast = mostrarToast;
    global.SGI.alert = function (input) { return enfileirarDialogo(input, false); };
    global.SGI.confirm = function (input) { return enfileirarDialogo(input, true); };
    global.SGI.cancelDialogs = cancelarDialogos;
}(window));
