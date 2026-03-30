<?php
$titulo = "Ranking";
$textTop = "Ranking";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main class="m-auto" style="width: 90%;">
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

<?php 
require_once '../componentes/navbar.php';
?>