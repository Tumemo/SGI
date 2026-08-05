<?php
$tituloPagina = 'SGI - Perfil';
$titulo = 'Perfil';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';

(session_status() === PHP_SESSION_NONE) && session_start();
$conn = null;
$sessionId = $_SESSION['id'] ?? null;
$sessionNome = $_SESSION['nome'] ?? '';
$usuarioPerfil = [
    'nome_usuario' => $sessionNome,
    'matricula_usuario' => $_SESSION['matricula'] ?? '',
    'foto_usuario' => ''
];

$nivelUsuario = (int)($_SESSION['nivel'] ?? 0);

$labelNiveis = [
    0 => ['label' => 'Administrador', 'icon' => 'bi-shield-fill-check', 'color' => '#E30613'],
    1 => ['label' => 'Colaborador',   'icon' => 'bi-person-badge-fill', 'color' => '#0d6efd'],
    2 => ['label' => 'Mesário',       'icon' => 'bi-person-check-fill', 'color' => '#6f42c1'],
    3 => ['label' => 'Usuário',       'icon' => 'bi-person-fill',       'color' => '#198754'],
];
$nivelInfo = $labelNiveis[$nivelUsuario] ?? ['label' => 'Desconhecido', 'icon' => 'bi-question-circle', 'color' => '#6c757d'];

try {
    $dbPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';
    require_once $dbPath;
    if ($sessionId && $conn) {
        $id = (int) $sessionId;
        $st = $conn->prepare('SELECT nome_usuario, matricula_usuario, foto_usuario, nivel_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = \'1\' LIMIT 1');
        if ($st && $st->execute()) {
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) {
                $usuarioPerfil = array_merge($usuarioPerfil, $row);
                $nivelUsuario = (int)($row['nivel_usuario'] ?? $nivelUsuario);
                $nivelInfo = $labelNiveis[$nivelUsuario] ?? ['label' => 'Desconhecido', 'icon' => 'bi-question-circle', 'color' => '#6c757d'];
            }
        }
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($_SESSION['id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
            exit;
        }
        $id = (int) $_SESSION['id'];

        if (isset($_POST['acao']) && $_POST['acao'] === 'remover_foto') {
            $st = $conn->prepare('SELECT foto_usuario FROM usuarios WHERE id_usuario = ?');
            $st->bind_param('i', $id);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();

            if ($row && $row['foto_usuario']) {
                $filePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios' . DIRECTORY_SEPARATOR . $row['foto_usuario'];
                if (file_exists($filePath)) @unlink($filePath);
            }

            $st = $conn->prepare('UPDATE usuarios SET foto_usuario = NULL WHERE id_usuario = ?');
            $st->bind_param('i', $id);
            $st->execute();
            $st->close();

            $_SESSION['foto_usuario'] = null;
            echo json_encode(['success' => true, 'mensagem' => 'Foto removida.']);
            exit;
        }

        if (isset($_POST['salvar_perfil'])) {
            $nome = trim($_POST['nome_usuario'] ?? '');
            if ($nome === '') {
                echo json_encode(['success' => false, 'message' => 'Nome não pode ficar vazio.']);
                exit;
            }
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';

            if ($novaSenha) {
                $st = $conn->prepare('SELECT senha_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1');
                $st->bind_param('i', $id);
                $st->execute();
                $row = $st->get_result()->fetch_assoc();
                $st->close();
                if (!$row || !password_verify($senhaAtual, $row['senha_usuario'])) {
                    echo json_encode(['success' => false, 'message' => 'Senha atual incorreta.']);
                    exit;
                }
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $st = $conn->prepare('UPDATE usuarios SET nome_usuario = ?, senha_usuario = ? WHERE id_usuario = ?');
                $st->bind_param('ssi', $nome, $hash, $id);
                $st->execute();
                $st->close();
            } else {
                $st = $conn->prepare('UPDATE usuarios SET nome_usuario = ? WHERE id_usuario = ?');
                $st->bind_param('si', $nome, $id);
                $st->execute();
                $st->close();
            }
            $_SESSION['nome'] = $nome;
            echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
            exit;
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar.']);
    }
    exit;
}

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'perfil';
?>


<!-- ===================== MOBILE ===================== -->
<main class="perfil-page d-md-none p-3" style="padding-top:5.5rem;padding-bottom:5rem;">
    <a href="<?= htmlspecialchars($urlVoltar) ?>" id="perfilBackMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="perfilNomeInterMobile">Interclasse</span>
    </a>

    <h5 class="fw-bold mb-4">Configurações da Conta</h5>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body text-center py-4">
            <div class="perfil-avatar-ring mx-auto" id="fotoCircleMob">
                <div class="perfil-avatar-inner">
                    <?php $fotoPath = $usuarioPerfil['foto_usuario'] ? '../../uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario']) : ''; ?>
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
            <span class="perfil-badge-nivel" style="--nivel-color:<?= $nivelInfo['color'] ?>">
                <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
            </span>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-danger rounded-pill d-none px-3" id="btnSalvarFotoMob"><i class="bi bi-check-lg me-1"></i>Salvar</button>
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
            <div class="perfil-field"><span class="perfil-field-label"><i class="bi bi-shield-check"></i> Nível</span><span class="perfil-field-value"><span class="perfil-badge-nivel perfil-badge-nivel--sm" style="--nivel-color:<?= $nivelInfo['color'] ?>"><i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?></span></span></div>
            <div class="perfil-field mb-0"><span class="perfil-field-label"><i class="bi bi-key"></i> Alterar</span><span class="perfil-field-value"><button class="btn btn-link btn-sm text-decoration-none p-0 text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAlterarSenha">Alterar senha</button></span></div>
        </div>
    </div>

    <button type="button" class="btn btn-danger w-100 rounded-pill py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
        <i class="bi bi-pencil-square me-2"></i>Editar perfil
    </button>
