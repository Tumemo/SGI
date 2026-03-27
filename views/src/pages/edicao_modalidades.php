<?php 
$titulo = "Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/header.php';
?>


<main>
    <p class="text-center mt-3 text-secondary">Escolha uma modalidade para editar</p>
    <section>
        <a href="./modalidades.php" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
                <img src="../../public/icons/bola-basquete.svg" alt="bola basquete">
                <h2 class="m-0 fs-3">Basquete</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
            <i class="bi bi-trophy fs-2"></i>
            <h2 class="m-0 fs-3">Corrida</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3" style="width: 80%;">
            <img src="../../public/icons/bola-futsal.svg" alt="bola futsal">
            <h2 class="m-0 fs-3">Futsal</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
    </section>
</main>

<?php 

require_once '../componentes/navbar.php';
?>