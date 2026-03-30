<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main class="position-relative">
    <p class="text-center mt-3 text-secondary">Editar detalhes da turma</p>
    <section>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3" style="width: 80%;">
                <h2 class="m-0 fs-4 text">3º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3" style="width: 80%;">
                <h2 class="m-0 fs-4">2º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3" style="width: 80%;">
                <h2 class="m-0 fs-4">1º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
    </section>
    

    <!-- Botão de adição -->
    <div class="bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed position-absolute" style="height: 60px; width: 60px; top: 80%; left: 80%; z-index: 10; cursor: pointer;">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </div>
</main>

<?php 
require_once '../componentes/navbar.php';
?>