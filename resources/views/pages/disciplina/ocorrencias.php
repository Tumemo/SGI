<?php
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'ocorrencias';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none ocr-mobile">
    <div class="px-3 mt-3">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarOcrMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-u-bg-E30613-radius-6px-p-8px-16px" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcrMob">Interclasse</span>
        </a>

        <div class="mb-4">
            <h1 class="ocr-header__title">Ocorrências</h1>
            <p class="ocr-header__sub">Registre ocorrências e descontos de pontos por turma.</p>
        </div>
    </div>

    <div class="px-3">
        <div class="ocr-grid sgi-u-grid-1fr" id="listaOcorrenciasMobile" >
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout ocr-page">
    <div class="ocr-container">
        <div class="mb-4">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarOcr" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-u-bg-E30613-radius-6px-p-8px-16px" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseOcr">Interclasse</span>
            </a>
        </div>

        <div class="ocr-header">
            <div class="ocr-header__top">
                <div>
                    <h1 class="ocr-header__title">Ocorrências</h1>
                    <p class="ocr-header__sub">Registre ocorrências e descontos de pontos por turma.</p>
                </div>
            </div>
        </div>

        <div class="ocr-grid" id="listaOcorrenciasDesktop">
            <div class="text-center text-muted py-5 sgi-u-col-1-1" ><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
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
                    <label class="form-label fw-semibold sgi-u-text-78rem-color-6B7280" >Título</label>
                    <input type="text" class="form-control sgi-u-radius-10px" id="ocrTituloModal" placeholder="Ex: Conduta antidesportiva" >
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold sgi-u-text-78rem-color-6B7280" >Pontos a descontar</label>
                    <input type="number" min="0" class="form-control sgi-u-radius-10px" id="ocrPontosModal" placeholder="0" >
                </div>
                <div id="msgOcrModal" class="mt-3 sgi-u-text-85rem" ></div>
            </div>
            <div class="modal-footer sgi-u-border-none-p-0-1-5rem-1-25rem" >
                <button type="button" class="ocr-btn-cancel sgi-u-border-1-5px-solid-E5E7EB-background-fff-color-6B7280" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="ocr-btn-primary sgi-u-background-E30613-border-none-color-fff" id="btnSalvarOcrModal" onclick="salvarOcorrenciaModal()" >
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
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Histórico de Ocorrências - <span id="modalHistoricoTurmaNome"></span></h5>
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

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>

<script type="application/json" data-sgi-config="disciplina/ocorrencias"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/disciplina/ocorrencias.js') ?>"></script>
