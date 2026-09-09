<?php
$tituloPagina = 'SGI - Gerenciar Turmas';
$titulo = 'Turmas';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('edicoes/categorias');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'categorias';
?>
<main class="d-md-none">
    <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarTurmasMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmasMob">Interclasse</span>
    </a>
    <p class="text-secondary text-center my-3">Editar detalhes turmas</p>

    <input type="text" id="inputBuscaTurmaMobile" class="form-control w-75 mx-auto mb-3 form-control-sm rounded-pill" placeholder="Buscar turma">

    <div id="listaTurmasMobile" class="px-3">
        <p class="text-muted text-center">Carregando...</p>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout" id="viewTurmasGestaoDesk">
    <div class="container-fluid px-0">
        <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="btnVoltarTurmasDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmasDesk">Interclasse</span>
        </a>
        <div class="row g-4 mx-0">
            <div class="col-md-4 px-0 px-md-2">
                <div class="bg-white rounded-4 shadow-sm overflow-hidden border-0">
                    <div class="p-3 d-flex align-items-center gap-2 sgi-inline-753d6b41" >
                        <h6 class="mb-0 fw-bold fs-5">Categorias</h6>
                    </div>

                    <div id="listaCategorias" class="list-group list-group-flush sgi-inline-3469be48" >
                        <p class="text-muted p-3 mb-0 text-center">Carregando categorias...</p>
                    </div>
                </div>
            </div>

            <div class="col-md-8 px-0 px-md-2 d-flex flex-column gap-3">

                <div class="bg-white rounded-3 shadow-sm p-2 d-flex align-items-center">
                    <i class="bi bi-search text-muted ms-3"></i>
                    <input type="text" id="inputBuscaTurma" class="form-control border-0 shadow-none bg-transparent" placeholder="Buscar turma">
                    <button class="btn fw-bold px-4 text-nowrap sgi-inline-2ea0b8d1"  data-bs-toggle="modal" data-bs-target="#modalCriarTurma">
                        + Adicionar
                    </button>
                </div>

                <div id="listaTurmas" class="d-flex flex-column gap-3 pe-2 sgi-inline-3469be48" >
                    <div class="text-center mt-5">
                        <p class="text-muted fs-5">Selecione uma categoria ao lado para ver as turmas.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCriarTurma" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 p-2">
                <div class="modal-header border-0 pb-0 justify-content-center">
                    <h5 class="modal-title fw-bold text-center w-100 sgi-inline-2ea0b8d1" >
                        ADICIONAR TURMA
                    </h5>
                </div>
                <form id="formTurma">
                    <div class="modal-body pt-3 pb-3">
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium sgi-inline-d634f68d" >Nome da turma:</label>
                            <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary sgi-inline-c7a491b4" placeholder="Ex: 9º Ano A"  id="inputNomeTurma" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium sgi-inline-d634f68d" >Nome fantasia:</label>
                            <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary sgi-inline-c7a491b4" placeholder="Ex: Turma dos Campeões"  id="inputNomeFantasiaTurma">
                        </div>
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium sgi-inline-d634f68d" >Turno:</label>
                            <select class="form-select form-select-lg shadow-sm rounded-3 text-secondary sgi-inline-c7a491b4"  id="inputTurnoTurma">
                                <option value="">Selecione o turno</option>
                                <option value="Manhã">Manhã</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noite">Noite</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2 flex-wrap">
                        <div id="msgTurma" class="w-100 text-center small mb-2"></div>
                        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 sgi-inline-960f414c" data-bs-dismiss="modal" >
                            Cancelar
                        </button>
                        <button type="submit" class="btn fw-semibold rounded-3 px-4 py-2 text-white sgi-inline-e625dcf1"  id="btnSalvarTurma">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="eventos/configurar-turmas"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-turmas.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
