<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../../styles/style.css">
</head>
<body>
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100">
        <a href="./aluno_perfil.php"><span class="position-absolute m-4 translate-middle text-white fs-2" style="z-index: 10; top: 3%; right: -20px;" id="btnVoltar"><i class="bi bi-person-gear"></i></span></a>
    </header>
    <main  class="m-auto" style="width: 90%;">
        <h3 class="text-secondary fs-6 text-center my-4">Inscreva-se ou visualize os resultados</h3>
        <section>
            <a href="./aluno_inscricao.php" class="text-decoration-none text-black">
                <div class="shadow d-flex justify-content-between px-4 py-3 mb-3 rounded" >
                    <section>
                        <h2 class="fs-4">Interclasse 2026</h2>
                        <p class="m-0 text-secondary">(Em progresso)</p>
                    </section>
                    <img src="../../../public/icons/arrow-right.svg" alt="icone de seta">
                </div>
            </a>
            <div class="shadow d-flex justify-content-between px-4 py-3 mb-3 rounded" style="background-color: #eaeaeaff;">
                <section>
                    <h2 class="fs-4">Interclasse 2025</h2>
                    <p class="m-0 text-secondary">(Finalizado)</p>
                </section>
                <img src="../../../public/icons/arrow-right.svg" alt="icone de seta">
            </div>
            <div class="shadow d-flex justify-content-between px-4 py-3 mb-3 rounded" style="background-color: #eaeaeaff;">
                <section>
                    <h2 class="fs-4">Interclasse 2024</h2>
                    <p class="m-0 text-secondary">(Finalizado)</p>
                </section>
                <img src="../../../public/icons/arrow-right.svg" alt="icone de seta">
            </div>
        </section>
    </main>
    <nav class="order-last fixed-bottom py-2 order-lg-first bg-danger">
        <ul class="nav justify-content-around fs-1">
            <li><a href="./aluno_home_inscrito.php" class="text-white"><i class="bi bi-house"></i></a></li>
            <li><a href="./aluno_editar_inscricao.php" class="text-white"><i class="bi bi-person"></i></a></li>
            <li><a href="./aluno_ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./aluno_login.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
        </ul>
    </nav>  
</body>
</html>