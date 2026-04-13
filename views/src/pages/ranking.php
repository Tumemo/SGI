<?php
$titulo = "Ranking";
$textTop = "Ranking";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- ranking mobile -->
<main class="m-auto d-md-none" style="width: 90%;">
    <!-- botoes de filtros -->
    <section class="mt-3">
        <div class="gap-2">
            <button class="rounded-2 fs btn border border-3 py-1 px-2 shadow-sm" style="height: 32px;">Ensino Médio</button>
            <button class="rounded-2 fs-6 btn border border-3 py-1 px-2 shadow-sm" style="height: 32px;">Ensino Fundamental</button>
        </div>
        <div class="gap-2 mt-2">
            <button class="rounded-2 fs-6 btn border border-3 py-1 px-2 shadow-sm" style="height: 32px;">Geral</button>
            <button class="rounded-2 fs-6 btn border border-3 py-1 px-2 shadow-sm" style="height: 32px;">Arrecadação</button>
        </div>
    </section>

    <!-- ranking -->
    <section class="mt-4">
        <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
            <div class="d-flex gap-3 align-items-center">
                <i class="bi bi-award fs-1" style="color: #a38c41;"></i>
                <h2 class="m-0 fs-4">1º <span class="fs-6">Sala A</span></h2>
            </div>
            <h2 class="m-0 fs-3">120 pts</h2>
        </div>
        <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
            <div class="d-flex gap-3 align-items-center">
                <i class="bi bi-award fs-1" style="color: #8a8a8a;"></i>
                <h2 class="m-0 fs-4">2º <span class="fs-6">Sala B</span></h2>
            </div>
            <h2 class="m-0 fs-3">110 pts</h2>
        </div>
        <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
            <div class="d-flex gap-3 align-items-center">
                <i class="bi bi-award fs-1" style="color: #be844f;"></i>
                <h2 class="m-0 fs-4">3º <span class="fs-6">Sala C</span></h2>
            </div>
            <h2 class="m-0 fs-3">100 pts</h2>
        </div>

        <!-- traço para separar o top3 -->
        <hr class="border border-1 border-dark">


        <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
            <div class="d-flex gap-3 align-items-center">
                <h2 class="m-0 fs-4">4º <span class="fs-6">Sala D</span></h2>
            </div>
            <h2 class="m-0 fs-3">90 pts</h2>
        </div>
        <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
            <div class="d-flex gap-3 align-items-center">
                <h2 class="m-0 fs-4">5º <span class="fs-6">Sala E</span></h2>
            </div>
            <h2 class="m-0 fs-3">80 pts</h2>
        </div>
    </section>
</main>

<!-- ranking desktop -->
<main style="margin-left: 80px; background-color: #f2f2f2;">


    <div class="container-fluid bg-white p-5 d-flex" style="min-height: calc(100vh - 220px);">

        <div class="ps-4 mb-5">
            <div class="d-inline-flex align-items-center bg-danger text-white px-3 py-2 rounded-1 mb-3 shadow-sm" style="cursor: pointer; font-size: 0.9rem;">
                <i class="bi bi-arrow-left-circle-fill me-2"></i>
                <span class="fw-bold">Interclasse 2026</span>
            </div>
            <h1 class="fw-bold text-dark mt-2">Ranking</h1>

            <div class="d-flex gap-2 mb-5">
                <button type="button" class="btn btn-light border rounded-pill px-3 py-1 text-secondary shadow-sm" style="font-size: 0.8rem; background-color: #efefef;">Categoria 1</button>
                <button type="button" class="btn btn-light border rounded-pill px-3 py-1 text-secondary shadow-sm" style="font-size: 0.8rem; background-color: #efefef;">Categoria 2</button>
            </div>
        </div>

        <div class="container" style="max-width: 900px;">

            <div class="card border shadow-sm mb-3 p-3">
                <div class="d-flex align-items-center justify-content-between px-4">
                    <div class="d-flex align-items-center gap-5">
                        <i class="bi bi-award-fill text-warning fs-1"></i>
                        <span class="fs-2 fw-bold">1º</span>
                        <span class="fw-bold text-secondary ms-4">Sala A</span>
                    </div>
                    <span class="fw-bold fs-5">120 pts</span>
                </div>
            </div>

            <div class="card border shadow-sm mb-3 p-3">
                <div class="d-flex align-items-center justify-content-between px-4">
                    <div class="d-flex align-items-center gap-5">
                        <i class="bi bi-award-fill text-secondary fs-1" style="color: #C0C0C0 !important;"></i>
                        <span class="fs-2 fw-bold">2º</span>
                        <span class="fw-bold text-secondary ms-4">Sala A</span>
                    </div>
                    <span class="fw-bold fs-5">115 pts</span>
                </div>
            </div>

            <div class="card border shadow-sm mb-3 p-3">
                <div class="d-flex align-items-center justify-content-between px-4">
                    <div class="d-flex align-items-center gap-5">
                        <i class="bi bi-award-fill fs-1" style="color: #CD7F32;"></i>
                        <span class="fs-2 fw-bold">3º</span>
                        <span class="fw-bold text-secondary ms-4">Sala A</span>
                    </div>
                    <span class="fw-bold fs-5">95 pts</span>
                </div>
            </div>

            <div class="card border shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between px-4 mb-3">
                    <div class="d-flex align-items-center gap-5">
                        <span class="fs-3 fw-bold text-muted" style="width: 45px; text-align: center;">4º</span>
                        <span class="fw-bold text-secondary ms-4">Sala A</span>
                    </div>
                    <span class="fw-bold text-muted">90 pts</span>
                </div>
            </div>

        </div>
    </div>
</main>

<?php
require_once '../componentes/navbar.php';
?>