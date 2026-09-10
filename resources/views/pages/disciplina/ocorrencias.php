<?php
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'ocorrencias';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none pt-5 pb-5">
    <div class="px-3 mt-3">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarOcrMob" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcrMob">Interclasse</span>
        </a>

        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Ocorrências</h1>
            <p class="text-body-secondary mb-0">Registre ocorrências e descontos de pontos por turma.</p>
        </div>
    </div>

    <div class="px-3">
        <div class="row row-cols-1 g-3" id="listaOcorrenciasMobile">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout pb-5">
    <div class="container-fluid px-4">
        <div class="mb-4">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarOcr" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcr">Interclasse</span>
            </a>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h1 class="h3 fw-bold mb-1">Ocorrências</h1>
                    <p class="text-body-secondary mb-0">Registre ocorrências e descontos de pontos por turma.</p>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-lg-2 g-3" id="listaOcorrenciasDesktop">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando...</div>
        </div>
    </div>
</main>

<!-- Modal Nova Ocorrência -->
<div class="modal fade" id="modalNovaOcorrencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Nova Ocorrência - <span id="modalTurmaNome"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary" >Título</label>
                    <input type="text" class="form-control rounded-3" id="ocrTituloModal" placeholder="Ex: Conduta antidesportiva" >
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary" >Pontos a descontar</label>
                    <input type="number" min="0" class="form-control rounded-3" id="ocrPontosModal" placeholder="0" >
                </div>
                <div id="msgOcrModal" class="mt-3 small" ></div>
            </div>
                  <div class="modal-footer border-0 px-4 pb-3" >
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarOcrModal" onclick="salvarOcorrenciaModal()" >
                    <i class="bi bi-check-lg me-1"></i>Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Histórico -->
<div class="modal fade" id="modalHistoricoOcorrencias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Histórico de Ocorrências - <span id="modalHistoricoTurmaNome"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="historicoConteudo" class="text-center text-muted py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>

<script type="application/json" data-sgi-config="disciplina/ocorrencias"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/disciplina/ocorrencias.js') ?>"></script>
