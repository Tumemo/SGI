<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Inscrição</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../../styles/style.css">
</head>

<body>
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100">
        <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0">Inscrição</h2>
        <i class="bi bi-arrow-left position-absolute z-3 text-white fs-3" style="top: 5%; left: 5%;"></i>
    </header>
    <main class="m-auto" style="width: 90%;">
        <h3 class="text-secondary fs-6 text-center my-4">Escolha até 3 modalidades</h3>
        <section>
            <div class="shadow d-flex gap-3 align-items-center px-4 py-3 mb-3 rounded">
                <img src="../../../public/icons/bola-basquete.svg" class="ms-5" alt="icone de basquete">
                <h2 class="fs-4 m-0">Basquete</h2>
            </div>
            <div class="shadow d-flex gap-3 align-items-center px-4 py-3 mb-3 rounded bg-danger text-white">
                <img src="../../../public/icons/bola-futsal.svg" class="ms-5" style="filter: invert(1);" alt="icone de futsal">
                <h2 class="fs-4 m-0">Futsal</h2>
            </div>
            <div class="shadow d-flex gap-3 align-items-center px-4 py-3 mb-3 rounded">
                <i class="bi bi-trophy ms-5" style="font-size: 25px;"></i>
                <h2 class="fs-4 m-0">Corrida</h2>
            </div>
        </section>

        <button class="btn btn-dark d-flex gap-2 mt-5 m-auto" data-bs-toggle="modal" data-bs-target="#exampleModal">
            Continuar
        </button>


        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-4 text-center">
                    <h2>Inscrição realizada com sucesso!</h2>
                    <a href="./aluno_home.php">
                        <div class="bg-danger rounded-circle px-2 m-auto" style="width: max-content;">
                            <i class="bi bi-check text-white fs-1"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>