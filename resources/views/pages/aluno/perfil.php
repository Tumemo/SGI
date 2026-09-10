<?php
$titulo = 'Perfil';
$mostrarVoltar = true;
$mostrarSino = false;
$urlVoltar = \App\Shared\Http\Url::to('aluno/inicio');

$nivelUsuario = (int) ($usuarioPerfil['nivel_usuario'] ?? $_SESSION['nivel'] ?? 0);
$labelNiveis = [
    0 => ['label' => 'Administrador', 'icon' => 'bi-shield-fill-check', 'color' => '#E30613'],
    1 => ['label' => 'Colaborador',   'icon' => 'bi-person-badge-fill', 'color' => '#0d6efd'],
    2 => ['label' => 'Mesário',       'icon' => 'bi-person-check-fill', 'color' => '#6f42c1'],
    3 => ['label' => 'Usuário',       'icon' => 'bi-person-fill',       'color' => '#198754'],
];
$nivelInfo = $labelNiveis[$nivelUsuario] ?? ['label' => 'Desconhecido', 'icon' => 'bi-question-circle', 'color' => '#6c757d'];

include SGI_ROOT . '/resources/views/components/aluno-head.php';

$paginaAtiva = 'perfil';
include SGI_ROOT . '/resources/views/components/aluno-nav.php';

?>

<!-- ===================== MOBILE ===================== -->
<main class="perfil-page d-md-none p-3 sgi-u-pt-1-25rem-pb-5rem" >
    <a href="<?= htmlspecialchars($urlVoltar) ?>" id="perfilBackMob" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 text-decoration-none" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span>Início</span>
    </a>

    <h5 class="fw-bold mb-4">Configurações da Conta</h5>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body text-center py-4">
            <div class="perfil-avatar-ring mx-auto" id="fotoCircleMob">
                <div class="perfil-avatar-inner">
                    <?php $fotoPath = $usuarioPerfil['foto_usuario'] ? \App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario'])) : ''; ?>
                    <img src="<?= $fotoPath ?>" id="fotoImgMob" class="w-100 h-100 object-fit-cover <?= $fotoPath ? '' : 'd-none' ?>" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconMob')?.classList.remove('d-none');">
                    <i class="bi bi-person-fill <?= $fotoPath ? 'd-none' : '' ?>" id="fotoIconMob"></i>
                    <div class="perfil-avatar-skeleton" id="fotoSkeletonMob">
                        <div class="perfil-skeleton-pulse"></div>
                    </div>
                </div>
                <button type="button" class="perfil-btn-camera" id="btnCameraMob" title="Alterar foto">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>
            <h5 class="fw-bold mt-3 mb-1" id="perfilNomeMob"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></h5>
            <span class="perfil-badge-nivel nivel-cor-<?= (int)$nivelUsuario ?>">
                <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
            </span>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-primary rounded-pill d-none px-3" id="btnSalvarFotoMob"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill perfil-btn-excluir" id="btnExcluirFotoMob" disabled><i class="bi bi-trash me-1"></i>Remover</button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h6 class="perfil-card-title mb-3"><i class="bi bi-person-vcard me-2"></i>Informações Pessoais</h6>
            <div class="perfil-field"><span class="perfil-field-label"><i class="bi bi-person-badge"></i> Matrícula</span><span class="perfil-field-value" id="perfilEmailMob"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></span></div>
            <div class="perfil-field"><span class="perfil-field-label"><i class="bi bi-briefcase"></i> Cargo</span><span class="perfil-field-value"><?= $nivelInfo['label'] ?></span></div>
            <div class="perfil-field mb-0"><span class="perfil-field-label"><i class="bi bi-envelope"></i> E-mail</span><span class="perfil-field-value"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></span></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h6 class="perfil-card-title mb-3"><i class="bi bi-shield-lock me-2"></i>Segurança e Acesso</h6>
            <div class="perfil-field"><span class="perfil-field-label"><i class="bi bi-lock"></i> Senha</span><span class="perfil-field-value"><span class="perfil-mask">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span></span></div>
            <div class="perfil-field"><span class="perfil-field-label"><i class="bi bi-shield-check"></i> Nível</span><span class="perfil-field-value"><span class="perfil-badge-nivel perfil-badge-nivel--sm nivel-cor-<?= (int)$nivelUsuario ?>"><i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?></span></span></div>
            <div class="perfil-field mb-0"><span class="perfil-field-label"><i class="bi bi-key"></i> Alterar</span><span class="perfil-field-value"><button class="btn btn-link btn-sm text-decoration-none p-0 text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAlterarSenha">Alterar senha</button></span></div>
        </div>
    </div>

    <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
        <i class="bi bi-pencil-square me-2"></i>Editar perfil
    </button>
</main>


