<?php
$tituloPagina = 'SGI - Início';
$cssExtra = '

/* ==================== DESIGN SYSTEM ==================== */
:root {
  --aluno-primary: #e30613;
  --aluno-primary-dark: #b02a37;
  --aluno-primary-light: #fce4e6;
  --aluno-primary-subtle: #fff0f0;
  --aluno-success: #198754;
  --aluno-bg: #f5f6fa;
  --aluno-surface: #ffffff;
  --aluno-border: #e9ecef;
  --aluno-text: #1a1a2e;
  --aluno-text-secondary: #6c757d;
  --aluno-text-muted: #adb5bd;
  --aluno-radius-sm: 8px;
  --aluno-radius-md: 12px;
  --aluno-radius-lg: 16px;
  --aluno-radius-xl: 24px;
  --aluno-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --aluno-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --aluno-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
  --aluno-shadow-hover: 0 12px 28px rgba(0,0,0,0.15);
  --aluno-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aluno-page {
  font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
  color: var(--aluno-text);
}

/* Hero Section */
.aluno-hero {
  background: linear-gradient(135deg, var(--aluno-primary) 0%, #c82333 100%);
  border-radius: var(--aluno-radius-xl);
  padding: 2.5rem 2rem;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.aluno-hero::before {
  content: "";
  position: absolute;
  top: -50%;
  right: -20%;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255,255,255,0.08);
}
.aluno-hero::after {
  content: "";
  position: absolute;
  bottom: -30%;
  left: 10%;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.aluno-hero h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  position: relative;
  z-index: 1;
}
.aluno-hero p {
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 0;
  position: relative;
  z-index: 1;
}

/* Search Bar */
.aluno-search {
  position: relative;
}
.aluno-search .form-control {
  border-radius: var(--aluno-radius-lg);
  border: 2px solid var(--aluno-border);
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  font-size: 0.95rem;
  transition: border-color var(--aluno-transition), box-shadow var(--aluno-transition);
}
.aluno-search .form-control:focus {
  border-color: var(--aluno-primary);
  box-shadow: 0 0 0 3px rgba(220,53,69,0.15);
  outline: none;
}
.aluno-search .search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--aluno-text-muted);
  font-size: 1.1rem;
  pointer-events: none;
}

/* Filter Pills */
.aluno-filter-pills {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.aluno-filter-pills .filter-pill {
  padding: 0.4rem 1rem;
  border-radius: 50px;
  border: 1.5px solid var(--aluno-border);
  background: var(--aluno-surface);
  color: var(--aluno-text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--aluno-transition);
  user-select: none;
}
.aluno-filter-pills .filter-pill:hover {
  border-color: var(--aluno-primary);
  color: var(--aluno-primary);
  background: var(--aluno-primary-subtle);
}
.aluno-filter-pills .filter-pill.active {
  background: var(--aluno-primary);
  border-color: var(--aluno-primary);
  color: #fff;
}

/* Card Grid */
.aluno-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
  gap: 1.25rem;
}

/* Individual Card */
.aluno-card {
  background: var(--aluno-surface);
  border-radius: var(--aluno-radius-lg);
  border: 1px solid var(--aluno-border);
  padding: 1.5rem;
  transition: all var(--aluno-transition);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.aluno-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--aluno-shadow-hover);
  border-color: transparent;
}
.aluno-card .card-accent {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  border-radius: 4px 0 0 4px;
}
.aluno-card .card-accent.active {
  background: var(--aluno-success);
}
.aluno-card .card-accent.inactive {
  background: var(--aluno-text-muted);
}
.aluno-card .card-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--aluno-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  flex-shrink: 0;
}
.aluno-card .card-icon.active {
  background: var(--aluno-primary-light);
  color: var(--aluno-primary);
}
.aluno-card .card-icon.inactive {
  background: #f0f0f0;
  color: var(--aluno-text-muted);
}
.aluno-card .card-body {
  flex: 1;
}
.aluno-card .card-title {
  font-size: 1.15rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--aluno-text);
}
.aluno-card .card-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  color: var(--aluno-text-secondary);
}
.aluno-card .card-meta .bi {
  margin-right: 0.25rem;
}
.aluno-card .card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}
.aluno-card .btn-card {
  background: var(--aluno-primary);
  color: #fff;
  border: none;
  border-radius: var(--aluno-radius-md);
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all var(--aluno-transition);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.aluno-card .btn-card:hover {
  background: var(--aluno-primary-dark);
  color: #fff;
  transform: translateX(2px);
}
.aluno-card .btn-card.inactive {
  background: var(--aluno-text-muted);
  cursor: default;
  pointer-events: none;
}

/* Status Badge */
.aluno-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.aluno-status-badge.active {
  background: #d1e7dd;
  color: #0a3622;
}
.aluno-status-badge.inactive {
  background: #e9ecef;
  color: #6c757d;
}

/* Section Header */
.aluno-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.aluno-section-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--aluno-text);
  margin: 0;
}
.aluno-section-header h2 .bi {
  color: var(--aluno-primary);
  margin-right: 0.5rem;
}

/* Empty & Loading */
.aluno-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--aluno-text-secondary);
}
.aluno-empty .empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: var(--aluno-text-muted);
}
.aluno-empty h5 {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.aluno-empty p {
  font-size: 0.9rem;
  max-width: 400px;
  margin: 0 auto;
}
.aluno-loading {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--aluno-text-secondary);
}

/* Responsive */
@media (max-width: 767.98px) {
  .aluno-hero {
    padding: 1.5rem 1.25rem;
    border-radius: var(--aluno-radius-lg);
  }
  .aluno-hero h1 { font-size: 1.35rem; }
  .aluno-card-grid { grid-template-columns: 1fr; }
  .aluno-filter-pills {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 0.25rem;
  }
  .aluno-filter-pills .filter-pill {
    white-space: nowrap;
    flex-shrink: 0;
  }
  .aluno-section-header { flex-direction: column; align-items: flex-start; }
}
@media (min-width: 768px) and (max-width: 991.98px) {
  .aluno-card-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Modal overrides */
#modalTermo .modal-content {
  border: none;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
#modalTermo .modal-header {
  background: var(--aluno-primary);
  color: #fff;
  border-bottom: none;
}
';

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
      </div>

      <!-- <div class="aluno-section-header">
        <h2><i class="bi bi-trophy"></i>Interclasses</h2>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <div class="aluno-search" style="min-width:220px">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar...">
          </div>
        </div>
      </div> -->

      <div class="aluno-filter-pills mb-4" id="filterPills">
        <span class="filter-pill active" data-filter="all">Todos</span>
        <span class="filter-pill" data-filter="active">Em Andamento</span>
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
                                    <i class="bi bi-circle-fill" style="font-size:0.4rem"></i>
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

async function initModalTermo() {
    const modalElement = document.getElementById('modalTermo');
    const modalTermo = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: false });
    const btnAceitar = document.getElementById('btnAceitarTermo');
    const btnRecusar = document.getElementById('btnRecusarTermo');
    const avisoRecusa = document.getElementById('avisoRecusa');

    try {
        const checagem = await fetch('../../../../api/concordarTermos.php', { method: 'GET' });
        if (checagem.status === 401) return;
        const resCheck = await checagem.json();
        if (resCheck.success && resCheck.termo_aceito === true) return;
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