</main>


<!-- ===================== DESKTOP ===================== -->
<main class="perfil-page main-desktop-layout d-none d-md-block">
    <div class="perfil-wrapper">
        <!-- Topbar -->
        <div class="perfil-topbar mt-5">
            <a href="<?= htmlspecialchars($urlVoltar) ?>" id="perfilBackDesk" class="perfil-btn-voltar btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="perfilNomeInterDesk">Interclasse</span>
            </a>
            <div class="perfil-topbar-title">
                <h1><i class="bi bi-person-circle me-2" style="color:#E30613"></i>Meu Perfil</h1>
                <p class="perfil-topbar-subtitle">Gerencie suas informações, segurança e acompanhe sua participação</p>
            </div>
        </div>

        <!-- Grid: 30% + 70% -->
        <div class="perfil-grid">
            <!-- === COLUNA ESQUERDA: Identidade Visual === -->
            <aside class="perfil-grid-left">
                <div class="card border-0 shadow-sm rounded-4 perfil-card-identity">
                    <div class="card-body text-center py-5 px-4">
                        <div class="perfil-avatar-ring mx-auto" id="fotoCircleDesk">
                            <div class="perfil-avatar-inner">
                                <?php $fotoPathDesk = $usuarioPerfil['foto_usuario'] ? '../../uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario']) : ''; ?>
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
                        <span class="perfil-badge-nivel" style="--nivel-color:<?= $nivelInfo['color'] ?>">
                            <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
                        </span>

                        <div class="d-flex align-items-center justify-content-center gap-1 mt-2" style="font-size:0.8rem;color:#888;">
                            <span class="perfil-status-dot perfil-status-online"></span> Online
                        </div>

                        <div class="d-flex justify-content-center gap-2 mt-4">
                            <button type="button" class="btn btn-sm btn-danger rounded-pill d-none px-3" id="btnSalvarFotoDesk"><i class="bi bi-check-lg me-1"></i>Salvar</button>
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
                                <span class="perfil-info-value"><?= $nivelInfo['label'] ?></span>
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
                                    <span class="perfil-badge-nivel perfil-badge-nivel--sm" style="--nivel-color:<?= $nivelInfo['color'] ?>">
                                        <i class="<?= $nivelInfo['icon'] ?>"></i> <?= $nivelInfo['label'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="perfil-info-item mb-0">
                                <span class="perfil-info-label"><i class="bi bi-shield-plus"></i> Autenticação</span>
                                <span class="perfil-info-value" style="color:#888;font-size:0.85rem;">Senha criptografada (bcrypt)</span>
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

<div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-hidden="true">
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
                    <button type="submit" class="btn btn-danger rounded-3 px-4" id="btnSalvarPerfil"><i class="bi bi-check-lg me-1"></i>Salvar</button>
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
                    <button type="submit" class="btn btn-danger rounded-3 px-4" id="btnSalvarSenha"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<input type="file" id="fotoUploadInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">

<script>
    const DADOS_PERFIL = {
        nome: <?= json_encode($usuarioPerfil['nome_usuario'] ?? '') ?>,
        matricula: <?= json_encode($usuarioPerfil['matricula_usuario'] ?? '') ?>,
        id: <?= json_encode($sessionId ?? 0) ?>,
        nivel: <?= json_encode((int)($nivelUsuario ?? 0)) ?>
    };
    const API_FOTO = '../../../api/foto.php';

    let fotoPreviewFile = null;
    let temFotoAtual = false;

    function esconderSkeleton(suf) {
        const skel = document.getElementById('fotoSkeleton' + suf);
        if (skel) skel.classList.add('d-none');
    }

    function preencherPerfil() {
        document.getElementById('perfilNomeMob').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailMob').textContent = DADOS_PERFIL.matricula;
        document.getElementById('perfilNomeDesk').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailDesk').textContent = DADOS_PERFIL.matricula;
        const nomeInfo = document.getElementById('perfilNomeInfo');
        if (nomeInfo) nomeInfo.textContent = DADOS_PERFIL.nome;
        const editarNome = document.getElementById('editarNome');
        if (editarNome) editarNome.value = DADOS_PERFIL.nome;
    }

    function mostrarFoto(url) {
        if (!url) return;
        ['Mob', 'Desk'].forEach(suf => {
            const img = document.getElementById('fotoImg' + suf);
            const icon = document.getElementById('fotoIcon' + suf);
            if (img && icon) {
                img.onload = () => {
                    img.classList.remove('d-none');
                    icon.classList.add('d-none');
                    esconderSkeleton(suf);
                };
                img.onerror = () => {
                    img.classList.add('d-none');
                    icon.classList.remove('d-none');
                    esconderSkeleton(suf);
                };
                img.src = url;
            }
        });
    }

    function atualizarBotoesFoto() {
        const temPreview = fotoPreviewFile !== null;
        ['Mob', 'Desk'].forEach(suf => {
            const btnSalvar = document.getElementById('btnSalvarFoto' + suf);
            const btnExcluir = document.getElementById('btnExcluirFoto' + suf);
            if (btnSalvar) btnSalvar.classList.toggle('d-none', !temPreview);
            if (btnExcluir) btnExcluir.disabled = !temFotoAtual;
        });
    }

    function mostrarToast(mensagem, tipo) {
        const toastContainer = document.getElementById('perfilToastContainer') || (() => {
            const c = document.createElement('div');
            c.id = 'perfilToastContainer';
            c.className = 'perfil-toast-container';
            document.body.appendChild(c);
            return c;
        })();
        const toast = document.createElement('div');
        toast.className = 'perfil-toast perfil-toast--' + tipo;
        toast.innerHTML = '<i class="bi ' + (tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') + ' me-2"></i>' + mensagem;
        toastContainer.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('perfil-toast--show'));
        setTimeout(() => {
            toast.classList.remove('perfil-toast--show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function toggleCampoSenha(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        btn.innerHTML = '<i class="bi bi-' + (mostrar ? 'eye' : 'eye-slash') + '"></i>';
        btn.setAttribute('aria-label', mostrar ? 'Esconder senha' : 'Mostrar senha');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            const nome = ativo?.nome_interclasse || 'Interclasse';
            document.getElementById('perfilNomeInterMobile').textContent = nome;
            document.getElementById('perfilNomeInterDesk').textContent = nome;
        } catch (e) {}

        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');
        if (id) {
            const href = './dashboard.php?id=' + encodeURIComponent(id);
            document.getElementById('perfilBackDesk').href = href;
            const mob = document.getElementById('perfilBackMob');
            if (mob) mob.href = href;
        }

        preencherPerfil();

        document.querySelectorAll('.perfil-password-eye').forEach(btn => {
            btn.addEventListener('click', () => toggleCampoSenha(btn.dataset.target, btn));
        });

        const input = document.getElementById('fotoUploadInput');
        ['btnCameraMob', 'btnCameraDesk'].forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) btn.addEventListener('click', () => input.click());
        });

        (async () => {
            try {
                const resp = await fetch(API_FOTO + '?user_id=' + DADOS_PERFIL.id);
                const data = await resp.json();
                if (data.success && data.foto_usuario) {
                    temFotoAtual = true;
                    mostrarFoto('../../../uploads/fotosUsuarios/' + data.foto_usuario);
                    atualizarBotoesFoto();
                } else {
                    ['Mob', 'Desk'].forEach(esconderSkeleton);
                }
            } catch (e) {
                ['Mob', 'Desk'].forEach(esconderSkeleton);
            }
        })();

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            mostrarFoto(url);
            fotoPreviewFile = file;
            atualizarBotoesFoto();
            input.value = '';
        });

        document.querySelectorAll('[id^="btnSalvarFoto"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!fotoPreviewFile) return;
                const fd = new FormData();
                fd.append('foto', fotoPreviewFile);
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
                try {
                    const resp = await fetch(API_FOTO, { method: 'POST', body: fd });
                    const data = await resp.json();
                    if (data.success && data.arquivo) {
                        mostrarToast('Foto atualizada com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        mostrarToast(data.mensagem || 'Erro ao enviar foto.', 'error');
                    }
                } catch (e) {
                    mostrarToast('Erro de conexão.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
                }
            });
        });

        document.querySelectorAll('[id^="btnExcluirFoto"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Remover foto de perfil?')) return;
                try {
                    const fd = new FormData();
                    fd.append('acao', 'remover_foto');
                    const resp = await fetch(window.location.href, { method: 'POST', body: fd });
                    const data = await resp.json();
                    if (data.success) {
                        mostrarToast('Foto removida.', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        mostrarToast(data.mensagem || 'Erro ao remover foto.', 'error');
                    }
                } catch (e) {
                    mostrarToast('Erro de conexão.', 'error');
                }
            });
        });

        document.getElementById('formEditarPerfil')?.addEventListener('submit', salvarPerfil);
        document.getElementById('formAlterarSenha')?.addEventListener('submit', salvarSenha);
    });

    async function salvarPerfil(e) {
        e.preventDefault();
        const msgEl = document.getElementById('msgEditarPerfil');
        const btn = document.getElementById('btnSalvarPerfil');
        msgEl.innerHTML = '';
        const nome = document.getElementById('editarNome').value.trim();
        if (!nome) {
            msgEl.innerHTML = '<span class="text-danger">O nome não pode ficar vazio.</span>';
            return;
        }
        const fd = new FormData();
        fd.append('salvar_perfil', '1');
        fd.append('nome_usuario', nome);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                mostrarToast(data.message || 'Perfil atualizado!', 'success');
                DADOS_PERFIL.nome = nome;
                preencherPerfil();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarPerfil'))?.hide();
                    msgEl.innerHTML = '';
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao salvar.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

    async function salvarSenha(e) {
        e.preventDefault();
        const msgEl = document.getElementById('msgAlterarSenha');
        const btn = document.getElementById('btnSalvarSenha');
        msgEl.innerHTML = '';
        const senhaAtual = document.getElementById('editarSenhaAtual').value;
        const novaSenha = document.getElementById('editarNovaSenha').value;
        const confirmarSenha = document.getElementById('editarConfirmarSenha').value;
        if (!senhaAtual || !novaSenha || !confirmarSenha) {
            msgEl.innerHTML = '<span class="text-danger">Preencha todos os campos.</span>';
            return;
        }
        if (novaSenha.length < 6) {
            msgEl.innerHTML = '<span class="text-danger">A nova senha deve ter no mínimo 6 caracteres.</span>';
            return;
        }
        if (novaSenha !== confirmarSenha) {
            msgEl.innerHTML = '<span class="text-danger">As senhas não coincidem.</span>';
            return;
        }
        const fd = new FormData();
        fd.append('salvar_perfil', '1');
        fd.append('nome_usuario', DADOS_PERFIL.nome);
        fd.append('senha_atual', senhaAtual);
        fd.append('nova_senha', novaSenha);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                mostrarToast('Senha alterada com sucesso!', 'success');
                document.getElementById('editarSenhaAtual').value = '';
                document.getElementById('editarNovaSenha').value = '';
                document.getElementById('editarConfirmarSenha').value = '';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAlterarSenha'))?.hide();
                    msgEl.innerHTML = '';
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao alterar senha.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
