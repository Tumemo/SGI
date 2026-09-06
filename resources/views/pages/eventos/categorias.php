<?php
$tituloPagina = 'SGI - Categorias';
$titulo = 'Categorias';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'categorias';
$isAdmin = $nivelUsuario === 0;
$isColaborador = $nivelUsuario === 1;
$isMesario = $nivelUsuario === 2;
?>



<!-- main mobile -->
<main class="position-relative d-md-none sgi-inline-80857b05" >
    <a href="./dashboard.php" id="btnVoltarCatMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCatMob">Interclasse</span>
    </a>

    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>

    <?php if ($isAdmin): ?>
    <section class="d-flex gap-3 mt-3 position-fixed translate-middle flex-wrap justify-content-center sgi-inline-1ab1658d" >
        <button type="button" id="btnEditarCategoriaMobile" class="btn btn-outline-primary d-none" onclick="abrirModalEditarCategoria()">Editar</button>
        <button type="button" id="btnExcluirCategoriaMobile" class="btn btn-danger d-none" onclick="excluirCategoria()">Excluir</button>
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar Categoria</button>
        <a href="#" id="btnContinuarMobile" class="btn btn-danger">Continuar</a>
    </section>
    <?php elseif ($isColaborador): ?>
    <div class="d-flex justify-content-center mt-4">
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar categoria</button>
    </div>
    <?php endif; ?>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarCatDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>
        </div>

        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>

        <?php if ($isAdmin): ?>
        <div class="position-fixed d-flex flex-row gap-3 sgi-inline-ddd60826" >
            <button type="button" id="btnEditarCategoriaDesktop" class="btn btn-outline-primary fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg d-none" onclick="abrirModalEditarCategoria()">
                <i class="bi bi-pencil-square"></i> Editar
            </button>
            <button type="button" id="btnExcluirCategoriaDesktop" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg d-none" onclick="excluirCategoria()">
                <i class="bi bi-trash"></i> Excluir
            </button>
            <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg sgi-inline-f825f7e3"  data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
                <i class="bi bi-plus-circle"></i> Adicionar
            </button>
            <a href="#" id="btnContinuarDesktop" class="btn fw-semibold rounded-3 px-5 py-2 text-white text-decoration-none shadow-lg d-flex align-items-center justify-content-center sgi-inline-aec0cf4e" >
                Continuar
            </a>
        </div>
        <?php elseif ($isColaborador): ?>
        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg mt-4 sgi-inline-f825f7e3"  data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
            <i class="bi bi-plus-circle"></i> Adicionar categoria
        </button>
        <?php endif; ?>
    </div>
</main>

<?php if ($isAdmin): ?>
<!-- modal de criar nova turma (admin only) -->
<div class="modal fade" id="criarTurma" tabindex="-1" aria-labelledby="criarTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="criarTurmaLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurmaCategoria">
                    <div class="mb-3">
                        <label for="inputNomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="inputNomeTurma" placeholder="Ex: 1º Médio A" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputNomeFantasiaTurma" class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="inputNomeFantasiaTurma" placeholder="Ex: Turma dos Campeões">
                    </div>
                    <div class="mb-3">
                        <label for="inputTurnoTurma" class="form-label">Turno:</label>
                        <select class="form-select" id="inputTurnoTurma">
                            <option value="">Selecione o turno</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p class="text-center sgi-inline-346a5ee5" >Adicione aqui o pdf dos alunos da turma criada</p>
                        <label for="arquivoUpload" class="btn btn-light border rounded-circle p-3 sgi-inline-f649ae05" >
                            <i class="bi bi-upload fs-4"></i>
                        </label>
                        <span id="nomeArquivo" class="text-muted mt-2"></span>
                    </div>
                    <div id="msgNovaTurmaCategoria" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnCriarTurmaCategoria">Criar e enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin || $isColaborador): ?>
<!-- modal de criar nova categoria -->
<div class="modal fade" id="modalCriarCategoria" tabindex="-1" aria-labelledby="modalNovaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalNovaCategoriaLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6 mb-3">Insira o nome da sua nova categoria:</h2>
                <form id="formNovaCategoria">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: Ensino Médio" id="inputNomeCategoriaNova" required>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-5">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarCategoria">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<!-- modal de editar categoria (admin only) -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarCategoria">
                    <h2 class="fs-6 mb-3">Altere o nome da categoria:</h2>
                    <input type="text" class="form-control" id="editNomeCategoria" required>
                    <div id="msgEditarCategoria" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-3 pt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoCategoria">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script type="application/json" data-sgi-config="eventos/categorias"><?= json_encode(['value0' => ($isMesario), 'value1' => ($isAdmin), 'value2' => ($isColaborador)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/categorias.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
