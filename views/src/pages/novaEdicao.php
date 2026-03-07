<?php
$titulo = "Nova Edição";
$textTop = "Nova Edição";
require_once '../components/header.php';
?>

<main class="px-4 mt-4 h-100">

    <!-- card de visualização das modalidades -->
    <a href="./novaEdicao_modalidades.php" class="text-decoration-none text-black">
        <div class="d-flex justify-content-between align-items-center shadow-sm p-3 w-100 mb-3 bg-white rounded">
            <div>
                <h2 class="fs-3">Modalidades</h2>
                <h3 class="fs-6 text-body-tertiary">(Queimada, Futebol, Quatro base, Vôlei, Just dance)</h3>
            </div>
        </div>
    </a>

    <!-- card de vizualização das turmas -->
    <a href="./novaEdicao_turmas.php" class="text-decoration-none text-black">
        <div class="d-flex justify-content-between align-items-center shadow-sm p-3 w-100 mb-3 bg-white rounded">
            <div>
                <h2 class="fs-3">Turmas</h2>
                <h3 class="fs-6 text-body-tertiary">(1° ano EM, 2° ano EM, 3° ano EM, 9° ano, 8° ano, 7° ano)</h3>
            </div>
        </div>
    </a>

    <!-- card de vizualização dos regulamentos -->
    <a href="./novaEdicao_regulamento.php" class="text-decoration-none text-black">
        <div class="d-flex justify-content-between align-items-center shadow-sm p-3 w-100 mb-3 bg-white rounded">
            <div>
                <h2 class="fs-3">Regulamento</h2>
                <h3 class="fs-6 text-body-tertiary">(1° ano EM, 2° ano EM, 3° ano EM, 9° ano, 8° ano, 7° ano)</h3>
            </div>
        </div>
    </a>

    <a href="#" class="btn text-white px-5 m-auto mt-5 position-relative bottom-0 start-50 translate-middle" style="background-color: #e30613;">Continuar</a>

</main>

<?php
require_once '../components/navbar.php';
?>