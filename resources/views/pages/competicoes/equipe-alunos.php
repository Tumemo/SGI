<?php
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none sgi-u-mb-120px" >
    <div class="container mt-3">
        <a href="#" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" id="btnVoltarEquipesMobile" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosMob">Interclasse</span>
        </a>
        <div id="listaAlunosMobile" class="sgi-u-display-grid-grid-repeat-2-1fr-gap-0-75rem">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
        <button id="btnSalvarAlunosMobile" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-lg"></i></button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <a href="#" id="btnVoltarEquipesDesktop" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosDesk">Interclasse</span>
            </a>
            <h1>Adicionar alunos à equipe</h1>
            <div class="ms-auto d-flex gap-2">
                <button id="btnSalvarAlunosDesktop" class="btn btn-primary"><i class="bi bi-check-lg"></i></button>
            </div>
        </div>

        <div id="listaAlunosDesktop" class="sgi-u-display-grid-grid-repeat-2-1fr-gap-0-75rem">
            <div class="aluno-loading text-center py-4 text-muted">Carregando alunos...</div>
        </div>
    </div>
</main>

<div id="toastMensagem" class="position-fixed top-0 start-50 translate-middle-x z-3 p-3 d-none mt-2" >
    <div class="d-flex align-items-center gap-2 px-4 py-3 rounded-3 shadow-lg bg-white sgi-u-min-width-280px-border-left-5px-solid-198754" id="toastConteudo" >
        <i class="bi fs-4" id="toastIcone"></i>
        <span class="fw-semibold" id="toastTexto"></span>
    </div>
</div>

<script type="application/json" data-sgi-config="competicoes/equipe-alunos"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/equipe-alunos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
