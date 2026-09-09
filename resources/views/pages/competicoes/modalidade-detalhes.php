<?php
$tituloPagina = 'SGI - Detalhes da Modalidade';
$titulo = 'Modalidade';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');

include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
$isAdmin = $nivelUsuario === 0;
?>

<main class="main-desktop-layout main-mdd-layout">
    <div class="mdd-container">
        <a href="#" id="btnVoltarDashboardDesktop" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterModalidadeDet">Interclasse</span>
        </a>
        <div class="mdd-head">
            <div class="mdd-head__kicker">Detalhes da modalidade</div>
            <h2 class="mdd-head__title" id="nomeModalidadeHeadDesktop">Modalidade</h2>
            <p class="mdd-head__sub"><i class="bi bi-info-circle"></i> Informações da modalidade</p>
        </div>
        <div id="resumoModalidadeDesktop" class="mdd-hero">
            <p class="text-muted m-0">(Carregando modalidade...)</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="mdd-panel h-100">
                    <div class="mdd-panel__header">
                        <div class="mdd-panel__icon"><i class="bi bi-mortarboard"></i></div>
                        <h3 class="mdd-panel__title">Turmas vinculadas</h3>
                        <span class="mdd-panel__count" id="countTurmasDesktop">0</span>
                    </div>
                    <div id="listaTurmasDesktop" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mdd-panel h-100">
                    <div class="mdd-panel__header">
                        <div class="mdd-panel__icon"><i class="bi bi-people"></i></div>
                        <h3 class="mdd-panel__title">Equipes cadastradas</h3>
                        <span class="mdd-panel__count" id="countEquipesDesktop">0</span>
                    </div>
                    <div id="listaEquipesDesktop" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalEditarModalidade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Modalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarModalidade">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="editNomeModalidade" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos:</label>
                        <input type="number" class="form-control" id="editMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Equipes por Turma:</label>
                        <input type="number" class="form-control" id="editMaxEquipes" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="editTipoModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="editCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarModalidade" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicao">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="competicoes/modalidade-detalhes"><?= json_encode(['value2' => (bool) $isAdmin], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/modalidade-detalhes.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
