<?php
$tituloPagina = 'SGI - Início';
include 'componentes/head.php';

$paginaAtiva = 'home';
include 'componentes/nav.php';
?>

<main class="aluno-page p-5  py-4">
  <div class="row">
    <div class=" w-100 d-flex flex-column gap-4">

      <div class="aluno-hero mb-4">
        <h1>Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Aluno', ENT_QUOTES) ?>!   </h1>
        <p>Confira as competições disponíveis e participe!</p>
        
        <!-- NOVO BOTÃO DE ACESSO AOS JOGOS -->
        <div class="mt-3">
            <a href="jogos.php" class="btn btn-light fw-bold text-danger rounded-pill px-4 shadow-sm sgi-inline-663d5b1a" >
                <i class="bi bi-calendar-check me-2"></i> Ver Tabela de Jogos
            </a>
        </div>
      </div>

      <!-- <div class="aluno-section-header">
        <h2><i class="bi bi-trophy"></i>Interclasses</h2>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <div class="aluno-search sgi-inline-4cc9496c" >
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar...">
          </div>
        </div>
      </div> -->

      <div class="aluno-filter-pills mb-4" id="filterPills">
        <span class="filter-pill active" data-filter="active">Em Andamento</span>
        <span class="filter-pill" data-filter="all">Todos</span>
        <span class="filter-pill" data-filter="inactive">Encerrados</span>
      </div>

      <section id="listaInterclassesAluno">
        <div class="aluno-loading">
          <div class="spinner-border spinner-border-sm text-danger me-2" role="status">
            <span class="visually-hidden">Carregando...</span>
          </div>
          Carregando competições...
        </div>
      </section>

    </div>
  </div>
</main>

<div class="modal fade" id="modalTermo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-text me-2"></i>Termo de Responsabilidade e Regulamento
                </h5>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">Declaro para os devidos fins que aceito e assumo inteira responsabilidade pelos termos abaixo descritos para participação no Interclasse:</p>

                <ol class="ps-3 text-secondary lh-lg mb-4">
                    <li class="mb-2"><strong>Conduta:</strong> Comprometo-me a agir com respeito, <em>fair play</em> e espírito esportivo durante todas as atividades.</li>
                    <li class="mb-2"><strong>Regras:</strong> Declaro estar ciente e de acordo com todas as regras oficiais do Interclasse, acatando as decisões da organização e arbitragem.</li>
                    <li class="mb-2"><strong>Materiais:</strong> Responsabilizo-me pelos materiais esportivos e uniformes que me forem confiados, respondendo por eventuais danos ou extravios.</li>
                    <li class="mb-2"><strong>Saúde:</strong> Declaro estar em condições físicas adequadas para a prática das modalidades escolhidas, isentando a organização de responsabilidade por acidentes ou lesões decorrentes da participação.</li>
                    <li class="mb-2"><strong>Imagem:</strong> Autorizo o uso de minha imagem e voz para fins de divulgação do evento nas mídias oficiais da instituição.</li>
                    <li class="mb-2"><strong>Pontuação:</strong> Aceito o sistema de pontuação e classificação estabelecido, bem como as penalidades previstas no regulamento.</li>
                </ol>

                <div id="containerPdfRegulamento" class="card border-danger-subtle bg-danger-subtle bg-opacity-10 mb-3">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf-fill fs-3 text-danger"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Regulamento Oficial (PDF)</h6>
                                <small class="text-muted">Leia o regulamento completo antes de aceitar.</small>
                            </div>
                        </div>
                        <a id="btnBaixarPdf" href="#" target="_blank" class="btn btn-danger btn-sm rounded-3 fw-semibold d-inline-flex align-items-center gap-1 disabled">
                            <i class="bi bi-download"></i> Baixar / Ler PDF
                        </a>
                    </div>
                </div>

                <div id="avisoRecusa" class="alert alert-danger d-none m-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Não é possível continuar sem aceitar os termos.
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-end gap-2 bg-light px-4 py-3">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="btnRecusarTermo">Recusar</button>
                <button type="button" class="btn btn-danger px-4 fw-semibold" id="btnAceitarTermo" disabled title="Abra o PDF do regulamento acima para liberar o botão">Aceitar e Continuar</button>
            </div>
        </div>
    </div>
