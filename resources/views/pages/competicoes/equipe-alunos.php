<?php
$titulo = 'Adicionar alunos à equipe';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
$idVoltarMobile = 'btnVoltarEquipesMobile';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none mb-5" >
    <div class="container mt-3">
        <label for="buscaAlunosMobile" class="visually-hidden">Buscar aluno por nome ou matrícula</label>
        <input id="buscaAlunosMobile" class="form-control mb-2" type="search" placeholder="Buscar aluno por nome ou matrícula" autocomplete="off">
        <p class="small text-body-secondary mb-3">Marque novos alunos para adicioná-los. Alunos já vinculados permanecem na equipe; desmarcar não remove ninguém.</p>
        <div id="listaAlunosMobile" class="row row-cols-1 row-cols-sm-2 g-3">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
        <p id="feedbackSelecaoEquipeMobile" class="small text-body-secondary mt-3 mb-1" role="status" aria-live="polite" aria-atomic="true">Nenhum aluno novo selecionado.</p>
        <button id="btnSalvarAlunosMobile" class="btn btn-primary w-100" type="button" aria-label="Salvar alunos selecionados na equipe — Adicionar 0 alunos" disabled>
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i><span data-selection-count>Adicionar 0 alunos</span>
        </button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid py-4 px-4 text-body">
        <?php
        $headerIdVoltar = 'btnVoltarEquipesDesktop';
        $headerCorpoHtml = '<h1 class="h4 mb-0 fw-bold">Adicionar alunos à equipe</h1>';
        include SGI_ROOT . '/resources/views/components/page-header.php';
        unset($headerMostrarVoltar, $headerCorpoHtml, $headerAcoesHtml, $headerClasse, $headerUrlVoltar, $headerIdVoltar, $headerClassBotao, $headerHiddenBotao);
        ?>

        <label for="buscaAlunosDesktop" class="visually-hidden">Buscar aluno por nome ou matrícula</label>
        <input id="buscaAlunosDesktop" class="form-control mb-2" type="search" placeholder="Buscar aluno por nome ou matrícula" autocomplete="off">
        <p class="small text-body-secondary mb-3">Marque novos alunos para adicioná-los. Alunos já vinculados permanecem na equipe; desmarcar não remove ninguém.</p>
        <p id="feedbackSelecaoEquipeDesktop" class="small text-body-secondary mb-2" role="status" aria-live="polite" aria-atomic="true">Nenhum aluno novo selecionado.</p>
        <div id="listaAlunosDesktop" class="row row-cols-1 row-cols-lg-2 g-3">
            <div class="text-center py-4 text-body-secondary">Carregando alunos...</div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button id="btnSalvarAlunosDesktop" class="btn btn-primary" type="button" aria-label="Salvar alunos selecionados na equipe — Adicionar 0 alunos" disabled>
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i><span data-selection-count>Adicionar 0 alunos</span>
            </button>
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="competicoes/equipe-alunos"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/equipe-alunos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
