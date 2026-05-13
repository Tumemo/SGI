<?php
$titulo = "Perfil";
$textTop = "Perfil";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .change-password-link {
        color: var(--inter-red, #ed1c24);
        text-decoration: none;
        font-size: 0.85rem;
        cursor: pointer;
        display: block;
        margin-top: 5px;
    }

    .btn-cancelar-custom {
        background: transparent;
        color: var(--inter-red, #ed1c24);
        border: 1px solid var(--inter-red, #ed1c24);
        border-radius: 10px;
        padding: 8px 25px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        text-decoration: none;
    }

    .btn-cancelar-custom:hover {
        background-color: #fff5f5;
        color: var(--inter-red, #ed1c24);
    }

    .btn-editar-custom {
        background-color: var(--inter-red, #ed1c24);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 40px;
        transition: 0.3s;
    }
</style>

<main class="position-relative d-md-none">
    <section class="mt-3">
        <div class="rounded-circle m-auto shadow" style="width: 130px; height: 130px; background-color: #d9d9d9;"></div>
        <form action="#" method="post" class="m-auto mt-3 text-center" style="width: 90%;">
            <div class="mb-3 text-start ps-2">
                <label class="form-label">Nome:</label>
                <input type="text" class="form-control" placeholder="nome">
            </div>
            <div class="mb-3 text-start ps-2">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="email">
            </div>
            <div class="mb-5 text-start ps-2">
                <label class="form-label">Senha</label>
                <input type="password" class="form-control" placeholder="********" readonly>
                <a class="change-password-link" data-bs-toggle="modal" data-bs-target="#modalSenha">
                    <i class="bi bi-shield-lock me-1"></i> Alterar sua senha
                </a>
            </div>
            
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn-editar-custom w-100">Editar perfil</button>
                <a href="#" class="btn-cancelar-custom w-100">Cancelar</a>
            </div>
        </form>
    </section>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid bg-white p-5 pb-0">
        <div class="row">
            <div class="col-lg-3 ps-4">
                <div class="d-inline-flex align-items-center bg-danger text-white px-3 py-2 rounded-1 mb-4 shadow-sm" style="cursor: pointer; font-size: 0.9rem;">
                    <i class="bi bi-arrow-left-circle-fill me-2"></i>
                    <span>Interclasse 2026</span>
                </div>
                <h2 class="d-flex align-items-center gap-3 text-dark mt-2">
                    <i class="bi bi-person-gear"></i> Perfil
                </h2>
            </div>

            <div class="col-lg-9 d-flex flex-column align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm mb-5" 
                     style="width: 200px; height: 200px; background-color: #dbdbdb; border: 5px solid #fff;">
                    <i class="bi bi-person-gear text-dark" style="font-size: 5rem;"></i>
                </div>

                <div style="width: 100%; max-width: 600px;">
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 text-dark text-nowrap">Nome:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control bg-light border-0 shadow-sm py-2 px-3" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 text-dark text-nowrap">Email</label>
                        <div class="col-sm-10">
                            <input type="email" class="form-control bg-light border-0 shadow-sm py-2 px-3" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="row mb-5 align-items-start">
                        <label class="col-sm-2 text-dark text-nowrap mt-2">Senha:</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control bg-light border-0 shadow-sm py-2 px-3" placeholder="********" style="border-radius: 8px;" readonly>
                            <a class="change-password-link" data-bs-toggle="modal" data-bs-target="#modalSenha">
                                <i class="bi bi-shield-lock me-1"></i> Alterar sua senha
                            </a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mb-5">
                        <a href="#" class="btn-cancelar-custom">Cancelar</a>
                        <button type="button" class="btn-editar-custom">Editar perfil</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title" style="color: var(--sesi-red);">Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" method="POST">
                <div class="modal-body px-4">
                    <p class="small text-muted mb-4">Caso sua senha tenha sido resetada, a padrão é 123456.</p>
                    <div class="mb-3">
                        <label class="form-label small">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control custom-input-modal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nova Senha</label>
                        <input type="password" name="nova_senha" class="form-control custom-input-modal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" class="form-control custom-input-modal" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn-cancelar-custom" data-bs-dismiss="modal" style="padding: 5px 20px; font-size: 0.9rem;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-editar-custom" style="padding: 7px 25px; font-size: 0.9rem;">Salvar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
require_once '../componentes/footer.php';
?>