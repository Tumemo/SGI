<?php
$tituloPagina = 'SGI - Ocorrências';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
/* ═══ Ocorrências ═══ */
.ocr-page{padding-bottom:5rem}
.ocr-container{width:100%;padding:0 2rem}
.ocr-header{margin-bottom:2rem}
.ocr-header__top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ocr-header__title{font-size:1.75rem;font-weight:800;color:#111827;letter-spacing:-.03em;margin:0;line-height:1.2}
.ocr-header__sub{font-size:.9rem;color:#6B7280;margin:.35rem 0 0;font-weight:400}
.ocr-hist-btn{display:inline-flex;align-items:center;gap:.45rem;border:1.5px solid #E5E7EB;background:#fff;color:#374151;border-radius:10px;padding:.5rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s}
.ocr-hist-btn:hover{border-color:#D1D5DB;background:#F9FAFB;transform:translateY(-1px);box-shadow:0 2px 8px rgba(0,0,0,.06)}
.ocr-hist-btn i{font-size:.9rem;color:#9CA3AF}

/* Controls */
.ocr-controls{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.03),0 4px 16px rgba(0,0,0,.02);margin-bottom:2rem;flex-wrap:wrap}
.ocr-controls__field{flex:1;min-width:200px}
.ocr-controls__field label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;margin-bottom:.4rem}
.ocr-controls__field select,.ocr-controls__field input{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:.85rem;font-weight:500;color:#374151;background:#fff;padding:.6rem .85rem;transition:border-color .15s,box-shadow .15s;cursor:pointer;appearance:auto}
.ocr-controls__field select:focus,.ocr-controls__field input:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none}
.ocr-controls__actions{display:flex;gap:.6rem;flex-shrink:0}
.ocr-btn-cancel{border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;border-radius:10px;padding:.6rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .15s}
.ocr-btn-cancel:hover{border-color:#D1D5DB;background:#F9FAFB;color:#374151}
.ocr-btn-primary{background:#E30613;border:none;color:#fff;border-radius:10px;padding:.6rem 1.5rem;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(227,6,19,.25)}
.ocr-btn-primary:hover{background:#C50510;transform:translateY(-1px);box-shadow:0 4px 14px rgba(227,6,19,.35)}

/* Cards grid */
.ocr-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem}
@media(max-width:991.98px){.ocr-grid{grid-template-columns:1fr}}
@media(max-width:575.98px){.ocr-controls{flex-direction:column;align-items:stretch}.ocr-controls__actions{justify-content:flex-end}.ocr-container{padding:0 1rem}}

/* Turma card */
.ocr-card{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;transition:transform .2s,box-shadow .2s;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden}
.ocr-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.ocr-card__icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF2F2,#FEE2E2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ocr-card__icon i{font-size:1.15rem;color:#E30613}
.ocr-card__info{flex:1;min-width:0}
.ocr-card__name{font-size:.95rem;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ocr-card__badge{display:inline-flex;align-items:center;font-size:.7rem;font-weight:600;background:#F3F4F6;color:#6B7280;border-radius:6px;padding:.15rem .5rem;margin-top:.25rem;border:1px solid #F0F0F0}
.ocr-card__input-wrap{flex-shrink:0;display:flex;gap:.5rem;align-items:center}
.ocr-card__input{width:80px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:1rem;font-weight:700;color:#111827;text-align:center;padding:.55rem .5rem;transition:all .15s;background:#FAFAFA}
.ocr-card__input:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none;background:#fff}
.ocr-card__input::placeholder{color:#D1D5DB;font-weight:400}
.ocr-card__submit{background:#E30613;border:none;color:#fff;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap}
.ocr-card__submit:hover{background:#C50510}
.ocr-card__submit:disabled{background:#9CA3AF;cursor:not-allowed}

/* History table */
.ocr-table-card{background:#fff;border:1px solid #F0F0F0;border-radius:16px;overflow:hidden;margin-bottom:2rem;box-shadow:0 1px 3px rgba(0,0,0,.03)}
.ocr-table-card__header{padding:1.25rem 1.5rem;border-bottom:1px solid #F0F0F0;display:flex;align-items:center;justify-content:space-between}
.ocr-table-card__title{font-size:1rem;font-weight:700;color:#111827}
.ocr-table-card .table{margin-bottom:0}
.ocr-table-card .table thead th{background:#F9FAFB;border-bottom:1px solid #F0F0F0;font-size:.72rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;padding:.85rem 1rem}
.ocr-table-card .table tbody td{padding:.85rem 1rem;font-size:.85rem;color:#374151;border-bottom:1px solid #F8F9FA}
.ocr-table-card .table tbody tr:hover{background:#F9FAFB}

.ocr-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.7rem;font-weight:600}
.ocr-badge--pontos{background:#FEE2E2;color:#991B1B}

/* Modal */
.ocr-modal .modal-content{border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.ocr-modal .modal-header{border:none;padding:1.25rem 1.5rem .5rem}
.ocr-modal .modal-title{font-size:1.05rem;font-weight:700}
.ocr-modal .modal-body{padding:.5rem 1.5rem 1.25rem}

/* Mobile */
.ocr-mobile{padding-top:5.5rem;padding-bottom:6rem}
.ocr-mobile .ocr-card{padding:1rem}
.ocr-mobile .ocr-card__input-wrap{flex-direction:column;align-items:stretch}
.ocr-mobile .ocr-card__input{width:100%}
.ocr-mobile .ocr-btn-primary{width:100%;padding:.7rem;font-size:.9rem}
.ocr-mobile .ocr-controls{margin:0 1rem 1.5rem}
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'ocorrencias';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<!-- ═══ MOBILE ═══ -->
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
                <label>Modalidade</label>
                <select id="filtroModalidadeMob">
                    <option value="">Todas as Modalidades</option>
                </select>
            </div>
        </div>
    </div>

    <div class="px-3">
        <div id="formOcorrenciaMob" class="d-none mb-3">
            <div style="background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.03);">
                <h6 style="font-weight:700;color:#111827;margin-bottom:1rem;"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Nova Ocorrência</h6>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Turma</label>
                    <select class="form-select" id="ocrTurmaMob" style="border-radius:10px;font-size:.85rem;">
                        <option value="">Selecione a turma</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Título</label>
                    <input type="text" class="form-control" id="ocrTituloMob" placeholder="Ex: Conduta antidesportiva" style="border-radius:10px;font-size:.85rem;">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Descrição</label>
                    <textarea class="form-control" id="ocrDescricaoMob" rows="2" placeholder="Descreva a ocorrência..." style="border-radius:10px;font-size:.85rem;"></textarea>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <div style="flex:1;">
                        <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Pontos a descontar</label>
                        <input type="number" min="0" class="form-control" id="ocrPontosMob" placeholder="0" style="border-radius:10px;font-size:.85rem;">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Data</label>
                        <input type="date" class="form-control" id="ocrDataMob" style="border-radius:10px;font-size:.85rem;">
                    </div>
                </div>
                <div id="msgOcrMob" class="mb-2" style="font-size:.82rem;"></div>
                <button type="button" class="ocr-btn-primary" id="btnSalvarOcrMob" onclick="salvarOcorrencia('mob')">
                    <i class="bi bi-check-lg me-1"></i>Registrar Ocorrência
                </button>
            </div>
        </div>
        <div class="ocr-grid" id="listaOcorrenciasMobile" style="grid-template-columns:1fr;">
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
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
                <label>Filtrar por Modalidade</label>
                <select id="filtroModalidadeDesktop">
                    <option value="">Todas as Modalidades</option>
                </select>
            </div>
            <div class="ocr-controls__actions">
                <button type="button" onclick="window.location.reload()" class="ocr-btn-cancel">Cancelar</button>
                <button type="button" class="ocr-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaOcorrencia">
                    <i class="bi bi-plus-lg me-1"></i>Nova Ocorrência
                </button>
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
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Nova Ocorrência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Modalidade</label>
                    <select class="form-select" id="ocrModalidade" style="border-radius:10px;">
                        <option value="">Selecione a modalidade</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Turma</label>
                    <select class="form-select" id="ocrTurma" style="border-radius:10px;">
                        <option value="">Selecione a turma</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Título</label>
                    <input type="text" class="form-control" id="ocrTitulo" placeholder="Ex: Conduta antidesportiva" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Descrição</label>
                    <textarea class="form-control" id="ocrDescricao" rows="3" placeholder="Descreva a ocorrência..." style="border-radius:10px;"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Pontos a descontar</label>
                        <input type="number" min="0" class="form-control" id="ocrPontos" placeholder="0" style="border-radius:10px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:.78rem;color:#6B7280;">Data</label>
                        <input type="date" class="form-control" id="ocrData" style="border-radius:10px;">
                    </div>
                </div>
                <div id="msgOcrDesk" class="mt-3" style="font-size:.85rem;"></div>
            </div>
            <div class="modal-footer" style="border:none;padding:0 1.5rem 1.25rem;">
                <button type="button" class="ocr-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="ocr-btn-primary" id="btnSalvarOcrDesk" onclick="salvarOcorrencia('desk')">
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
    let todasModalidades = [];
    let todasTurmas = [];
    let historicoRegistros = [];

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

    async function carregarDados() {
        await resolverInterclasse();

        const [resMod, resTurmas] = await Promise.all([
            fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`),
            fetch(`../../../api/turmas.php?id_interclasse=${idInterclasse}`)
        ]);
        todasModalidades = await resMod.json();
        todasTurmas = await resTurmas.json();

        const selects = ['filtroModalidadeDesktop', 'filtroModalidadeMob', 'ocrModalidade'];
        selects.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = id === 'ocrModalidade' ? '<option value="">Selecione a modalidade</option>' : '<option value="">Todas as Modalidades</option>';
            todasModalidades.forEach(m => {
                const gen = m.genero_modalidade ? ` (${m.genero_modalidade})` : '';
                el.innerHTML += `<option value="${m.id_modalidade}">${esc(m.nome_modalidade)}${gen}</option>`;
            });
        });

        const dataInput = document.getElementById('ocrData');
        const dataInputMob = document.getElementById('ocrDataMob');
        const hoje = new Date().toISOString().split('T')[0];
        if (dataInput) dataInput.value = hoje;
        if (dataInputMob) dataInputMob.value = hoje;

        carregarLista();
    }

    function carregarLista() {
        const filtroModDesk = document.getElementById('filtroModalidadeDesktop').value;
        const filtroModMob = document.getElementById('filtroModalidadeMob').value;

        function filtrarTurmas(filtroMod) {
            if (!filtroMod) return todasTurmas;
            const mod = todasModalidades.find(m => String(m.id_modalidade) === String(filtroMod));
            if (!mod) return todasTurmas;
            return todasTurmas.filter(t => String(t.categorias_id_categoria) === String(mod.categorias_id_categoria));
        }

        function renderCard(turma) {
            return `
                <div class="ocr-card">
                    <div class="ocr-card__icon"><i class="bi bi-people-fill"></i></div>
                    <div class="ocr-card__info">
                        <p class="ocr-card__name">${esc(turma.nome_fantasia_turma || turma.nome_turma)}</p>
                        <span class="ocr-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                    </div>
                </div>`;
        }

        const listaDesk = document.getElementById('listaOcorrenciasDesktop');
        const listaMob = document.getElementById('listaOcorrenciasMobile');
        const turmasDesk = filtrarTurmas(filtroModDesk);
        const turmasMob = filtrarTurmas(filtroModMob);

        if (turmasDesk.length === 0) {
            listaDesk.innerHTML = '<div class="text-center text-muted py-5" style="grid-column:1/-1"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#D1D5DB"></i>Nenhuma turma encontrada.</div>';
        } else {
            listaDesk.innerHTML = turmasDesk.map(renderCard).join('');
        }

        if (turmasMob.length === 0) {
            listaMob.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#D1D5DB"></i>Nenhuma turma encontrada.</div>';
        } else {
            listaMob.innerHTML = turmasMob.map(renderCard).join('');
        }

        popularSelectsTurma();
    }

    function popularSelectsTurma() {
        const filtroModDesk = document.getElementById('filtroModalidadeDesktop').value;
        const filtroModMob = document.getElementById('filtroModalidadeMob').value;

        function turmasFiltradas(filtro) {
            if (!filtro) return todasTurmas;
            const mod = todasModalidades.find(m => String(m.id_modalidade) === String(filtro));
            if (!mod) return todasTurmas;
            return todasTurmas.filter(t => String(t.categorias_id_categoria) === String(mod.categorias_id_categoria));
        }

        const selDesk = document.getElementById('ocrTurma');
        const selMob = document.getElementById('ocrTurmaMob');
        [selDesk, selMob].forEach(sel => {
            if (!sel) return;
            const filtro = sel === selDesk ? filtroModDesk : filtroModMob;
            const turmas = turmasFiltradas(filtro);
            sel.innerHTML = '<option value="">Selecione a turma</option>';
            turmas.forEach(t => {
                sel.innerHTML += `<option value="${t.id_turma}">${esc(t.nome_fantasia_turma || t.nome_turma)}</option>`;
            });
        });
    }

    async function salvarOcorrencia(origem) {
        const isMob = origem === 'mob';
        const titulo = document.getElementById(isMob ? 'ocrTituloMob' : 'ocrTitulo').value.trim();
        const descricao = document.getElementById(isMob ? 'ocrDescricaoMob' : 'ocrDescricao').value.trim();
        const pontos = parseInt(document.getElementById(isMob ? 'ocrPontosMob' : 'ocrPontos').value) || 0;
        const data = document.getElementById(isMob ? 'ocrDataMob' : 'ocrData').value;
        const idTurma = document.getElementById(isMob ? 'ocrTurmaMob' : 'ocrTurma').value;
        const idModalidade = document.getElementById(isMob ? 'filtroModalidadeMob' : 'ocrModalidade').value;
        const msgEl = document.getElementById(isMob ? 'msgOcrMob' : 'msgOcrDesk');
        const btnEl = document.getElementById(isMob ? 'btnSalvarOcrMob' : 'btnSalvarOcrDesk');

        if (!idTurma || !titulo || !data || !idModalidade) {
            msgEl.innerHTML = '<span style="color:#dc2626;font-weight:700;">Preencha todos os campos obrigatórios.</span>';
            return;
        }

        btnEl.disabled = true;
        const originalText = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

        try {
            const resp = await fetch('../../../api/ocorrencias_turmas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    turmas_id_turma: Number(idTurma),
                    modalidades_id_modalidade: Number(idModalidade),
                    interclasses_id_interclasse: Number(idInterclasse),
                    titulo_ocorrencia: titulo,
                    descricao_ocorrencia: descricao,
                    pontos_descontados: pontos,
                    data_ocorrencia: data,
                    usuarios_id_usuario: <?= (int)($_SESSION['id_usuario'] ?? 0) ?>
                })
            });
            const result = await resp.json();

            if (result.success) {
                msgEl.innerHTML = '<span style="color:#16a34a;font-weight:700;">Ocorrência registrada!</span>';
                setTimeout(() => {
                    if (isMob) {
                        document.getElementById('ocrTituloMob').value = '';
                        document.getElementById('ocrDescricaoMob').value = '';
                        document.getElementById('ocrPontosMob').value = '';
                    } else {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaOcorrencia'));
                        if (modal) modal.hide();
                        document.getElementById('ocrTitulo').value = '';
                        document.getElementById('ocrDescricao').value = '';
                        document.getElementById('ocrPontos').value = '';
                    }
                    msgEl.innerHTML = '';
                }, 1200);
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
            const res = await fetch(`../../../api/ocorrencias_turmas.php?id_interclasse=${idInterclasse}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros) || historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhuma ocorrência registrada.</p>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
            html += '<thead><tr class="table-light"><th>Data</th><th>Turma</th><th>Modalidade</th><th>Título</th><th>Descrição</th><th class="text-center">Pontos</th><th class="text-center">Ação</th></tr></thead><tbody>';

            historicoRegistros.forEach(r => {
                html += '<tr>';
                html += '<td class="small text-muted">' + esc(r.data_ocorrencia) + '</td>';
                html += '<td class="fw-semibold">' + esc(r.nome_fantasia_turma || r.nome_turma) + '</td>';
                html += '<td><span class="badge bg-light text-dark border">' + esc(r.nome_modalidade) + '</span></td>';
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
            const res = await fetch('../../../api/ocorrencias_turmas.php', {
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

    document.getElementById('filtroModalidadeDesktop').addEventListener('change', carregarLista);
    document.getElementById('filtroModalidadeMob').addEventListener('change', carregarLista);

    document.getElementById('ocrModalidade').addEventListener('change', popularSelectsTurma);

    window.addEventListener('load', carregarDados);
</script>
