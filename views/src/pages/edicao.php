<?php 
$titulo = "Edição ???";
$textTop = "Interclasse ???";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main>
    <p class="text-center mt-3 text-secondary">Editar detalhes o interclasse</p>
    <section>
        <a href="./edicao_modalidades.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0 fs-3">Modalidades</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
            <i class="bi bi-basket fs-2"></i>
            <h2 class="m-0 fs-3">Arrecadação</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
            <i class="bi bi-journal-bookmark fs-2"></i>
            <h2 class="m-0 fs-3">Regulamento</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
        <a href="./edicao_turmas.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
                <i class="bi bi-person fs-2"></i>
                <h2 class="m-0 fs-3">Turmas</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
            <i class="bi bi-calendar fs-2"></i>
            <h2 class="m-0 fs-3">Agenda</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
    </section>
</main>
<?php 

require_once '../componentes/navbar.php';
?>