<?php
$tituloPagina = 'SGI - Colaboradores';
$titulo = 'Colaboradores';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'colaboradores';

// Verifica se o usuário logado é administrador
$usuarioEhAdmin = (isset($_SESSION['nivel']) && (string)$_SESSION['nivel'] === '0');
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none sgi-inline-5460531b" >
    <div class="col-wrap">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarColabMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseColabMobile">Interclasse</span>
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsMobile">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalMob">-</div><div class="col-stat__label">Usuários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminMob">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioMob">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabMob">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar sgi-inline-3b7b4abf" >
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabMob" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosMob">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresMobile">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout col-page">
    <div class="col-wrap">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarColabDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseColabDesk">Interclasse</span>
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar colaborador
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsDesktop">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalDesk">-</div><div class="col-stat__label">Usuários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminDesk">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioDesk">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabDesk">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar">
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabDesk" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosDesk">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresDesktop">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ MODAL ADICIONAR ═══ -->
<div class="modal fade col-modal" id="modalAdicionarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus text-danger me-2"></i>Adicionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email / Matrícula</label>
                        <input type="text" class="form-control" id="novoNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="text" class="form-control" id="novaSenhaColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="novoGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>

                    <?php if ($usuarioEhAdmin): ?>
                    <!-- Restrição: Exibido apenas se o usuário logado for Admin -->
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoAdminColaborador">
                        <label class="form-check-label" for="novoAdminColaborador">Administrador</label>
                    </div>
                    <?php endif; ?>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoMesarioColaborador" checked>
                        <label class="form-check-label" for="novoMesarioColaborador">Mesário</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoColaborador">
                        <label class="form-check-label" for="novoColaborador">Colaborador</label>
                    </div>
                    <div id="msgNovoColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary sgi-inline-0d1f6728" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-danger sgi-inline-be2e418b" id="btnSalvarColaborador" >Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDITAR ═══ -->
<div class="modal fade col-modal" id="modalEditarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square text-danger me-2"></i>Editar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matrícula / NIF</label>
                        <input type="text" class="form-control" id="editNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova senha <small class="text-muted">(deixe em branco para manter)</small></label>
                        <input type="text" class="form-control" id="editSenhaColaborador">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="editGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>
                    <div id="msgEditarColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary sgi-inline-0d1f6728" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-danger sgi-inline-be2e418b" id="btnSalvarEdicaoColaborador" >Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="acesso/colaboradores"><?= json_encode(['value2' => ($usuarioEhAdmin)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/acesso/colaboradores.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
