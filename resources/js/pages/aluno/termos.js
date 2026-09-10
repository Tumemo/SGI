window.SGIPage.mount("aluno/termos", function (pageConfig, pageScope) {

        const APP_BASE = window.SGI_BASE_PATH || '';

        async function carregarRegulamento() {
            const statusEl = document.getElementById('statusRegulamento');
            const containerPdf = document.getElementById('containerPdfRegulamento');
            const btnPdf = document.getElementById('btnBaixarPdf');

            try {
                // Busca a lista de interclasses com regulamento atrelado
                const res = await fetch('/api/v1/edicoes?status_interclasse=1&regulamento=true');
                if (!res.ok) throw new Error('Erro na resposta da API');

                const data = await res.json();

                // Trata retorno caso venha um array ou objeto único
                const lista = Array.isArray(data) ? data : [data];

                // Busca o interclasse ativo (status_interclasse === '1' ou 1)
                const ativo = lista.find(i => String(i.status_interclasse) === '1') || lista[0];

                if (ativo && ativo.regulamento_interclasse && ativo.regulamento_interclasse.trim() !== '') {
                    btnPdf.href = APP_BASE + '/uploads/regulamentos/' + encodeURIComponent(ativo.regulamento_interclasse);

                    statusEl.classList.add('d-none');
                    containerPdf.classList.remove('d-none');
                } else {
                    statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                    statusEl.className = 'text-muted mb-0';
                }
            } catch (error) {
                console.error("Erro ao carregar regulamento:", error);
                statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
                statusEl.className = 'text-danger mb-0';
            }
        }

        async function carregarStatusTermos() {
            const button = document.getElementById('btnAceitarTermos');
            const message = document.getElementById('msgAceiteTermos');
            if (!button || !message) return;

            try {
                const res = await fetch('/api/v1/termos');
                if (res.status === 401) {
                    window.location.href = APP_BASE + '/aluno/login';
                    return;
                }
                const data = await res.json();
                if (res.ok && data.success && data.termo_aceito === true) {
                    button.disabled = true;
                    button.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Termos já aceitos';
                    message.className = 'small mt-3 mb-0 text-success';
                    message.textContent = 'Seu aceite está registrado.';
                }
            } catch (error) {
                console.error('Erro ao verificar aceite dos termos:', error);
            }
        }

        async function aceitarTermos() {
            const button = document.getElementById('btnAceitarTermos');
            const message = document.getElementById('msgAceiteTermos');
            if (!button || !message) return;

            button.disabled = true;
            message.className = 'small mt-3 mb-0 text-secondary';
            message.textContent = 'Registrando aceite...';
            try {
                const res = await fetch('/api/v1/termos', {
                    method: 'POST',
                    headers: { 'X-SGI-CSRF': window.SGI_CSRF_TOKEN || '' }
                });
                if (res.status === 401) {
                    window.location.href = APP_BASE + '/aluno/login';
                    return;
                }
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Não foi possível registrar o aceite.');
                }
                message.className = 'small mt-3 mb-0 text-success';
                message.textContent = data.message || 'Termos aceitos com sucesso.';
                window.location.href = APP_BASE + '/aluno/inicio';
            } catch (error) {
                button.disabled = false;
                message.className = 'small mt-3 mb-0 text-danger';
                message.textContent = error.message || 'Erro ao registrar o aceite. Tente novamente.';
            }
        }

        window.SGIPage.ready(function () {
            carregarRegulamento();
            carregarStatusTermos();
            const button = document.getElementById('btnAceitarTermos');
            if (button) pageScope.listen(button, 'click', aceitarTermos);
        });

return {carregarRegulamento, carregarStatusTermos, aceitarTermos};
});
