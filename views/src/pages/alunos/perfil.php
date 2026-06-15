<?php
$tituloPagina = 'SGI - Perfil';
$titulo = 'Perfil';
$mostrarVoltar = true;
$mostrarSino = false;
$urlVoltar = './home.php';

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
    $dbPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($_SESSION['id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
            exit;
        }
        $id = (int) $_SESSION['id'];

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
            $uploadDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios';
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
?>

<style>
    .perfil-page { font-weight: 300; }
    .perfil-foto-circle {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .perfil-foto-circle i { font-size: 4.5rem; color: #222; }
    .perfil-btn-camera {
        position: absolute;
        bottom: 5px;
        right: 5px;
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
    .perfil-input {
        background: #f5f5f5 !important;
        border: none !important;
        border-radius: 10px !important;
        font-weight: 300;
    }
    .perfil-input:focus {
        background: #fff !important;
        box-shadow: 0 0 0 2px rgba(237, 28, 36, 0.15) !important;
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
    .perfil-btn-editar:hover { background-color: #d41920; color: #fff; }
    .perfil-label {
        font-weight: 300;
        color: #444;
        font-size: 0.85rem;
        margin-bottom: 0.15rem;
    }
    .perfil-valor {
        font-weight: 500;
        font-size: 1.1rem;
        margin-bottom: 0;
    }
</style>

<main class="perfil-page d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <h2 class="d-flex align-items-center gap-2 text-dark mb-4" style="font-weight: 400; font-size: 1.1rem;">
        <i class="bi bi-person-gear"></i> Perfil
    </h2>
    <div class="text-center mb-4">
        <div class="position-relative d-inline-block">
            <div class="perfil-foto-circle" id="fotoCircleMob">
                <img src="" id="fotoImgMob" class="w-100 h-100 object-fit-cover d-none" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconMob')?.classList.remove('d-none');">
                <i class="bi bi-person-gear" id="fotoIconMob"></i>
            </div>
            <button type="button" class="perfil-btn-camera" id="btnCameraMob" title="Alterar foto" aria-label="Alterar foto">
                <i class="bi bi-camera"></i>
            </button>
        </div>
    </div>
    <div class="mb-3">
        <div class="perfil-label">Nome</div>
        <div class="perfil-valor" id="perfilNomeMob"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></div>
    </div>
    <div class="mb-3">
        <div class="perfil-label">RA</div>
        <div class="perfil-valor" id="perfilRaMob"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></div>
    </div>
    <div class="mb-4">
        <div class="perfil-label">Senha</div>
        <div class="perfil-valor">********</div>
    </div>
    <button type="button" class="perfil-btn-editar d-block mx-auto" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">Editar perfil</button>
</main>

<main class="perfil-page d-none d-md-block" style="padding-top: 5.5rem;">
    <div class="container-fluid px-2 px-md-4 py-4">
        <h2 class="d-flex align-items-center gap-2 text-dark mb-4" style="font-weight: 400;">
            <i class="bi bi-person-gear"></i> Perfil
        </h2>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="position-relative d-inline-block">
                    <div class="perfil-foto-circle" id="fotoCircleDesk">
                        <img src="" id="fotoImgDesk" class="w-100 h-100 object-fit-cover d-none" alt="Foto" onerror="this.classList.add('d-none');document.getElementById('fotoIconDesk')?.classList.remove('d-none');">
                        <i class="bi bi-person-gear" id="fotoIconDesk"></i>
                    </div>
                    <button type="button" class="perfil-btn-camera" id="btnCameraDesk" title="Alterar foto" aria-label="Alterar foto">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mb-3">
                    <div class="perfil-label">Nome</div>
                    <div class="perfil-valor" id="perfilNomeDesk"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></div>
                </div>
                <div class="mb-3">
                    <div class="perfil-label">RA</div>
                    <div class="perfil-valor" id="perfilRaDesk"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></div>
                </div>
                <div class="mb-4">
                    <div class="perfil-label">Senha</div>
                    <div class="perfil-valor">********</div>
                </div>
                <button type="button" class="perfil-btn-editar" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">Editar perfil</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    const DADOS_PERFIL = {
        nome: <?= json_encode($usuarioPerfil['nome_usuario'] ?? '') ?>,
        matricula: <?= json_encode($usuarioPerfil['matricula_usuario'] ?? '') ?>,
        id: <?= json_encode($sessionId ?? 0) ?>
    };

    document.addEventListener('DOMContentLoaded', async () => {
        preencherPerfil();
        document.getElementById('formEditarPerfil').addEventListener('submit', salvarPerfil);
    });

    function preencherPerfil() {
        document.getElementById('perfilNomeMob').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilRaMob').textContent = DADOS_PERFIL.matricula;
        document.getElementById('perfilNomeDesk').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilRaDesk').textContent = DADOS_PERFIL.matricula;
        const editarNome = document.getElementById('editarNome');
        if (editarNome) editarNome.value = DADOS_PERFIL.nome;
    }

    function mostrarFoto(url) {
        if (!url) return;
        ['Mob', 'Desk'].forEach(suf => {
            const img = document.getElementById('fotoImg' + suf);
            const icon = document.getElementById('fotoIcon' + suf);
            if (img && icon) {
                const load = () => { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                const err = () => { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                img.onload = load;
                img.onerror = err;
                if (img.complete && img.naturalWidth) load();
                img.src = url;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('fotoUploadInput');
        ['btnCameraMob', 'btnCameraDesk'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', () => input.click());
        });

        (async () => {
            try {
                const resp = await fetch('../../../api/foto.php?user_id=' + DADOS_PERFIL.id);
                const data = await resp.json();
                if (data.foto_usuario) mostrarFoto('../../../uploads/fotosUsuarios/' + data.foto_usuario);
            } catch (e) {
                console.warn('Erro ao buscar foto:', e);
            }
        })();

        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('acao', 'upload_foto');
            fd.append('foto', file);
            try {
                const resp = await fetch(window.location.href, { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success) {
                    if (data.arquivo) mostrarFoto('../../../uploads/fotosUsuarios/' + data.arquivo);
                } else {
                    alert(data.mensagem || 'Erro ao enviar foto.');
                }
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

        if (!nome) {
            msgEl.innerHTML = '<span class="text-danger">O nome não pode ficar vazio.</span>';
            return;
        }

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
$paginaAtiva = 'perfil';
include 'componentes/nav.php';
?>
</body>
</html>
