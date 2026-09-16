<?php
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
<main class="d-md-none pt-5 pb-5">
    <div class="container-fluid px-3">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarColabMobile" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
            <i class="bi bi-arrow-left-circle fs-5" aria-hidden="true"></i> <span id="nomeInterclasseColabMobile">Interclasse</span>
        </a>

        <div class="mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h1 class="h3 fw-bold mb-1">Colaboradores</h1>
                    <p class="text-body-secondary mb-0">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar
                </button>
            </div>
        </div>

        <div class="row row-cols-2 g-3 mb-4" id="statsMobile">
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-people-fill" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statTotalMob">-</div><div class="small text-body-secondary text-uppercase">Usuários</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-shield-fill" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statAdminMob">-</div><div class="small text-body-secondary text-uppercase">Admins</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statMesarioMob">-</div><div class="small text-body-secondary text-uppercase">Mesários</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-person" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statColabMob">-</div><div class="small text-body-secondary text-uppercase">Colaboradores</div></div></div></div>
        </div>

        <div class="d-flex flex-column align-items-stretch gap-3 mb-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <label for="buscaColabMob" class="visually-hidden">Pesquisar colaboradores</label>
                <input type="text" class="form-control" id="buscaColabMob" placeholder="Pesquisar colaborador...">
            </div>
            <div class="d-flex flex-wrap gap-2" id="filtrosMob">
                <button class="btn btn-sm btn-primary" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="row row-cols-1 g-3" id="listaColaboradoresMobile">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border text-primary me-2" role="status"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout pb-5">
    <div class="container-fluid px-4">
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarColabDesk" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
            <i class="bi bi-arrow-left-circle fs-5" aria-hidden="true"></i> <span id="nomeInterclasseColabDesk">Interclasse</span>
        </a>

        <div class="mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h1 class="h3 fw-bold mb-1">Colaboradores</h1>
                    <p class="text-body-secondary mb-0">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar colaborador
                </button>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-lg-4 g-3 mb-4" id="statsDesktop">
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-people-fill" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statTotalDesk">-</div><div class="small text-body-secondary text-uppercase">Usuários</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-shield-fill" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statAdminDesk">-</div><div class="small text-body-secondary text-uppercase">Admins</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statMesarioDesk">-</div><div class="small text-body-secondary text-uppercase">Mesários</div></div></div></div>
            <div class="col"><div class="card border-0 shadow-sm h-100 p-3 d-flex flex-row align-items-center gap-3"><span class="rounded-3 bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center p-2 fs-5"><i class="bi bi-person" aria-hidden="true"></i></span><div><div class="fs-4 fw-bold" id="statColabDesk">-</div><div class="small text-body-secondary text-uppercase">Colaboradores</div></div></div></div>
        </div>

        <div class="d-flex gap-3 align-items-center flex-wrap mb-4">
            <div class="input-group flex-grow-1">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <label for="buscaColabDesk" class="visually-hidden">Pesquisar colaboradores</label>
                <input type="text" class="form-control" id="buscaColabDesk" placeholder="Pesquisar colaborador...">
            </div>
            <div class="d-flex flex-wrap gap-2" id="filtrosDesk">
                <button class="btn btn-sm btn-primary" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-lg-2 g-3" id="listaColaboradoresDesktop">
            <div class="col text-center text-body-secondary py-5"><div class="spinner-border text-primary me-2" role="status"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ MODAL ADICIONAR ═══ -->
<div class="modal fade" id="modalAdicionarColaborador" tabindex="-1" aria-labelledby="modalAdicionarColaboradorTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalAdicionarColaboradorTitulo"><i class="bi bi-person-plus text-danger me-2" aria-hidden="true"></i>Adicionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar janela Adicionar colaborador"></button>
            </div>
            <div class="modal-body pt-0">
                <form id="formNovoColaborador">
                    <div class="mb-3">
                        <label for="novoNomeColaborador" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label for="novoNifColaborador" class="form-label">Email / Matrícula</label>
                        <input type="text" class="form-control" id="novoNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label for="novaSenhaColaborador" class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="novaSenhaColaborador" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-password-toggle data-password-target="novaSenhaColaborador" aria-controls="novaSenhaColaborador" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden" data-password-label>Mostrar senha</span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="novoGeneroColaborador" class="form-label">Gênero</label>
                        <select class="form-select" id="novoGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>

                    <fieldset class="border-0 p-0 m-0">
                        <legend class="form-label mb-2">Tipo de usuário</legend>
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
                    </fieldset>
                    <div id="msgNovoColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold small" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-bold small" id="btnSalvarColaborador" >Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDITAR ═══ -->
<div class="modal fade" id="modalEditarColaborador" tabindex="-1" aria-labelledby="modalEditarColaboradorTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalEditarColaboradorTitulo"><i class="bi bi-pencil-square text-danger me-2" aria-hidden="true"></i>Editar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar janela Editar colaborador"></button>
            </div>
            <div class="modal-body pt-0">
                <form id="formEditarColaborador">
                    <div class="mb-3">
                        <label for="editNomeColaborador" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label for="editNifColaborador" class="form-label">Matrícula / NIF</label>
                        <input type="text" class="form-control" id="editNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSenhaColaborador" class="form-label">Nova senha <small class="text-muted">(deixe em branco para manter)</small></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="editSenhaColaborador" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" data-password-toggle data-password-target="editSenhaColaborador" aria-controls="editSenhaColaborador" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden" data-password-label>Mostrar senha</span>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editGeneroColaborador" class="form-label">Gênero</label>
                        <select class="form-select" id="editGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>
                    <div id="msgEditarColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold small" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-bold small" id="btnSalvarEdicaoColaborador" >Salvar</button>
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
