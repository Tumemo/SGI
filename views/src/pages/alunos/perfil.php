<?php
$tituloPagina = 'SGI - Perfil';
$titulo = 'Perfil';
$mostrarVoltar = true;
$mostrarSino = false;
$urlVoltar = './home.php';

(session_status() === PHP_SESSION_NONE) && session_start();
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
        $st = $conn->prepare("SELECT nome_usuario, matricula_usuario, foto_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = '1' LIMIT 1");
        if ($st && $st->execute()) {
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) $usuarioPerfil = array_merge($usuarioPerfil, $row);
        }
    }
} catch (Throwable $e) {
}

// Trata os envios POST (Salvar dados e Foto)
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

// Inclusão da barra lateral antes da estrutura principal
$paginaAtiva = 'perfil';
include 'componentes/nav.php';

// Inclusão do header/banner no topo
include 'componentes/header.php';
?>

<style>
    .perfil-page { 
        font-weight: 300; 
    }
    .perfil-foto-container {
        position: relative;
        width: 160px;
        height: 160px;
        margin: 0 auto;
    }
    .perfil-foto-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 3px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .perfil-foto-circle i { 
        font-size: 4rem; 
        color: #555; 
    }
    .perfil-btn-camera {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ed1c24;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s, background-color 0.2s;
    }
    .perfil-btn-camera:hover {
        transform: scale(1.08);
        background: #d41920;
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
        padding: 0.65rem 2rem;
        font-weight: 400;
        transition: background-color 0.2s;
    }
    .perfil-btn-editar:hover { 
        background-color: #d41920; 
        color: #fff; 
    }
    .perfil-label {
        font-weight: 300;
        color: #666;
        font-size: 0.85rem;
        margin-bottom: 0.15rem;
    }
    .perfil-valor {
        font-weight: 500;
        font-size: 1.1rem;
        color: #222;
        margin-bottom: 0;
    }
</style>

