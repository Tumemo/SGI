<?php
$titulo = 'Arrecadações';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
$idVoltarMobile = 'btnVoltarArrecadacaoMob';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'arrecadacoes';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none pt-5 pb-5 sgi-arrecadacao-mobile sgi-u-min-width-0">
    <div class="px-3 mt-3">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Arrecadações</h1>
            <p class="text-body-secondary mb-0">Registre as arrecadações das turmas por categoria.</p>
        </div>
    </div>

    <div class="px-3">
        <div class="row row-cols-1 g-3" id="listaArrecadacaoMobile">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout pb-5">
    <div class="container-fluid px-4">
        <div class="mb-4">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarArrecadacao" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
            <i class="bi bi-arrow-left-circle fs-5" aria-hidden="true"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h1 class="h3 fw-bold mb-1">Arrecadações</h1>
                    <p class="text-body-secondary mb-0">Registre as arrecadações das turmas por categoria.</p>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-lg-2 g-3" id="listaArrecadacaoDesktop">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando...</div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalHistoricoArrecadacao" tabindex="-1" aria-labelledby="modalHistoricoArrecadacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalHistoricoArrecadacaoLabel">
                    <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Histórico de arrecadações — <span id="modalHistoricoTurmaNome"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar histórico de arrecadações"></button>
            </div>
            <div class="modal-body pt-0">
                <?php if ($isAdmin): ?>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="btnFiltroAdicionados" class="btn btn-sm btn-primary rounded-3 px-3 py-1 fw-semibold active" aria-pressed="true" data-filtro-historico="adicionados">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Adicionados
                    </button>
                    <button type="button" id="btnFiltroExcluidos" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1 fw-semibold" aria-pressed="false" data-filtro-historico="excluidos">
                        <i class="bi bi-trash me-1" aria-hidden="true"></i>Excluídos
                    </button>
                </div>
                <?php endif; ?>
                <div id="historicoConteudo" class="text-center text-muted py-4">
                    <div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; ?>

<script type="application/json" data-sgi-config="eventos/configurar-arrecadacao"><?= json_encode(['value0' => ($isAdmin), 'value2' => (bool) $isAdmin], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-arrecadacao.js') ?>"></script>

<?php require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>
