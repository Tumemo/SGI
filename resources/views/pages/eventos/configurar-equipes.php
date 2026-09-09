<?php
$tituloPagina = 'SGI - Equipes';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3 sgi-inline-d6522d52" >
    <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarEquipesMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipesMob">Interclasse</span>
    </a>
    <p class="text-secondary text-center small mb-3">Equipes por modalidade e categoria desta edição.</p>

    <div id="filtroCategoriaMobile" class="d-flex flex-nowrap overflow-auto gap-2 pb-2 mb-3"></div>

    <?php if ($isAdmin): ?>
    <button id="btnCriarEquipeMob" class="btn btn-aluno w-100 fw-semibold mb-3" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
        <i class="bi bi-plus-lg"></i>
    </button>
    <?php endif; ?>
    <div id="listaEquipesMobile" class="d-flex flex-column gap-3"></div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <div class="header-left">
                <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarEquipesDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipesDesk">Interclasse</span>
                </a>
                <h1 id="nomeInterclasseEquipes" class="sgi-inline-12a59c06">Equipes</h1>
            </div>
            <?php if ($isAdmin): ?>
            <button id="btnCriarEquipeDesk" class="btn btn-aluno" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
                <i class="bi bi-plus-lg"></i>
            </button>
            <?php endif; ?>
        </div>

        <div id="filtroCategoria" class="d-flex flex-wrap gap-2 mb-4"></div>

        <div id="listaEquipesDesktop">
            <div class="aluno-loading text-center py-4 text-muted">Carregando...</div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalCriarEquipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sgi-inline-910958c1" >
            <div class="modal-header border-0 sgi-inline-d4384255" >
                <h5 class="modal-title sgi-inline-919e66cc" ><i class="bi bi-plus-circle me-2"></i>Criar nova equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body sgi-inline-e7df035e" >
                <form id="formCriarEquipe">
                    <label for="selectModalidadeEquipe" class="form-label small text-muted fw-semibold">Modalidade</label>
                    <select id="selectModalidadeEquipe" class="form-select mb-3 sgi-inline-0fd5584e"  required>
                        <option value="" selected disabled>Carregando modalidades...</option>
                    </select>
                    <label for="selectTurmaEquipe" class="form-label small text-muted fw-semibold">Turma</label>
                    <select id="selectTurmaEquipe" class="form-select mb-3 sgi-inline-0fd5584e"  required>
                        <option value="" selected disabled>Carregando turmas...</option>
                    </select>
                    <div id="msgCriarEquipe" class="text-center mb-2 small"></div>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-aluno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-aluno" id="btnSalvarEquipe"><i class="bi bi-check-lg"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="eventos/configurar-equipes"><?= json_encode(['value2' => (bool) $isAdmin], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-equipes.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
