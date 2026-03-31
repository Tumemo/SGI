<?php
$titulo = "Categorias";
$textTop = "Categorias";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main>
    <p class="text-secondary text-center my-3">Editar detalhes interclasse</p>
    <a href="./edicao_turmas.php" class="text-decoration-none text-black">
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 80%;">
            <i class="bi bi-trophy fs-1"></i>
            <h2 class="m-0 fs-3">Categoria I</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
    </a>
    <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 80%;">
        <i class="bi bi-basket fs-1"></i>
        <h2 class="m-0 fs-3">Categoria II</h2>
        <picture>
            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
        </picture>
    </div>

</main>


<?php

require_once '../componentes/navbar.php';
?>