</div>

<script>
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
            const href = isAtivo ? `./modalidade.php?id=${item.id_interclasse}` : `./ranking.php?id=${item.id_interclasse}`;
            const btnLabel = isAtivo ? 'Ver Detalhes <i class="bi bi-arrow-right"></i>' : 'Ver Ranking <i class="bi bi-bar-chart"></i>';

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
                                    <i class="bi bi-circle-fill sgi-inline-a03c0aaa" ></i>
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
        const res = await fetch('../../../../api/interclasse.php?regulamento=true');
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
        const res = await fetch('../../../../api/interclasse.php?status_interclasse=1&regulamento=true');
        const data = await res.json();
        const ativo = Array.isArray(data) ? data[0] : data;

        if (ativo && ativo.regulamento_interclasse && ativo.regulamento_interclasse.trim() !== '') {
            btnPdf.href = `../../../../uploads/regulamentos/${ativo.regulamento_interclasse}`;
            btnPdf.classList.remove('disabled');
            btnPdf.addEventListener('click', () => {
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
        const res = await fetch('../../../../api/trocar_senha.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nova_senha: novaSenha, confirmar_senha: confirmarSenha })
        });
        if (res.status === 401) { window.location.href = '../../../..'; return; }
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
        const checagem = await fetch('../../../../api/concordarTermos.php', { method: 'GET' });
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

    btnAceitar.addEventListener('click', async function() {
        btnAceitar.disabled = true;
        btnRecusar.disabled = true;
        btnAceitar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';
        try {
            const res = await fetch('../../../../api/concordarTermos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (res.status === 401) { window.location.href = '../../../..'; return; }
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

    btnRecusar.addEventListener('click', function() {
        avisoRecusa.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Acesso bloqueado:</strong> É necessário aceitar os termos de responsabilidade para participar e utilizar o painel.
        `;
        avisoRecusa.classList.remove('d-none');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    carregarInterclassesAluno();
    initModalTermo();

    document.getElementById('formTrocarSenha')?.addEventListener('submit', function(e) {
        e.preventDefault();
        salvarNovaSenha();
    });
    document.getElementById('btnSalvarNovaSenha')?.addEventListener('click', function() {
        document.getElementById('formTrocarSenha')?.requestSubmit();
    });

    document.getElementById('searchInput')?.addEventListener('input', filterAndRender);

    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            filterAndRender();
        });
    });
});
</script>
<div class="modal fade" id="modalTrocarSenha" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shield-lock me-2"></i>Alterar Senha
                </h5>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">Por segurança, defina uma senha pessoal e confidencial. A senha padrão é a mesma para todos os alunos.</p>
                <form id="formTrocarSenha" novalidate>
                    <div class="mb-3">
                        <label for="novaSenha" class="form-label small fw-semibold text-secondary">Nova Senha</label>
                        <input type="password" class="form-control" id="novaSenha" name="nova_senha" minlength="6" maxlength="72" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="confirmarNovaSenha" class="form-label small fw-semibold text-secondary">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmarNovaSenha" name="confirmar_senha" minlength="6" maxlength="72" required autocomplete="new-password">
                    </div>
                    <div id="msgTrocarSenha" class="small mt-2 text-center"></div>
                </form>
            </div>
            <div class="modal-footer border-0 justify-content-end gap-2 bg-light px-4 py-3">
                <button type="button" class="btn btn-danger px-4 fw-semibold" id="btnSalvarNovaSenha">
                    <i class="bi bi-check-lg me-1"></i>Salvar Senha
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
