<?php
$titulo = "Perfil";
$textTop = "Perfil";
$btnVoltar = true;
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
            <div class="perfil-foto-circle mx-auto">
                <i class="bi bi-person-gear"></i>
            </div>
            <button type="button" class="perfil-btn-camera" style="bottom: 0; right: calc(50% - 100px);" title="Alterar foto" aria-label="Alterar foto">
                <i class="bi bi-camera"></i>
            </button>
        </div>
        <div class="mb-3">
            <label class="form-label">Nome:</label>
            <input type="text" class="form-control perfil-input" id="perfilNomeMob" placeholder="Nome" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control perfil-input" id="perfilEmailMob" placeholder="email" readonly>
        </div>
        <div class="mb-4">
            <label class="form-label">Senha:</label>
            <input type="password" class="form-control perfil-input" value="........" readonly>
        </div>
        <button type="button" class="perfil-btn-editar d-block mx-auto">Editar perfil</button>
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
                    <div class="perfil-foto-circle">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <button type="button" class="perfil-btn-camera" title="Alterar foto" aria-label="Alterar foto">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <div class="col-md-7 perfil-form-area">
                    <div class="mb-3">
                        <label class="form-label">Nome:</label>
                        <input type="text" class="form-control form-control-lg perfil-input" id="perfilNomeDesk" placeholder="Nome" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-lg perfil-input" id="perfilEmailDesk" placeholder="email" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Senha:</label>
                        <input type="password" class="form-control form-control-lg perfil-input" value="........" readonly>
                    </div>
                    <button type="button" class="perfil-btn-editar">Editar perfil</button>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title text-danger" style="font-weight: 400;">Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="#" method="POST">
                <div class="modal-body px-4">
                    <p class="small text-muted mb-4">Caso sua senha tenha sido resetada, a padrão é 123456.</p>
                    <div class="mb-3">
                        <label class="form-label small">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control rounded-3 perfil-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nova Senha</label>
                        <input type="password" name="nova_senha" class="form-control rounded-3 perfil-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" class="form-control rounded-3 perfil-input" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3">Salvar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        const nome = ativo?.nome_interclasse || 'Interclasse';
        document.getElementById('perfilNomeInterMobile').textContent = nome;
        document.getElementById('perfilNomeInterDesk').textContent = nome;
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');
        if (id) {
            const href = `./dashboard.php?id=${encodeURIComponent(id)}`;
            document.getElementById('perfilBackDesk').href = href;
            const mob = document.getElementById('perfilBackMob');
            if (mob) mob.href = href;
        }
    });
</script>

<?php
require_once '../componentes/footer.php';
