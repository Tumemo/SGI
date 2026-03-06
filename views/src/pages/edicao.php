<?php 
$titulo = "Edição";
$textTop = "Interclasse 2026";
require_once '../components/header.php';
?>

<main>
    <section class="px-4 mt-3 text-center">
        <h5 class="fs-6 text-secondary mb-3">Editar detalhes do interclasse</h5>
        <a href="./modalidades.php" class="text-decoration-none text-black">
            <div class="d-flex ps-4 justify-content-between align-items-center shadow p-3 mb-3">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0">Modalidades</h2>
                <img src="../../public/icons/arrow.svg" alt="icone seta">
            </div>
        </a>
        <div class="d-flex  ps-4 justify-content-between align-items-center shadow p-3 mb-3">
            <i class="bi bi-basket fs-2"></i>
            <h2 class="m-0">Arrecadação</h2>
            <img src="../../public/icons/arrow.svg" alt="icone seta">
        </div>
        <div class="d-flex  ps-4 justify-content-between align-items-center shadow p-3 mb-3">
            <i class="bi bi-journal-bookmark fs-2"></i>
            <h2 class="m-0">Regulamento</h2>
            <img src="../../public/icons/arrow.svg" alt="icone seta">
        </div>
        <div class="d-flex  ps-4 justify-content-between align-items-center shadow p-3 mb-3">
            <i class="bi bi-person fs-2"></i>
            <h2 class="m-0">Turmas</h2>
            <img src="../../public/icons/arrow.svg" alt="icone seta">
        </div>
        <div class="d-flex  ps-4 justify-content-between align-items-center shadow p-3 mb-3">
            <i class="bi bi-calendar2-week fs-2"></i>
            <h2 class="m-0">Agenda</h2>
            <img src="../../public/icons/arrow.svg" alt="icone seta">
        </div>
    </section>
</main>
<?php 
    require_once '../components/navbar.php';
?>