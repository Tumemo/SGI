<?php
$titulo = 'Modalidade';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="main-desktop-layout main-modalidades-layout">

    <div class="modalidades-toolbar">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarModalidades" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseModalidades">Interclasse</span>
            </a>
        <div class="d-flex align-items-center gap-3 flex-shrink-0 flex-wrap">
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalDestaques">
                <span>⭐</span> Alunos Destaques
            </button>
            <?php if ($nivelUsuario === 0): ?>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 rounded-3"  data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-plus-lg"></i> Nova Modalidade
            </button>
            <?php endif; ?>
            <a href="#" id="btnContinuarDesktop" class="btn btn-primary fw-bold px-4 py-2 d-inline-flex align-items-center gap-2 text-white text-decoration-none disabled d-none" aria-disabled="true">
                Continuar
            </a>
        </div>
    </div>

    <header class="modalidades-head">
        <h1 class="modalidades-head__title">Modalidades</h1>
        <p class="modalidades-head__sub">Gerencie as modalidades do interclasse e navegue para os detalhes de cada uma.</p>
    </header>

    <div id="listaModalidadesDesktop">
        <p class="text-muted">(Carregando modalidades...)</p>
    </div>

</main>


<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Modalidade</h1>
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
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 12" id="inputMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Equipes por Turma (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 3" id="inputMaxEquipes" min="1">
                    </div>
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
                        <button type="submit" class="btn btn-primary" id="btnSalvarModalidade">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alunos Destaques -->
<div class="modal fade" id="modalDestaques" tabindex="-1" aria-labelledby="modalDestaquesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4" >
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold" id="modalDestaquesLabel"><i class="bi bi-star-fill me-2 sgi-u-color-f5b301" ></i>Alunos Destaques</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="corpoDestaques">
                <p class="text-muted small">(Carregando destaques...)</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="eventos/configurar-modalidades"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-modalidades.js') ?>"></script>



<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
