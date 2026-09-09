<?php
$titulo = 'Turmas';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'categorias';
?>

<!-- Toast -->
<div class="toast-wrapper" id="toastWrapper"></div>

<!-- Mobile -->
<main class="position-relative d-md-none sgi-u-mb-120px" >
    <div class="p-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarCatMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-u-bg-E30613-radius-6px-p-8px-16px" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCatMob">Interclasse</span>
            </a>
            <div class="turma-search-wrapper flex-grow-1">
                <i class="bi bi-search turma-search-icon"></i>
                <input type="text" id="buscaTurmaMob" placeholder="Buscar turma..." oninput="filtrarTurmas()">
            </div>
        </div>
        <div id="listaTurmasMobile"></div>
    </div>

    <?php if ($nivelUsuario === 0): ?>
    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed sgi-u-h-60px-w-60px-bottom-100px"  data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus-lg text-white"></i>
    </button>
    <?php endif; ?>
</main>

<!-- Desktop -->
<main class="d-none d-md-flex flex-column main-desktop-layout">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarCatDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-u-bg-E30613-radius-6px-p-8px-16px" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-3 flex-shrink-0">
            <div class="turma-search-wrapper">
                <i class="bi bi-search turma-search-icon"></i>
                <input type="text" id="buscaTurmaDesk" placeholder="Buscar turma..." oninput="filtrarTurmas()">
            </div>
            <?php if ($nivelUsuario === 0): ?>
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 sgi-u-radius-8px"  data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-plus-lg"></i> Nova Turma
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="listaTurmasDesktop"></div>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurma">
                    <div class="mb-3">
                        <label for="nomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="nomeTurma" placeholder="Turma A" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomeFantasia" class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="nomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label for="turnoTurma" class="form-label">Turno:</label>
                        <select class="form-select" id="turnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="categoriaTurma" class="form-label">Categoria:</label>
                        <select class="form-select" id="categoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p class="sgi-u-text-14px">Adicione aqui o pdf dos alunos da turma criada</p>

                        <label for="arquivoUpload" class="">
                            <i class="bi bi-upload"></i>
                        </label>

                        <span id="nomeArquivo" class="text-muted"></span>
                    </div>
                    <div class=" d-flex justify-content-center gap-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Turma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarTurma">
                    <div class="mb-3">
                        <label class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="editNomeTurma" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="editNomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Turno:</label>
                        <select class="form-select" id="editTurnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria:</label>
                        <select class="form-select" id="editCategoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarTurma" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoTurma">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluirTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content sgi-u-radius-16px" >
            <div class="modal-body text-center py-4">
                <div class="modal-excluir-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Excluir Turma</h5>
                <p class="text-muted small mb-3">
                    Tem certeza que deseja excluir <strong class="modal-excluir-nome" id="excluirTurmaNome"></strong>?
                    <br>Esta ação não pode ser desfeita.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" id="btnConfirmarExclusao">Sim, excluir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="participantes/turmas"><?= json_encode(['value2' => ($nivelUsuario)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/participantes/turmas.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
