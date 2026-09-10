<?php
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none mb-5" >
    <div class="container mt-3">
        <a href="#" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" id="btnVoltarEquipesMobile" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosMob">Interclasse</span>
        </a>
        <div id="listaAlunosMobile" class="row row-cols-1 row-cols-sm-2 g-3">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
        <button id="btnSalvarAlunosMobile" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-lg"></i></button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid py-4 px-4 text-body">
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-2">
            <a href="#" id="btnVoltarEquipesDesktop" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosDesk">Interclasse</span>
            </a>
            <h1 class="h4 mb-0 fw-bold">Adicionar alunos à equipe</h1>
            <div class="ms-auto d-flex gap-2">
                <button id="btnSalvarAlunosDesktop" class="btn btn-primary"><i class="bi bi-check-lg"></i></button>
            </div>
        </div>

        <div id="listaAlunosDesktop" class="row row-cols-1 row-cols-lg-2 g-3">
            <div class="text-center py-4 text-body-secondary">Carregando alunos...</div>
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="competicoes/equipe-alunos"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/equipe-alunos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
