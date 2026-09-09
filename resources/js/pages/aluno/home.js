window.SGIPage.mount("aluno/home", function (pageConfig, pageScope) {

const APP_BASE = window.SGI_BASE_PATH || '';

function escapeHTML(string) {
    const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' };
    return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
}

let allInterclasses = [];

function renderCards(items) {
    const container = document.getElementById('listaInterclassesAluno');

    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="aluno-empty">
                <div class="empty-icon"><i class="bi bi-folder-x"></i></div>
                <h5>Nenhum interclasse encontrado</h5>
                <p>No momento não há competições disponíveis com os filtros selecionados.</p>
            </div>`;
        return;
    }

    container.innerHTML = `<div class="aluno-card-grid">${
        items.map(item => {
            const nome = escapeHTML(item.nome_interclasse);
            const ano = item.ano_interclasse ? escapeHTML(String(item.ano_interclasse).split('-')[0]) : 'N/A';
            const isAtivo = String(item.status_interclasse) === '1';
            const statusLabel = isAtivo ? 'Em Andamento' : 'Encerrado';
            const statusClass = isAtivo ? 'active' : 'inactive';
            const iconClass = isAtivo ? 'active' : 'inactive';
            const publicado = Boolean(item.ranking_publicado_em);
            const href = isAtivo ? `/aluno/modalidades?id=${item.id_interclasse}` : (publicado ? `/aluno/ranking?id=${item.id_interclasse}` : '#');
            const btnLabel = isAtivo ? 'Ver Detalhes <i class="bi bi-arrow-right"></i>' : (publicado ? 'Ver Ranking <i class="bi bi-bar-chart"></i>' : 'Ranking aguardando premiação');

            return `
                <div class="aluno-card" data-status="${statusClass}">
                    <div class="card-accent ${statusClass}"></div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="card-icon ${iconClass}">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div class="card-body">
                            <div class="card-title">${nome}</div>
                            <div class="card-meta">
                                <span><i class="bi bi-calendar3"></i>${ano}</span>
                                <span class="aluno-status-badge ${statusClass}">
                                    <i class="bi bi-circle-fill sgi-u-text-0-4rem" ></i>
                                    ${statusLabel}
                                </span>
                            </div>
                            <div class="card-footer">
                                <a href="${href}" class="btn-card">${btnLabel}</a>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('')
    }</div>`;
}

function filterAndRender() {
    const activeFilter = document.querySelector('.filter-pill.active')?.dataset?.filter || 'all';
    const searchTerm = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();

    let filtered = allInterclasses;

    if (activeFilter === 'active') {
        filtered = filtered.filter(item => String(item.status_interclasse) === '1');
    } else if (activeFilter === 'inactive') {
        filtered = filtered.filter(item => String(item.status_interclasse) !== '1');
    }

    if (searchTerm) {
        filtered = filtered.filter(item =>
            (item.nome_interclasse || '').toLowerCase().includes(searchTerm)
        );
    }

    renderCards(filtered);
}

async function carregarInterclassesAluno() {
    try {
        const res = await fetch('/api/v1/edicoes?regulamento=true');
        if (!res.ok) throw new Error('Resposta do servidor não amigável.');
        const lista = await res.json();

        if (!Array.isArray(lista) || lista.length === 0) {
            allInterclasses = [];
            renderCards([]);
            return;
        }

        allInterclasses = lista.sort((a, b) => {
            if (a.ano_interclasse > b.ano_interclasse) return -1;
            if (a.ano_interclasse < b.ano_interclasse) return 1;
            return (b.id_interclasse || 0) - (a.id_interclasse || 0);
        });
        filterAndRender();
    } catch (error) {
        console.error('Erro ao buscar dados:', error);
        document.getElementById('listaInterclassesAluno').innerHTML = `
            <div class="aluno-empty">
                <div class="empty-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5>Erro ao carregar</h5>
                <p>Não foi possível carregar as competições. Tente novamente mais tarde.</p>
            </div>`;
    }
}

async function carregarRegulamentoModal() {
    const btnPdf = document.getElementById('btnBaixarPdf');
    const btnAceitar = document.getElementById('btnAceitarTermo');

    try {
        const res = await fetch('/api/v1/edicoes?status_interclasse=1&regulamento=true');
        const data = await res.json();
        const ativo = Array.isArray(data) ? data[0] : data;

        if (ativo && ativo.regulamento_interclasse && ativo.regulamento_interclasse.trim() !== '') {
            btnPdf.href = `${APP_BASE}/uploads/regulamentos/${encodeURIComponent(ativo.regulamento_interclasse)}`;
            btnPdf.classList.remove('disabled');
            pageScope.listen(btnPdf, 'click', () => {
                btnAceitar.disabled = false;
                btnAceitar.removeAttribute('title');
            });
        } else {
            btnPdf.textContent = 'Sem PDF anexado';
            btnPdf.classList.add('btn-secondary', 'disabled');
            btnPdf.classList.remove('btn-danger');
            btnAceitar.disabled = false;
            btnAceitar.removeAttribute('title');
        }
    } catch (e) {
        console.error("Erro ao carregar PDF do regulamento:", e);
        btnAceitar.disabled = false;
    }
}

let modalTrocarSenhaInstance = null;

function abrirModalTrocarSenha() {
    const modalEl = document.getElementById('modalTrocarSenha');
    if (!modalEl || modalTrocarSenhaInstance) return;
    modalTrocarSenhaInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    modalTrocarSenhaInstance.show();
}

async function salvarNovaSenha() {
    const msgEl = document.getElementById('msgTrocarSenha');
    const btn = document.getElementById('btnSalvarNovaSenha');
    msgEl.innerHTML = '';

    const novaSenha = document.getElementById('novaSenha').value;
    const confirmarSenha = document.getElementById('confirmarNovaSenha').value;

    if (novaSenha.length < 6) {
        msgEl.innerHTML = '<span class="text-danger">A senha deve ter no mínimo 6 caracteres.</span>';
        return;
    }
    if (novaSenha !== confirmarSenha) {
        msgEl.innerHTML = '<span class="text-danger">As senhas não coincidem.</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';

    try {
        const res = await fetch('/api/v1/senha', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nova_senha: novaSenha, confirmar_senha: confirmarSenha })
        });
        if (res.status === 401) { window.location.href = `${APP_BASE}/aluno/login`; return; }
        const data = await res.json();
        if (data.success) {
            msgEl.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
            btn.disabled = true;
            setTimeout(() => window.location.reload(), 1500);
        } else {
            msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao alterar a senha.') + '</span>';
        }
    } catch (error) {
        msgEl.innerHTML = '<span class="text-danger">Erro de conexão. Tente novamente.</span>';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar Senha';
    }
}

async function initModalTermo() {
    const modalElement = document.getElementById('modalTermo');
    const modalTermo = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: false });
    const btnAceitar = document.getElementById('btnAceitarTermo');
    const btnRecusar = document.getElementById('btnRecusarTermo');
    const avisoRecusa = document.getElementById('avisoRecusa');

    let precisaTrocarSenha = false;

    try {
        const checagem = await fetch('/api/v1/termos', { method: 'GET' });
        if (checagem.status === 401) return;
        const resCheck = await checagem.json();
        precisaTrocarSenha = !!resCheck.exige_troca_senha;
        if (resCheck.success && resCheck.termo_aceito === true) {
            if (precisaTrocarSenha) abrirModalTrocarSenha();
            return;
        }
    } catch (e) {
        console.error("Erro ao verificar status dos termos:", e);
    }

    await carregarRegulamentoModal();
    modalTermo.show();

    pageScope.listen(btnAceitar, 'click', async function() {
        btnAceitar.disabled = true;
        btnRecusar.disabled = true;
        btnAceitar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';
        try {
            const res = await fetch('/api/v1/termos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (res.status === 401) { window.location.href = `${APP_BASE}/aluno/login`; return; }
            const data = await res.json();
            if (data.success) {
                avisoRecusa.classList.add('d-none');
                modalTermo.hide();
                if (data.exige_troca_senha || precisaTrocarSenha) abrirModalTrocarSenha();
            } else {
                avisoRecusa.textContent = data.message || 'Erro ao salvar aceite. Tente novamente.';
                avisoRecusa.classList.remove('d-none');
            }
        } catch (error) {
            avisoRecusa.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
            avisoRecusa.classList.remove('d-none');
        } finally {
            btnAceitar.disabled = false;
            btnRecusar.disabled = false;
            btnAceitar.textContent = 'Aceitar e Continuar';
        }
    });

    pageScope.listen(btnRecusar, 'click', function() {
        avisoRecusa.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Acesso bloqueado:</strong> É necessário aceitar os termos de responsabilidade para participar e utilizar o painel.
        `;
        avisoRecusa.classList.remove('d-none');
    });
}

window.SGIPage.ready( function() {
    carregarInterclassesAluno();
    initModalTermo();

    pageScope.listen(document.getElementById('formTrocarSenha'), 'submit', function(e) {
        e.preventDefault();
        salvarNovaSenha();
    });
    pageScope.listen(document.getElementById('btnSalvarNovaSenha'), 'click', function() {
        document.getElementById('formTrocarSenha')?.requestSubmit();
    });

    pageScope.listen(document.getElementById('searchInput'), 'input', filterAndRender);

    document.querySelectorAll('.filter-pill').forEach(pill => {
        pageScope.listen(pill, 'click', function() {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            filterAndRender();
        });
    });
});

return {escapeHTML, renderCards, filterAndRender, carregarInterclassesAluno, carregarRegulamentoModal, abrirModalTrocarSenha, salvarNovaSenha, initModalTermo};
});
