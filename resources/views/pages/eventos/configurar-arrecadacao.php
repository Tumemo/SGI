<?php
$tituloPagina = 'SGI - Arrecadação';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'arrecadacoes';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none ocr-mobile">
    <div class="px-3 mt-3">
        <a href="./dashboard.php" id="btnVoltarArrecadacaoMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacaoMob">Interclasse</span>
        </a>

        <div class="mb-4">
            <h1 class="ocr-header__title">Arrecadações</h1>
            <p class="ocr-header__sub">Registre as arrecadações das turmas por categoria.</p>
        </div>
    </div>

    <div class="px-3">
        <div class="ocr-grid sgi-inline-98d53286" id="listaArrecadacaoMobile" >
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout ocr-page">
    <div class="ocr-container">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>
        </div>

        <div class="ocr-header">
            <div class="ocr-header__top">
                <div>
                    <h1 class="ocr-header__title">Arrecadações</h1>
                    <p class="ocr-header__sub">Registre as arrecadações das turmas por categoria.</p>
                </div>
            </div>
        </div>

        <div class="ocr-grid" id="listaArrecadacaoDesktop">
            <div class="text-center text-muted py-5 sgi-inline-c5f53f82" ><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>

<div class="modal fade ocr-modal" id="modalHistoricoArrecadacao" tabindex="-1" aria-labelledby="modalHistoricoArrecadacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalHistoricoArrecadacaoLabel">
                    <i class="bi bi-clock-history me-2"></i>Histórico de Arrecadações - <span id="modalHistoricoTurmaNome"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php if ($isAdmin): ?>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="btnFiltroAdicionados" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold active sgi-inline-882a3ecc"  onclick="filtrarHistorico('adicionados')">
                        <i class="bi bi-plus-circle me-1"></i>Adicionados
                    </button>
                    <button type="button" id="btnFiltroExcluidos" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold sgi-inline-dc96eb94"  onclick="filtrarHistorico('excluidos')">
                        <i class="bi bi-trash me-1"></i>Excluídos
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

<script type="application/json" data-sgi-config="eventos/configurar-arrecadacao"><?= json_encode(['value0' => ($isAdmin), 'value2' => (bool) $isAdmin], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-arrecadacao.js') ?>"></script>
