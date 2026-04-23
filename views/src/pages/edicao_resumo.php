<?php
$titulo = "Resumo";
$textTop = "Interclasse 2026";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="mt-4 d-flex justify-content-center align-items-center flex-column position-relative d-md-none" style="margin-bottom: 120px;">
    <a href="./edicao_modalidades.php" class="text-decoration-none text-dark">
        <div class="m-auto shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1  " style="width: 90%;">
            <div>
                <h2 class="m-0 fs-5">Modalidades</h2>
                <p class="text-secondary m-0 mt-1" style="font-size: 14px;">(Queimada, Futebol, Quatro base, Vôlei, Just dance)</p>
            </div>
        </div>
    </a>

    <a href="./edicao_regulamentos.php" class="text-decoration-none text-dark">
        <div class="m-auto shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1  " style="width: 90%;">
            <div>
                <h2 class="m-0 fs-5">Regulalmentos</h2>
                <p class="text-secondary m-0 mt-1" style="font-size: 14px;">(1° ano EM, 2° ano EM, 3° ano EM, 9° ano, 8° ano, 7° ano)</p>
            </div>
        </div>
    </a>

    <section class="d-flex gap-4 mt-3 position-fixed translate-middle" style="width: max-content;  top: 85%; left: 50%; z-index: 10; cursor: pointer;">
        <a href="./edicao_regulamentos.php" class="btn btn-dark">Voltar</a>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">Adicionar categoria</button>
    </section>
</main>







<!-- main desktop -->
<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block" style="padding-top: 2rem; padding-bottom: 120px;">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 shadow-sm" style="border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>
        </div>

        <div class="d-flex flex-column gap-3 mb-5">
            <div class="bg-white border-0 shadow-sm rounded-2 p-4" style="cursor: pointer; transition: 0.2s ease;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                <h6 class="text-dark fw-medium mb-1">Modalidades</h6>
                <span class="text-secondary" style="font-size: 0.95rem;">
                    (Queimada, Futebol, Quatro base, Vôlei, Just dance)
                </span>
            </div>

            <div class="bg-white border-0 shadow-sm rounded-2 p-4" style="cursor: pointer; transition: 0.2s ease;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                <h6 class="text-dark fw-medium mb-1">Regulamento</h6>
                <span class="text-secondary" style="font-size: 0.95rem;">
                    (1º lugar 10 pontos, 2º lugar 8 pontos, 3º lugar 5 pontos, arrecadação 5 pontos)
                </span>
            </div>
            
            </div>
    </div>

    <div class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, rgba(248,249,250,1) 70%, rgba(248,249,250,0) 100%); padding: 30px 0;">
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3" style="max-width: 80%;">
            <a href="edicao_regulamentos.php" class="text-decoration-none">
                <button class="btn btn-outline-danger bg-white fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Voltar
                </button>
            </a>
            <button class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">
                Adicionar categoria
            </button>
        </div>
    </div>
</main>








<!-- modal (adicionar categoria) -->
<div class="modal fade" id="adicionarCategoria" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-5">
                        <label for="exampleInputEmail1" class="form-label">Insira o nome da categoria:</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="EX: Ensino Médio" require>
                    </div>

                    <div class=" d-flex justify-content-center gap-4 mb-0">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <a href="./edicao_categorias.php" class="btn btn-danger">Criar</a>
                    </div>
                </form>
            </div>
            <div class="mb-1 border border-0">
            </div>
        </div>
    </div>
</div>

<?php
require_once '../componentes/footer.php';
?>