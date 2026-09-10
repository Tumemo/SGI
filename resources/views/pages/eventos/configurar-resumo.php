<?php
$titulo = 'Resumo da Edição';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="mt-4 d-flex justify-content-center align-items-center flex-column position-relative d-md-none sgi-u-mb-120px" >
    <a href="<?= \App\Shared\Http\Url::to('edicoes/modalidades') ?>" id="linkEditarModalidadesMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 sgi-u-w-90-min-height-90px" >
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Modalidades</h2>
                <p class="text-secondary m-0 mt-1 text-truncate sgi-u-text-14px" id="resumoModalidadesMobile" >(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkEditarRegulamentosMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 sgi-u-w-90-min-height-90px" >
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Pontuação</h2>
                <p class="text-secondary m-0 mt-1 text-truncate sgi-u-text-14px" id="resumoRegulamentosMobile" >(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 sgi-u-w-90-min-height-90px" >
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Categorias</h2>
                <p class="text-secondary m-0 mt-1 text-truncate sgi-u-text-14px" id="resumoCategoriasMobile" >(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkEditarTurmasMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 sgi-u-w-90-min-height-90px" >
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Turmas</h2>
                <p class="text-secondary m-0 mt-1 text-truncate sgi-u-text-14px" id="resumoTurmasMobile" >(Carregando...)</p>
            </div>
        </div>
    </a>

    <section class="d-flex gap-4 mt-3 position-fixed translate-middle sgi-u-w-max-content-top-85-left-50" >
        <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="btnVoltarMobile" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseResumoMob">Interclasse</span>
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">Adicionar categoria</button>
    </section>
</main>


<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0 sgi-u-maxw-80" >

        <div class="mb-5">
            <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="btnVoltarResumoTopo" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseResumo">Interclasse</span>
            </a>
        </div>

        <div class="d-flex flex-column gap-3 mb-5">
            <a href="<?= \App\Shared\Http\Url::to('edicoes/modalidades') ?>" id="linkEditarModalidadesDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center sgi-u-cursor-pointer-transition-0-2s-ease-min-height-100px"  onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Modalidades</h6>
                    <span class="text-secondary text-truncate sgi-u-text-0-95rem" id="resumoModalidadesDesktop" >
                        (Carregando...)
                    </span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkEditarRegulamentosDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center sgi-u-cursor-pointer-transition-0-2s-ease-min-height-100px"  onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Regulamento</h6>
                    <span class="text-secondary text-truncate sgi-u-text-0-95rem" id="resumoRegulamentosDesktop" >
                        (Carregando...)
                    </span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center sgi-u-cursor-pointer-transition-0-2s-ease-min-height-100px"  onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Categorias</h6>
                    <span class="text-secondary text-truncate sgi-u-text-0-95rem" id="resumoCategoriasDesktop" >(Carregando...)</span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkEditarTurmasDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center sgi-u-cursor-pointer-transition-0-2s-ease-min-height-100px"  onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Turmas</h6>
                    <span class="text-secondary text-truncate sgi-u-text-0-95rem" id="resumoTurmasDesktop" >(Carregando...)</span>
                </div>
            </a>
        </div>
    </div>

    <div class="d-none d-md-block fixed-bottom sgi-u-background-linear-gradient-to-top-248-249-250-1-70-248-249-250-0-100-p-30px-0" >
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3 sgi-u-maxw-80" >
            <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="btnVoltarDesktop" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseResumoDesk">Interclasse</span>
            </a>
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" class="text-decoration-none" id="btnCriarInterclasseFinal">
                <button class="btn btn-primary fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Criar interclasse
                </button>
            </a>
        </div>
    </div>
</main>



<script type="application/json" data-sgi-config="eventos/configurar-resumo"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-resumo.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
