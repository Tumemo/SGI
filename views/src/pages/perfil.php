<?php
$titulo = "Perfil";
$textTop = "Perfil";
$btnVoltar = true;

session_start();
$conn = null;
$sessionId = $_SESSION['id'] ?? null;
$sessionNome = $_SESSION['nome'] ?? '';
$usuarioPerfil = [
    'nome_usuario' => $sessionNome,
    'matricula_usuario' => $_SESSION['matricula'] ?? '',
    'foto_usuario' => ''
];
try {
    $dbPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';
    require_once $dbPath;
    if ($sessionId && $conn) {
        $id = (int) $sessionId;
        $st = $conn->prepare('SELECT nome_usuario, matricula_usuario, foto_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = \'1\' LIMIT 1');
        if ($st && $st->execute()) {
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) $usuarioPerfil = array_merge($usuarioPerfil, $row);
        }
    }
} catch (Throwable $e) {
    // keep session defaults
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($_SESSION['id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
            exit;
        }
        $id = (int) $_SESSION['id'];

        // Upload de foto
        if (isset($_POST['acao']) && $_POST['acao'] === 'upload_foto') {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
                exit;
            }
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo json_encode(['success' => false, 'message' => 'Formato inválido. Use JPG, PNG, GIF ou WebP.']);
                exit;
            }
            $nomeArquivo = 'user_' . $id . '_' . time() . '.' . $ext;
            $uploadDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destino = $uploadDir . DIRECTORY_SEPARATOR . $nomeArquivo;
            move_uploaded_file($_FILES['foto']['tmp_name'], $destino);

            $st = $conn->prepare('UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?');
            $st->bind_param('si', $nomeArquivo, $id);
            $st->execute();
            $st->close();

            echo json_encode(['success' => true, 'mensagem' => 'Foto atualizada!', 'arquivo' => $nomeArquivo]);
            exit;
        }

        // Atualizar perfil (nome/senha)
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

require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .perfil-page {
        font-weight: 300;
    }
    .perfil-topbar {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #ed1c24;
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }
    .perfil-topbar span {
        font-weight: 400;
    }
    .perfil-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        max-width: 920px;
        margin: 0 auto;
    }
    .perfil-foto-wrap {
        position: relative;
        min-height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f3f3;
        border-right: 1px solid #eee;
    }
    .perfil-foto-circle {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .perfil-foto-circle i {
        font-size: 4.5rem;
        color: #222;
    }
    .perfil-btn-camera {
        position: absolute;
        bottom: 2rem;
        right: 2rem;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #ed1c24;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    .perfil-form-area {
        padding: 2rem 2.5rem;
    }
    .perfil-form-area label {
        font-weight: 300;
        color: #444;
        font-size: 0.95rem;
    }
    .perfil-input {
        background: #f5f5f5 !important;
        border: none !important;
        border-radius: 10px !important;
        font-weight: 300;
    }
    .perfil-btn-editar {
        background-color: #ed1c24;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 0.65rem 2.5rem;
        font-weight: 400;
        width: 100%;
        max-width: 320px;
    }
</style>

<main class="perfil-page d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="./home.php" class="perfil-topbar" id="perfilBackMob">
        <i class="bi bi-arrow-left-circle fs-5"></i>
        <span id="perfilNomeInterMobile">Interclasse</span>
    </a>
    <h2 class="d-flex align-items-center gap-2 text-dark mb-3" style="font-weight: 400; font-size: 1.1rem;">
        <i class="bi bi-person-gear"></i> Perfil
    </h2>
    <div class="perfil-card p-4">
        <div class="text-center mb-4 position-relative d-inline-block w-100">
            <div class="perfil-foto-circle mx-auto overflow-hidden" id="fotoCircleMob">
                <?php $fotoPath = $usuarioPerfil['foto_usuario'] ? '/sgi/uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario']) : ''; ?>
                <img src="<?= $fotoPath ?>" id="fotoImgMob" class="w-100 h-100 object-fit-cover <?= $fotoPath ? '' : 'd-none' ?>" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconMob')?.classList.remove('d-none');">
                <i class="bi bi-person-gear <?= $fotoPath ? 'd-none' : '' ?>" id="fotoIconMob"></i>
            </div>
            <button type="button" class="perfil-btn-camera" id="btnCameraMob" style="bottom: 0; right: calc(50% - 100px);" title="Alterar foto" aria-label="Alterar foto">
                <i class="bi bi-camera"></i>
            </button>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small">Nome</label>
            <p class="fw-semibold fs-5 mb-0" id="perfilNomeMob"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small">RA</label>
            <p class="fw-semibold fs-5 mb-0" id="perfilEmailMob"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></p>
        </div>
        <div class="mb-4">
            <label class="form-label text-muted small">Senha</label>
            <p class="fw-semibold fs-5 mb-0">********</p>
        </div>
        <button type="button" class="perfil-btn-editar d-block mx-auto" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">Editar perfil</button>
    </div>
</main>

