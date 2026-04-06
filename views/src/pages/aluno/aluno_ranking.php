<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Ranking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../../styles/style.css">
</head>

<body>
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100">
        <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0">Ranking</h2>
        <i class="bi bi-arrow-left position-absolute z-3 text-white fs-3" style="top: 5%; left: 5%;"></i>
    </header>
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
 <nav class="order-last fixed-bottom py-2 order-lg-first">
        <ul class="nav justify-content-around fs-1">
            <li><a href="./aluno_home.php" class="text-white"><i class="bi bi-house"></i></a></li>
            <li><a href="./aluno_editar.php" class="text-white"><i class="bi bi-person"></i></a></li>
            <li><a href="./aluno_ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./aluno_login.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
        </ul>
    </nav>  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>