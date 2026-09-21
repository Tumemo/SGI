<?php
$titulo = 'Jogos';
$tagTituloCompacto = 'h1';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>



<main class="container py-4 main-desktop-layout sgi-jogos-lista sgi-u-min-width-0">
    <?php
    $headerIdVoltar = 'btnVoltarJogosDesk';
    $headerClasse = 'd-none d-md-flex';
    $headerClassBotao = 'd-none d-md-inline-flex';
    $headerCorpoHtml = '<h1 class="h3 fw-bold text-body m-0 d-none d-md-block">Jogos</h1>';
    include SGI_ROOT . '/resources/views/components/page-header.php';
    unset($headerMostrarVoltar, $headerCorpoHtml, $headerAcoesHtml, $headerClasse, $headerUrlVoltar, $headerIdVoltar, $headerClassBotao, $headerHiddenBotao);
    ?>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" id="listaJogos">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando jogos...
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="competicoes/jogos"><?= json_encode(['placarUrl' => \App\Shared\Http\Url::to('jogos/placar')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/jogos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