<main class="perfil-page d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-2 px-md-4 py-4">
        <a href="./home.php" class="perfil-topbar" id="perfilBackDesk">
            <i class="bi bi-arrow-left-circle fs-5"></i>
            <span id="perfilNomeInterDesk">Interclasse</span>
        </a>
        <h2 class="d-flex align-items-center gap-2 text-dark mb-4" style="font-weight: 400;">
            <i class="bi bi-person-gear"></i> Perfil
        </h2>

        <div class="perfil-card">
            <div class="row g-0">
                <div class="col-md-5 perfil-foto-wrap">
                    <div class="perfil-foto-circle overflow-hidden">
                        <?php $fotoPathDesk = $usuarioPerfil['foto_usuario'] ? '/sgi/uploads/fotosUsuarios/' . rawurlencode($usuarioPerfil['foto_usuario']) : ''; ?>
                        <img src="<?= $fotoPathDesk ?>" id="fotoImgDesk" class="w-100 h-100 object-fit-cover <?= $fotoPathDesk ? '' : 'd-none' ?>" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconDesk')?.classList.remove('d-none');">
                        <i class="bi bi-person-gear <?= $fotoPathDesk ? 'd-none' : '' ?>" id="fotoIconDesk"></i>
                    </div>
                    <button type="button" class="perfil-btn-camera" id="btnCameraDesk" title="Alterar foto" aria-label="Alterar foto">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <div class="col-md-7 perfil-form-area">
                    <div class="mb-3">
                        <label class="form-label text-muted">Nome</label>
                        <p class="fw-semibold fs-5 mb-0" id="perfilNomeDesk"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">RA</label>
                        <p class="fw-semibold fs-5 mb-0" id="perfilEmailDesk"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted">Senha</label>
                        <p class="fw-semibold fs-5 mb-0">********</p>
                    </div>
                    <button type="button" class="perfil-btn-editar" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">Editar perfil</button>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title text-danger" style="font-weight: 400;">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarPerfil">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small">Nome</label>
                        <input type="text" name="nome_usuario" class="form-control rounded-3 perfil-input" id="editarNome" required>
                    </div>
                    <hr>
                    <p class="small text-muted mb-3">Alterar senha (opcional)</p>
                    <div class="mb-3">
                        <label class="form-label small">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control rounded-3 perfil-input" id="editarSenhaAtual">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nova Senha</label>
                        <input type="password" name="nova_senha" class="form-control rounded-3 perfil-input" id="editarNovaSenha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" class="form-control rounded-3 perfil-input" id="editarConfirmarSenha">
                    </div>
                    <div id="msgEditarPerfil" class="small text-center"></div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3" id="btnSalvarPerfil">Salvar</button>
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
        id: <?= json_encode($sessionId ?? 0) ?>
    };
    const API_FOTO = '/sgi/api/foto.php';

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
            const href = `./dashboard.php?id=${encodeURIComponent(id)}`;
            document.getElementById('perfilBackDesk').href = href;
            const mob = document.getElementById('perfilBackMob');
            if (mob) mob.href = href;
        }

            preencherPerfil();

        document.getElementById('formEditarPerfil').addEventListener('submit', salvarPerfil);
    });

    function preencherPerfil() {
        document.getElementById('perfilNomeMob').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailMob').textContent = DADOS_PERFIL.matricula;
        document.getElementById('perfilNomeDesk').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailDesk').textContent = DADOS_PERFIL.matricula;
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
                };
                img.onerror = () => {
                    img.classList.add('d-none');
                    icon.classList.remove('d-none');
                    console.warn('Foto não carregou:', url);
                };
                img.src = url;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('fotoUploadInput');
        const btnCameras = ['btnCameraMob', 'btnCameraDesk'];
        btnCameras.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', () => input.click());
        });

        (async () => {
            try {
                const resp = await fetch(API_FOTO + '?user_id=' + DADOS_PERFIL.id);
                const data = await resp.json();
                if (data.success && data.url) mostrarFoto(data.url);
            } catch (e) {
                console.warn('Erro ao buscar foto:', e);
            }
        })();

        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('foto', file);
            try {
                const resp = await fetch(API_FOTO, { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success) mostrarFoto(data.url);
                else alert(data.mensagem || 'Erro ao enviar foto.');
            } catch (e) {
                alert('Erro de conexão.');
            }
            input.value = '';
        });
    });

    async function salvarPerfil(e) {
        e.preventDefault();
        const msgEl = document.getElementById('msgEditarPerfil');
        const btn = document.getElementById('btnSalvarPerfil');
        msgEl.innerHTML = '';

        const nome = document.getElementById('editarNome').value.trim();
        const senhaAtual = document.getElementById('editarSenhaAtual').value;
        const novaSenha = document.getElementById('editarNovaSenha').value;
        const confirmarSenha = document.getElementById('editarConfirmarSenha').value;

        if (novaSenha && novaSenha !== confirmarSenha) {
            msgEl.innerHTML = '<span class="text-danger">As senhas não coincidem.</span>';
            return;
        }

        const fd = new FormData();
        fd.append('salvar_perfil', '1');
        fd.append('nome_usuario', nome);
        if (novaSenha) {
            fd.append('senha_atual', senhaAtual);
            fd.append('nova_senha', novaSenha);
        }

        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await resp.json();

            if (data.success) {
                msgEl.innerHTML = '<span class="text-success">' + data.message + '</span>';
                DADOS_PERFIL.nome = nome;
                preencherPerfil();
                document.getElementById('editarSenhaAtual').value = '';
                document.getElementById('editarNovaSenha').value = '';
                document.getElementById('editarConfirmarSenha').value = '';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarPerfil'))?.hide();
                    msgEl.innerHTML = '';
                }, 1500);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao salvar.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Salvar';
        }
    }
</script>

<?php
require_once '../componentes/footer.php';
