<?php
$tituloPagina = 'SGI - Modalidades';
$titulo = 'Modalidades';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'modalidades';
?>

<main class="position-relative d-md-none sgi-inline-80857b05" >
    <section id="listaModalidadesMobile" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando modalidades...)</p>
    </section>

    <div class="position-fixed sgi-inline-39903530" >
        <button class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center shadow sgi-inline-33e75484"  data-bs-toggle="modal" data-bs-target="#modalCriarModalidade">
            <i class="bi bi-plus-lg text-white fs-4"></i>
        </button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="sgi-inline-93f2597a">
        <div class="mb-5">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarModalidades" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseModalidades">Interclasse</span>
            </a>
        </div>

        <div class="row g-4" id="listaModalidadesDesktop">
            <p class="text-muted">(Carregando modalidades...)</p>
        </div>
    </div>

    <div class="position-fixed d-flex flex-row align-items-center gap-4 py-3 px-5 sgi-inline-06a722df" >
        <span class="text-muted small fw-medium">Não tem a modalidade que você quer?</span>

        <button type="button" class="btn bg-white fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm sgi-inline-035ae517"  data-bs-toggle="modal" data-bs-target="#modalCriarModalidade">
            <i class="bi bi-plus-circle"></i> Adicionar
        </button>
    </div>
</main>

<div class="modal fade" id="modalCriarModalidade" tabindex="-1" aria-labelledby="modalCriarModalidadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalCriarModalidadeLabel">Criar nova Modalidade</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaModalidade">
                    <div class="mb-3">
                        <label for="inputNomeModalidade" class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="inputNomeModalidade" placeholder="Ex: Futsal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Gênero:</label>
                        <select class="form-select" id="inputGeneroModalidade" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>
                    <?php if ($nivelUsuario === 0): ?>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 12" id="inputMaxInscritos" min="0">
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="inputTipoModalidade" required>
                            <option value="" disabled selected>Carregando tipos...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="inputCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando categorias...</option>
                        </select>
                    </div>
                    <div id="caixaMensagemModalidade" class="mt-3"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarModalidade">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="competicoes/modalidades"><?= json_encode(['value2' => ($nivelUsuario)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/modalidades.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
