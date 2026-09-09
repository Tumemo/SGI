<?php
$tituloPagina = 'SGI - Mesário - Jogos';
$titulo = 'Jogos';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>



<main class="container py-4 main-desktop-layout">

    <div class="row g-3" id="listaJogos">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando jogos...
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="competicoes/jogos"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/jogos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
