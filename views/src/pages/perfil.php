<?php
$titulo = "Perfil";
$textTop = "Perfil";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="position-relative d-md-none">
    <section class="mt-3">
        <div class="rounded-circle m-auto shadow" style="width: 130px; height: 130px; background-color: #d9d9d9;"></div>
        <form action="#" method="post" class="m-auto mt-3 text-center" style="width: 90%;">
            <div class="mb-3 text-start ps-2">
                <label for="form" class="form-label">Nome:</label>
                <input type="text" class="form-control" placeholder="nome">
            </div>
            <div class="mb-3 text-start ps-2">
                <label for="form" class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="email">
            </div>
            <div class="mb-5 text-start ps-2">
                <label for="form" class="form-label">Senha</label>
                <input type="password" class="form-control" placeholder="senha">
            </div>
            <input type="submit" value="Editar perfil" class="btn btn-danger">
        </form>
    </section>
</main>


<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid bg-white p-5 pb-0">
        <div class="row">
            
            <div class="col-lg-3 ps-4">
                <div class="d-inline-flex align-items-center bg-danger text-white px-3 py-2 rounded-1 mb-4 shadow-sm" style="cursor: pointer; font-size: 0.9rem;">
                    <i class="bi bi-arrow-left-circle-fill me-2"></i>
                    <span class="fw-bold">Interclasse 2026</span>
                </div>
                
                <h2 class="d-flex align-items-center gap-3 fw-bold text-dark mt-2">
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
                        <label class="col-sm-2 fw-bold text-dark text-nowrap">Nome:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control bg-light border-0 shadow-sm py-2 px-3" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 fw-bold text-dark text-nowrap">Email</label>
                        <div class="col-sm-10">
                            <input type="email" class="form-control bg-light border-0 shadow-sm py-2 px-3" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="row mb-5 align-items-center">
                        <label class="col-sm-2 fw-bold text-dark text-nowrap">Senha:</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control bg-light border-0 shadow-sm py-2 px-3" placeholder="********"  style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-danger px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; font-size: 1rem;">
                            Editar perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<?php

require_once '../componentes/footer.php';
?>