<?php 
$titulo = "Edição ???";
$textTop = "Interclasse ???";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main>
    <p class="text-center mt-3 text-secondary">Editar detalhes o interclasse</p>
    <section>
        <a href="./edicao_modalidades.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0 fs-3">Modalidades</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="./edicao_arrecadacao.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-basket fs-2"></i>
                <h2 class="m-0 fs-3">Arrecadação</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="./edicao_regulamentos.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-journal-bookmark fs-2"></i>
                <h2 class="m-0 fs-3">Regulamento</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="./edicao_categorias.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-person fs-2"></i>
                <h2 class="m-0 fs-3">Categorias</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="./edicao_agenda.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-calendar fs-2"></i>
                <h2 class="m-0 fs-3">Agenda</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="./colaboradores.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
                <i class="bi bi-people fs-2"></i>
                <h2 class="m-0 fs-3">Colaboradores</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
    </section>
</main>
<?php 

require_once '../componentes/footer.php';
?>