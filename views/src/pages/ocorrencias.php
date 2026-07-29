<?php
$tituloPagina = 'SGI - Ocorrências';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
.ocr-page{padding-bottom:5rem}
.ocr-container{width:100%;padding:0 2rem}
.ocr-header{margin-bottom:2rem}
.ocr-header__top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ocr-header__title{font-size:1.75rem;font-weight:800;color:#111827;letter-spacing:-.03em;margin:0;line-height:1.2}
.ocr-header__sub{font-size:.9rem;color:#6B7280;margin:.35rem 0 0;font-weight:400}
.ocr-hist-btn{display:inline-flex;align-items:center;gap:.45rem;border:1.5px solid #E5E7EB;background:#fff;color:#374151;border-radius:10px;padding:.5rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s}
.ocr-hist-btn:hover{border-color:#D1D5DB;background:#F9FAFB;transform:translateY(-1px);box-shadow:0 2px 8px rgba(0,0,0,.06)}
.ocr-hist-btn i{font-size:.9rem;color:#9CA3AF}

.ocr-controls{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;display:flex;gap:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.03),0 4px 16px rgba(0,0,0,.02);margin-bottom:2rem;flex-wrap:wrap}
.ocr-controls__field{flex:1;min-width:200px}
.ocr-controls__field label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;margin-bottom:.4rem}
.ocr-controls__field select{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:.85rem;font-weight:500;color:#374151;background:#fff;padding:.6rem .85rem;transition:border-color .15s,box-shadow .15s;cursor:pointer}
.ocr-controls__field select:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none}

.ocr-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem}
@media(max-width:991.98px){.ocr-grid{grid-template-columns:1fr}}
@media(max-width:575.98px){.ocr-controls{flex-direction:column;align-items:stretch}.ocr-container{padding:0 1rem}}

.ocr-card{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;transition:transform .2s,box-shadow .2s;display:flex;align-items:center;gap:1rem;overflow:hidden}
.ocr-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.ocr-card__icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF2F2,#FEE2E2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ocr-card__icon i{font-size:1.15rem;color:#E30613}
.ocr-card__info{flex:1;min-width:0}
.ocr-card__name{font-size:.95rem;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ocr-card__badge{display:inline-flex;align-items:center;font-size:.7rem;font-weight:600;background:#F3F4F6;color:#6B7280;border-radius:6px;padding:.15rem .5rem;margin-top:.25rem;border:1px solid #F0F0F0}
.ocr-card__add{width:38px;height:38px;border-radius:50%;border:2px dashed #D1D5DB;background:transparent;color:#9CA3AF;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0;font-size:1.1rem}
.ocr-card__add:hover{border-color:#E30613;color:#E30613;background:#FEF2F2;transform:scale(1.1)}

.ocr-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.7rem;font-weight:600}
.ocr-badge--pontos{background:#FEE2E2;color:#991B1B}

.ocr-modal .modal-content{border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.ocr-modal .modal-header{border:none;padding:1.25rem 1.5rem .5rem}
.ocr-modal .modal-title{font-size:1.05rem;font-weight:700}
.ocr-modal .modal-body{padding:.5rem 1.5rem 1.25rem}

.ocr-mobile{padding-top:5.5rem;padding-bottom:6rem}
.ocr-mobile .ocr-card{padding:1rem}
.ocr-mobile .ocr-controls{margin:0 1rem 1.5rem}
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'ocorrencias';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none ocr-mobile">
    <div class="px-3 mt-3">
        <a href="./dashboard.php" id="btnVoltarOcrMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#ed1c24;border-radius:6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcrMob">Interclasse</span>
        </a>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="ocr-header__title">Ocorrências</h1>
                <p class="ocr-header__sub">Registre ocorrências e descontos de pontos por turma.</p>
            </div>
            <button type="button" class="ocr-hist-btn" data-bs-toggle="modal" data-bs-target="#modalHistoricoOcorrencias" onclick="carregarHistorico()">
                <i class="bi bi-clock-history"></i> Histórico
            </button>
        </div>

        <div class="ocr-controls">
            <div class="ocr-controls__field">
                <label>Filtrar por Categoria</label>
                <select id="filtroCategoriaMob">
                    <option value="">Todas as Categorias</option>
                </select>
            </div>
        </div>
    </div>

    <div class="px-3">
        <div class="ocr-grid" id="listaOcorrenciasMobile" style="grid-template-columns:1fr;">
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout ocr-page">
    <div class="ocr-container">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarOcr" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#ed1c24;border-radius:6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcr">Interclasse</span>
            </a>
        </div>

        <div class="ocr-header">
            <div class="ocr-header__top">
                <div>
                    <h1 class="ocr-header__title">Ocorrências</h1>
                    <p class="ocr-header__sub">Registre ocorrências e descontos de pontos por turma.</p>
                </div>
                <button type="button" class="ocr-hist-btn" data-bs-toggle="modal" data-bs-target="#modalHistoricoOcorrencias" onclick="carregarHistorico()">
                    <i class="bi bi-clock-history"></i> Histórico
                </button>
            </div>
        </div>

        <div class="ocr-controls">
            <div class="ocr-controls__field">
                <label>Filtrar por Categoria</label>
                <select id="filtroCategoriaDesktop">
                    <option value="">Todas as Categorias</option>
                </select>
            </div>
        </div>

        <div class="ocr-grid" id="listaOcorrenciasDesktop">
            <div class="text-center text-muted py-5" style="grid-column:1/-1"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<!-- Modal Nova Ocorrência -->
<div class="modal fade ocr-modal" id="modalNovaOcorrencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Nova Ocorrência - <span id="modalTurmaNome"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Título</label>
                    <input type="text" class="form-control" id="ocrTituloModal" placeholder="Ex: Conduta antidesportiva" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Pontos a descontar</label>
                    <input type="number" min="0" class="form-control" id="ocrPontosModal" placeholder="0" style="border-radius:10px;">
                </div>
                <div id="msgOcrModal" class="mt-3" style="font-size:.85rem;"></div>
            </div>
            <div class="modal-footer" style="border:none;padding:0 1.5rem 1.25rem;">
                <button type="button" class="ocr-btn-cancel" data-bs-dismiss="modal" style="border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;border-radius:10px;padding:.6rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;">Cancelar</button>
                <button type="button" class="ocr-btn-primary" id="btnSalvarOcrModal" onclick="salvarOcorrenciaModal()" style="background:#E30613;border:none;color:#fff;border-radius:10px;padding:.6rem 1.5rem;font-size:.85rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(227,6,19,.25);">
                    <i class="bi bi-check-lg me-1"></i>Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Histórico -->
<div class="modal fade ocr-modal" id="modalHistoricoOcorrencias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Histórico de Ocorrências</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="historicoConteudo" class="text-center text-muted py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>

<script>
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    const params = new URLSearchParams(window.location.search);
    let idInterclasse = params.get('id');
    let todasTurmas = [];
    let todasCategorias = [];
    let historicoRegistros = [];
    let modalTurmaId = null;

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo.');
            window.location.href = 'home.php';
            return;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        ['nomeInterclasseOcr', 'nomeInterclasseOcrMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = dados?.nome_interclasse || 'Interclasse';
        });
        ['btnVoltarOcr', 'btnVoltarOcrMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
    }

    const API_BASE = window.location.pathname.replace(/\/views\/src\/pages\/.*$/, '/api');

    async function carregarDados() {
        await resolverInterclasse();

        if (!idInterclasse) return;

        const resTurmas = await fetch(`${API_BASE}/turmas.php?id_interclasse=${idInterclasse}`);
        if (!resTurmas.ok) {
            alert('Erro ao carregar turmas.');
            return;
        }
        todasTurmas = await resTurmas.json();

        const catsMap = {};
        todasTurmas.forEach(t => {
            if (t.categorias_id_categoria && t.nome_categoria) {
                catsMap[t.categorias_id_categoria] = t.nome_categoria;
            }
        });
        todasCategorias = Object.entries(catsMap).map(([id, nome]) => ({ id_categoria: Number(id), nome_categoria: nome }));

        const selects = ['filtroCategoriaDesktop', 'filtroCategoriaMob'];
        selects.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = '<option value="">Todas as Categorias</option>';
            todasCategorias.forEach(c => {
                el.innerHTML += `<option value="${c.id_categoria}">${esc(c.nome_categoria)}</option>`;
            });
        });

        carregarLista();
    }

    function turmasFiltradas() {
        const idCatDesk = document.getElementById('filtroCategoriaDesktop').value;
        const idCatMob = document.getElementById('filtroCategoriaMob').value;
        const idCat = idCatDesk || idCatMob;
        if (!idCat) return todasTurmas;
        return todasTurmas.filter(t => String(t.categorias_id_categoria) === String(idCat));
    }

    function carregarLista() {
        function renderCard(turma) {
            return `
                <div class="ocr-card">
                    <div class="ocr-card__icon"><i class="bi bi-people-fill"></i></div>
                    <div class="ocr-card__info">
                        <p class="ocr-card__name">${esc(turma.nome_fantasia_turma || turma.nome_turma)}</p>
                        <span class="ocr-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                    </div>
                    <button class="ocr-card__add" onclick="abrirModalOcorrencia(${turma.id_turma}, '${esc(turma.nome_fantasia_turma || turma.nome_turma)}')" title="Adicionar ocorrência">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>`;
        }

        const listaDesk = document.getElementById('listaOcorrenciasDesktop');
        const listaMob = document.getElementById('listaOcorrenciasMobile');
        const turmas = turmasFiltradas();

        if (turmas.length === 0) {
            const msg = '<div class="text-center text-muted py-5" style="grid-column:1/-1"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#D1D5DB"></i>Nenhuma turma encontrada.</div>';
            listaDesk.innerHTML = msg;
            listaMob.innerHTML = msg;
        } else {
            listaDesk.innerHTML = turmas.map(renderCard).join('');
            listaMob.innerHTML = turmas.map(renderCard).join('');
        }
    }

    function abrirModalOcorrencia(idTurma, nomeTurma) {
        modalTurmaId = idTurma;
        document.getElementById('modalTurmaNome').innerText = nomeTurma;
        document.getElementById('ocrTituloModal').value = '';
        document.getElementById('ocrPontosModal').value = '';
        document.getElementById('msgOcrModal').innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('modalNovaOcorrencia'));
        modal.show();
    }

    async function salvarOcorrenciaModal() {
        const titulo = document.getElementById('ocrTituloModal').value.trim();
        const pontos = parseInt(document.getElementById('ocrPontosModal').value) || 0;
        const data = new Date().toISOString().split('T')[0];
        const msgEl = document.getElementById('msgOcrModal');
        const btnEl = document.getElementById('btnSalvarOcrModal');

        if (!titulo || !modalTurmaId) {
            msgEl.innerHTML = '<span style="color:#dc2626;font-weight:700;">Preencha o título.</span>';
            return;
        }

        btnEl.disabled = true;
        const originalText = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

        try {
            const resp = await fetch(`${API_BASE}/ocorrencias_turmas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    turmas_id_turma: Number(modalTurmaId),
                    interclasses_id_interclasse: Number(idInterclasse),
                    titulo_ocorrencia: titulo,
                    pontos_descontados: pontos,
                    data_ocorrencia: data
                })
            });
            const result = await resp.json();

            if (result.success) {
                msgEl.innerHTML = '<span style="color:#16a34a;font-weight:700;">Ocorrência registrada!</span>';
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaOcorrencia'));
                    if (modal) modal.hide();
                    msgEl.innerHTML = '';
                }, 1000);
            } else {
                msgEl.innerHTML = '<span style="color:#dc2626;font-weight:700;">' + esc(result.message || 'Erro ao salvar.') + '</span>';
            }
        } catch (e) {
            msgEl.innerHTML = '<span style="color:#dc2626;font-weight:700;">Erro de conexão.</span>';
        } finally {
            btnEl.disabled = false;
            btnEl.innerHTML = originalText;
        }
    }

    async function carregarHistorico() {
        const conteudo = document.getElementById('historicoConteudo');
        conteudo.innerHTML = '<div class="spinner-border text-danger" role="status"></div>';

        try {
            if (!idInterclasse) return;
            const res = await fetch(`${API_BASE}/ocorrencias_turmas.php?id_interclasse=${idInterclasse}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros) || historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhuma ocorrência registrada.</p>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
            html += '<thead><tr class="table-light"><th>Data</th><th>Turma</th><th>Título</th><th>Descrição</th><th class="text-center">Pontos</th><th class="text-center">Ação</th></tr></thead><tbody>';

            historicoRegistros.forEach(r => {
                html += '<tr>';
                html += '<td class="small text-muted">' + esc(r.data_ocorrencia) + '</td>';
                html += '<td class="fw-semibold">' + esc(r.nome_fantasia_turma || r.nome_turma) + '</td>';
                html += '<td>' + esc(r.titulo_ocorrencia) + '</td>';
                html += '<td class="small text-muted">' + esc(r.descricao_ocorrencia || '-') + '</td>';
                html += '<td class="text-center"><span class="ocr-badge ocr-badge--pontos">-' + r.pontos_descontados + ' pts</span></td>';
                html += '<td class="text-center">';
                html += '<button class="btn btn-outline-danger btn-sm" title="Remover" onclick="removerOcorrencia(' + r.id_ocorrencia_turma + ')"><i class="bi bi-trash"></i></button>';
                html += '</td></tr>';
            });

            html += '</tbody></table></div>';
            conteudo.innerHTML = html;
        } catch (e) {
            conteudo.innerHTML = '<p class="text-danger">Erro ao carregar histórico.</p>';
        }
    }

    async function removerOcorrencia(id) {
        if (!confirm('Tem certeza que deseja remover esta ocorrência?')) return;

        try {
            const res = await fetch(`${API_BASE}/ocorrencias_turmas.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_ocorrencia_turma: id })
            });
            const result = await res.json();
            if (result.success) {
                alert('Ocorrência removida!');
                carregarHistorico();
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (e) {
            alert('Erro de conexão.');
        }
    }

    document.getElementById('filtroCategoriaDesktop').addEventListener('change', carregarLista);
    document.getElementById('filtroCategoriaMob').addEventListener('change', carregarLista);

    window.addEventListener('load', carregarDados);
</script>