<!-- ===================== DESKTOP ===================== -->
<main class="perfil-page perfil-desktop d-none d-md-block">
    <div class="perfil-wrapper">
        <!-- Topbar -->
        <div class="perfil-topbar">
            <a href="<?= htmlspecialchars($urlVoltar) ?>" id="perfilBackDesk" class="perfil-btn-voltar btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span>Início</span>
            </a>
            <div class="perfil-topbar-title">
                <h1><i class="bi bi-person-circle me-2 text-primary" ></i>Meu Perfil</h1>
                <p class="perfil-topbar-subtitle">Gerencie suas informações, segurança e acompanhe sua participação</p>
            </div>
        </div>

        <!-- Grid: 260px + 1fr -->
        <div class="perfil-grid">
            <!-- === COLUNA ESQUERDA: Identidade Visual === -->
            <aside class="perfil-grid-left">
                <div class="card border-0 shadow-sm rounded-4 perfil-card-identity">
                    <div class="card-body text-center py-5 px-4">
                        <div class="perfil-avatar-ring mx-auto" id="fotoCircleDesk">
                            <div class="perfil-avatar-inner">
                                <?php $fotoPathDesk = $usuarioPerfil['foto_usuario'] ? \App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario'])) : ''; ?>
                                <img src="<?= $fotoPathDesk ?>" id="fotoImgDesk" class="w-100 h-100 object-fit-cover <?= $fotoPathDesk ? '' : 'd-none' ?>" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconDesk')?.classList.remove('d-none');">
                                <i class="bi bi-person-fill <?= $fotoPathDesk ? 'd-none' : '' ?>" id="fotoIconDesk"></i>
                                <div class="perfil-avatar-skeleton" id="fotoSkeletonDesk">
                                    <div class="perfil-skeleton-pulse"></div>
                                </div>
                            </div>
                            <button type="button" class="perfil-btn-camera" id="btnCameraDesk" title="Alterar foto">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                        </div>

                        <h5 class="fw-bold mt-3 mb-1" id="perfilNomeDesk"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></h5>
                        <span class="perfil-badge-nivel nivel-cor-<?= (int)$nivelUsuario ?>">
                            <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
                        </span>

                        <div class="d-flex align-items-center justify-content-center gap-1 mt-2 sgi-u-text-0-8rem-color-888" >
                            <span class="perfil-status-dot perfil-status-online"></span> Online
                        </div>

                        <div class="d-flex justify-content-center gap-2 mt-4">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill d-none px-3" id="btnSalvarFotoDesk"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill perfil-btn-excluir" id="btnExcluirFotoDesk" disabled><i class="bi bi-trash me-1"></i>Remover</button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- === COLUNA DIREITA: Cards Funcionais === -->
            <div class="perfil-grid-right">

                <!-- Card 1: Informações Pessoais -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="perfil-card-title mb-3"><i class="bi bi-person-vcard me-2"></i>Informações Pessoais</h6>
                        <div class="perfil-info-grid">
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-person"></i> Nome Completo</span>
                                <span class="perfil-info-value" id="perfilNomeInfo"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></span>
                            </div>
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-briefcase"></i> Cargo / Função</span>
                                <span class="perfil-info-value">Competidor</span>
                            </div>
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-person-badge"></i> Matrícula</span>
                                <span class="perfil-info-value" id="perfilEmailDesk"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></span>
                            </div>
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-envelope"></i> E-mail</span>
                                <span class="perfil-info-value"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Segurança e Acesso -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="perfil-card-title mb-3"><i class="bi bi-shield-lock me-2"></i>Segurança e Acesso</h6>
                        <div class="perfil-security-grid">
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-lock"></i> Senha</span>
                                <span class="perfil-info-value">
                                    <span class="perfil-mask">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span>
                                    <button class="btn btn-link btn-sm text-decoration-none p-0 ms-2 text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAlterarSenha">Alterar senha</button>
                                </span>
                            </div>
                            <div class="perfil-info-item">
                                <span class="perfil-info-label"><i class="bi bi-shield-check"></i> Nível de Acesso</span>
                                <span class="perfil-info-value">
                                    <span class="perfil-badge-nivel perfil-badge-nivel--sm nivel-cor-<?= (int)$nivelUsuario ?>">
                                        <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="perfil-info-item mb-0">
                                <span class="perfil-info-label"><i class="bi bi-shield-plus"></i> Autenticação</span>
                                <span class="perfil-info-value sgi-u-color-888-text-0-85rem" >Senha criptografada</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de ações -->
                <div class="d-flex justify-content-end">
                    <button type="button" class="perfil-btn-editar" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                        <i class="bi bi-pencil-square me-2"></i>Editar perfil
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade w-100" id="modalEditarPerfil" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pt-4 px-4 pb-0">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square text-danger me-2"></i>Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarPerfil">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold"><i class="bi bi-person me-1"></i>Nome</label>
                        <input type="text" name="nome_usuario" class="form-control rounded-3 perfil-input" id="editarNome" required>
                    </div>
                    <div id="msgEditarPerfil" class="small text-center mt-2"></div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="btnSalvarPerfil"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAlterarSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pt-4 px-4 pb-0">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-shield-lock text-danger me-2"></i>Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formAlterarSenha">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Senha Atual</label>
                        <div class="perfil-password-input">
                            <input type="password" name="senha_atual" class="form-control rounded-3 perfil-input pe-5" id="editarSenhaAtual" required autocomplete="current-password">
                            <button type="button" class="perfil-password-eye" data-target="editarSenhaAtual" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Nova Senha</label>
                        <div class="perfil-password-input">
                            <input type="password" name="nova_senha" class="form-control rounded-3 perfil-input pe-5" id="editarNovaSenha" required minlength="6" autocomplete="new-password">
                            <button type="button" class="perfil-password-eye" data-target="editarNovaSenha" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Confirmar Nova Senha</label>
                        <div class="perfil-password-input">
                            <input type="password" name="confirmar_senha" class="form-control rounded-3 perfil-input pe-5" id="editarConfirmarSenha" required autocomplete="new-password">
                            <button type="button" class="perfil-password-eye" data-target="editarConfirmarSenha" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div id="msgAlterarSenha" class="small text-center mt-2"></div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="btnSalvarSenha"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<input type="file" id="fotoUploadInput" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">

<script src="<?= \App\Shared\Http\Assets::url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
<script type="application/json" data-sgi-config="aluno/perfil"><?= json_encode(['value2' => ($usuarioPerfil['nome_usuario'] ?? ''), 'value3' => ($usuarioPerfil['matricula_usuario'] ?? ''), 'value4' => ($sessionId ?? 0), 'value5' => ((int)($nivelUsuario ?? 3))], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/aluno/perfil.js') ?>"></script>
</body>
</html>
