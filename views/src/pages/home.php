<?php
    $titulo = "Home";
    $textTop = '';
    require_once '../components/header.php';
?>

<main class="px-4 mt-4">
    <a href="./novaEdicao.php" class="btn btn-danger p-2 mb-3"><i class="bi bi-plus-circle me-2"></i>Criar Nova Adição</a>
    <section>
        <a href="./edicao.php" class="text-decoration-none text-black">
            <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
                <div>
                    <h2 class="fs-2">Interclasse  2026</h2>
                    <h3 class="fs-6 text-secondary">(Em Andamamento)</h3>
                </div>
                <div>
                    <img src="../../public/icons/arrow.svg" alt="icone de seta">
                </div>
            </div>
        </a>
        <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
            <div>
                <h2 class="fs-2">Interclasse  2025</h2>
                <h3 class="fs-6 text-secondary">(Finalizado)</h3>
            </div>
            <div>
                <img src="../../public/icons/arrow.svg" alt="icone de seta">
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
            <div>
                <h2 class="fs-2">Interclasse  2024</h2>
                <h3 class="fs-6 text-secondary">(Finalizado)</h3>
            </div>
            <div>
                <img src="../../public/icons/arrow.svg" alt="icone de seta">
            </div>
        </div>
    </section>
</main>

    
<?php 
    require_once '../components/navbar.php'
?>
