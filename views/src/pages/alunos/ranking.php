<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Ranking Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100">
        <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0">Ranking</h2>
        <a href="./home.php" class="bi bi-arrow-left position-absolute z-3 text-white fs-3 text-decoration-none" style="top: 5%; left: 5%;"></a>
    </header>
    <main class="m-auto" style="width: 90%; margin-bottom: 120px;">
        <section class="mt-3 d-flex flex-wrap gap-2">
            <button class="rounded-2 btn border shadow-sm">Ensino médio</button>
            <button class="rounded-2 btn border shadow-sm">Ensino fundamental</button>
            <button class="rounded-2 btn border shadow-sm">Geral</button>
            <button class="rounded-2 btn border shadow-sm">Arrecadação</button>
        </section>
        <section class="mt-4 d-flex flex-column gap-3">
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border shadow-sm bg-white">
                <div class="d-flex gap-3 align-items-center"><i class="bi bi-award fs-2 text-warning"></i><h2 class="m-0 fs-4">1º <span class="fs-6">Sala A</span></h2></div>
                <h2 class="m-0 fs-4">120 pts</h2>
            </div>
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border shadow-sm bg-white">
                <div class="d-flex gap-3 align-items-center"><i class="bi bi-award fs-2 text-secondary"></i><h2 class="m-0 fs-4">2º <span class="fs-6">Sala A</span></h2></div>
                <h2 class="m-0 fs-4">115 pts</h2>
            </div>
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border shadow-sm bg-white">
                <div class="d-flex gap-3 align-items-center"><i class="bi bi-award fs-2" style="color:#be844f;"></i><h2 class="m-0 fs-4">3º <span class="fs-6">Sala A</span></h2></div>
                <h2 class="m-0 fs-4">110 pts</h2>
            </div>
            <div class="px-4 py-3 border shadow-sm bg-white">
                <div class="d-flex justify-content-between"><h2 class="m-0 fs-4 text-muted">4º <span class="fs-6 text-dark">Sala A</span></h2><h2 class="m-0 fs-5">90 pts</h2></div>
                <div class="progress mt-2" style="height: 5px;"><div class="progress-bar bg-danger" style="width: 76%"></div></div>
            </div>
        </section>
    </main>
    <nav class="fixed-bottom py-2 bg-danger">
        <ul class="nav justify-content-around fs-1">
            <li><a href="./home.php" class="text-white"><i class="bi bi-house"></i></a></li>
            <li><a href="./inscricao.php" class="text-white"><i class="bi bi-people"></i></a></li>
            <li><a href="./ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./login.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
        </ul>
    </nav>
</body>
</html>
