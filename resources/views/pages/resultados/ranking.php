<?php
if (session_status() === PHP_SESSION_NONE) {
    \App\Shared\Http\SessionManager::start();
}

// Resgata o nível buscando principalmente por 'nivel_usuario' da tabela
$nivelRaw = $_SESSION['nivel_usuario'] ?? $_SESSION['nivel'] ?? $_SESSION['usuario_nivel'] ?? $_SESSION['nivel_acesso'] ?? $_SESSION['perfil'] ?? 99;

if (is_numeric($nivelRaw)) {
    $nivelNum = (int)$nivelRaw;
$eAdmin = ($nivelNum === 0 || $nivelNum === 1);
} else {
    $eAdmin = (strtolower((string)$nivelRaw) === 'admin');
}

$titulo = 'Ranking de Turmas';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'ranking';
?>

<!-- ======================== MOBILE ======================== -->
<main class="d-md-none py-3 px-3 mb-5" >
    <div id="msgMob"></div>

    <header class="rk-mobile-header mb-2">
        <div>
            <h1 class="rk-mobile-header__title d-none" id="nomeInterclasse"></h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($eAdmin): ?>
                <button type="button" class="btn btn-sm btn-outline-dark btn-imprimir" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            <?php endif; ?>

            <div class="rk-stat-chip">
                <span>&#x1F465;</span>
                <span id="totalTurmas">0 Turmas</span>
            </div>
        </div>
    </header>

    <div id="filtrosMob" class="rk-filters-mobile"></div>
    <div id="listaMob" class="rk-ranking-list"></div>
</main>

<!-- ======================== DESKTOP ======================== -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-4 py-4">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-3 flex-wrap">
            <div id="filtrosDesk" class="d-flex overflow-auto gap-2"></div>

            <div class="d-flex align-items-center gap-3">
                <?php if ($eAdmin): ?>
                    <button type="button" class="btn btn-outline-dark fw-bold btn-imprimir" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimir Ranking
                    </button>
                <?php endif; ?>

                <div class="rk-stat-chip flex-shrink-0">
                    <span>&#x1F465;</span>
                    <span id="totalTurmasDesk">0 Turmas</span>
                </div>
            </div>
        </div>

        <div id="msgDesk"></div>
        <div id="listaDesk" class="d-flex flex-column gap-3"></div>
    </div>
</main>

<!-- Modal: histórico de pontuações da turma -->
<div class="modal fade" id="modalHistoricoTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4" >
            <div class="modal-header border-0 pb-0 px-4 pt-3">
                <h5 class="modal-title fw-bold" id="htrTitulo">Histórico de Pontos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body htr-modal-body" id="htrCorpo">
                <div class="text-center py-5"><div class="spinner-border text-danger"></div></div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="resultados/ranking"><?= json_encode(['value2' => (bool) $eAdmin], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/resultados/ranking.js') ?>"></script>

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>
