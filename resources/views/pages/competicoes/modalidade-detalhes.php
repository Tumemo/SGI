<?php
$titulo = 'Modalidade';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');

include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
$isAdmin = $nivelUsuario === 0;
?>

<main class="main-desktop-layout">
    <div class="container-fluid px-0">
        <a href="#" id="btnVoltarDashboardDesktop" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none d-none d-md-inline-flex" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterModalidadeDet">Interclasse</span>
        </a>
        <div class="mb-4">
            <h1 class="h2 text-body-secondary fw-bold">Detalhes da modalidade</h1>
            <h2 class="fs-2 fw-bold text-body mb-2" id="nomeModalidadeHeadDesktop">Modalidade</h2>
            <p class="small text-body-secondary mb-0"><i class="bi bi-info-circle text-primary me-1"></i> Informações da modalidade</p>
        </div>
        <div id="resumoModalidadeDesktop" class="card border-0 border-top border-4 border-primary shadow-sm rounded-4 p-4 p-lg-5 mb-4">
            <p class="text-body-secondary m-0">(Carregando modalidade...)</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex"><i class="bi bi-mortarboard"></i></div>
                        <h3 class="h5 fw-bold text-body mb-0">Turmas vinculadas</h3>
                        <span class="badge text-bg-light ms-auto" id="countTurmasDesktop">0</span>
                    </div>
                    <div id="listaTurmasDesktop" class="row row-cols-1 row-cols-sm-2 g-3"><p class="text-body-secondary">(Carregando...)</p></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex"><i class="bi bi-people"></i></div>
                        <h3 class="h5 fw-bold text-body mb-0">Equipes cadastradas</h3>
                        <span class="badge text-bg-light ms-auto" id="countEquipesDesktop">0</span>
                    </div>
                    <div id="listaEquipesDesktop" class="row row-cols-1 row-cols-sm-2 g-3"><p class="text-body-secondary">(Carregando...)</p></div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalEditarModalidade" tabindex="-1" aria-labelledby="tituloEditarModalidade" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold" id="tituloEditarModalidade">Editar Modalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarModalidade">
                    <div class="mb-3">
                        <label for="editNomeModalidade" class="form-label fw-medium">Nome da modalidade</label>
                        <input type="text" class="form-control" id="editNomeModalidade" required>
                    </div>
                    <div class="mb-3">
                        <label for="editGeneroModalidade" class="form-label fw-medium">Gênero:</label>
                        <select class="form-select" id="editGeneroModalidade" required>
                            <option value="" disabled>Selecione...</option>
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editMaxInscritos" class="form-label fw-medium">Máximo de inscritos</label>
                        <input type="number" class="form-control" id="editMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="editMaxEquipes" class="form-label fw-medium">Máximo de equipes por turma</label>
                        <input type="number" class="form-control" id="editMaxEquipes" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="editTipoModalidade" class="form-label fw-medium">Tipo de modalidade</label>
                        <select class="form-select" id="editTipoModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoriaModalidade" class="form-label fw-medium">Categoria</label>
                        <select class="form-select" id="editCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarModalidade" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSalvarEdicao">Salvar Alterações</button>
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