<!-- Conteúdo Centralizado -->
<main class="perfil-page container py-4 flex-grow-1">
    <div class="row justify-content-center mt-3">
        <div class="col-12 col-md-10 col-lg-8">
            <h2 class="d-flex align-items-center gap-2 text-dark mb-4 fs-4 fw-normal">
                <i class="bi bi-person-gear text-danger"></i> Detalhes do Perfil
            </h2>
            
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="row g-4 align-items-center">
                    
                    <!-- Bloco da Foto -->
                    <div class="col-12 col-md-5 text-center border-end-md">
                        <div class="perfil-foto-container mb-3">
                            <div class="perfil-foto-circle">
                                <img src="" id="fotoImg" class="w-100 h-100 object-fit-cover d-none" alt="Foto de Perfil" onerror="this.classList.add('d-none'); document.getElementById('fotoIcon')?.classList.remove('d-none');">
                                <i class="bi bi-person-gear" id="fotoIcon"></i>
                            </div>
                            <button type="button" class="perfil-btn-camera" id="btnCamera" title="Alterar foto" aria-label="Alterar foto">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3" id="btnExcluirFoto" disabled title="Excluir foto">
                                <i class="bi bi-trash me-1"></i> Remover foto
                            </button>
                        </div>
                    </div>

                    <!-- Bloco das Informações -->
                    <div class="col-12 col-md-7 ps-md-4">
                        <div class="mb-3">
                            <div class="perfil-label">Nome Completo</div>
                            <div class="perfil-valor" id="perfilNome"><?= htmlspecialchars($usuarioPerfil['nome_usuario'] ?? '', ENT_QUOTES) ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="perfil-label">Matrícula (RA)</div>
                            <div class="perfil-valor" id="perfilRa"><?= htmlspecialchars($usuarioPerfil['matricula_usuario'] ?? '', ENT_QUOTES) ?></div>
                        </div>

                        <div class="mb-4">
                            <div class="perfil-label">Senha</div>
                            <div class="perfil-valor">••••••••</div>
                        </div>

                        <button type="button" class="perfil-btn-editar w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                            Editar perfil
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Editar Perfil -->
<div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title text-danger fw-normal">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarPerfil">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Nome</label>
                        <input type="text" name="nome_usuario" class="form-control rounded-3 perfil-input" id="editarNome" required>
                    </div>
                    <hr class="my-4 opacity-25">
                    <p class="small text-muted mb-3 fw-semibold">Alterar senha (opcional)</p>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control rounded-3 perfil-input" id="editarSenhaAtual">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Nova Senha</label>
                        <input type="password" name="nova_senha" class="form-control rounded-3 perfil-input" id="editarNovaSenha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" class="form-control rounded-3 perfil-input" id="editarConfirmarSenha">
                    </div>
                    <div id="msgEditarPerfil" class="small text-center mt-2"></div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4" id="btnSalvarPerfil">Salvar</button>
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
    const API_FOTO = '../../../../api/foto.php';
    let temFotoAtual = false;

    document.addEventListener('DOMContentLoaded', async () => {
        const editarNome = document.getElementById('editarNome');
        if (editarNome) editarNome.value = DADOS_PERFIL.nome;

        document.getElementById('formEditarPerfil').addEventListener('submit', salvarPerfil);
        
        const input = document.getElementById('fotoUploadInput');
        document.getElementById('btnCamera')?.addEventListener('click', () => input.click());

        // Carrega foto inicial se existir
        try {
            const resp = await fetch(API_FOTO + '?user_id=' + DADOS_PERFIL.id);
            const data = await resp.json();
            if (data.foto_usuario) {
                temFotoAtual = true;
                mostrarFoto('../../../../uploads/fotosUsuarios/' + data.foto_usuario);
            }
        } catch (e) {
            console.warn('Erro ao carregar foto:', e);
        }

        // Upload de nova foto
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('acao', 'upload_foto');
            fd.append('foto', file);
            try {
                const resp = await fetch(window.location.href, { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success && data.arquivo) {
                    temFotoAtual = true;
                    mostrarFoto('../../../../uploads/fotosUsuarios/' + data.arquivo + '?v=' + new Date().getTime());
                } else {
                    alert(data.message || 'Erro ao enviar foto.');
                }
            } catch (e) {
                alert('Erro na requisição.');
            }
            input.value = '';
        });

        // Remover foto
        document.getElementById('btnExcluirFoto').addEventListener('click', async () => {
            if (!confirm('Remover foto de perfil?')) return;
            try {
                const resp = await fetch(API_FOTO, { method: 'DELETE' });
                const data = await resp.json();
                if (data.success) {
                    temFotoAtual = false;
                    const img = document.getElementById('fotoImg');
                    const icon = document.getElementById('fotoIcon');
                    if (img && icon) {
                        img.classList.add('d-none');
                        img.src = '';
                        icon.classList.remove('d-none');
                    }
                    atualizarBotoes();
                }
            } catch (e) {
                alert('Erro ao excluir foto.');
            }
        });
    });

    function mostrarFoto(url) {
        const img = document.getElementById('fotoImg');
        const icon = document.getElementById('fotoIcon');
        if (img && icon) {
            img.onload = () => { img.classList.remove('d-none'); icon.classList.add('d-none'); };
            img.onerror = () => { img.classList.add('d-none'); icon.classList.remove('d-none'); };
            img.src = url;
        }
        atualizarBotoes();
    }

    function atualizarBotoes() {
        const btn = document.getElementById('btnExcluirFoto');
        if (btn) btn.disabled = !temFotoAtual;
    }

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
                document.getElementById('perfilNome').textContent = nome;
                DADOS_PERFIL.nome = nome;
                
                document.getElementById('editarSenhaAtual').value = '';
                document.getElementById('editarNovaSenha').value = '';
                document.getElementById('editarConfirmarSenha').value = '';

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarPerfil'))?.hide();
                    msgEl.innerHTML = '';
                }, 1200);
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
</body>
</html>