<?php 
$titulo = "Edição";
$textTop = "Menu de Edição";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Editar detalhes do interclasse</p>
    <section>
        <a href="./edicao_modalidades.php" id="linkModalidades" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Modalidades</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        
        <a href="./edicao_arrecadacao.php" id="linkArrecadacao" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-basket fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Arrecadação</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        
        <a href="./edicao_regulamentos.php" id="linkRegulamentos" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-journal-bookmark fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Regulamento</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        
        <a href="./edicao_categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-person fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        
        <a href="./edicao_agenda.php" id="linkAgenda" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-calendar fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Agenda</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        
        <a href="./colaboradores.php" id="linkColaboradores" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-people fs-2 text-danger"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Colaboradores</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
    </section>
</main>

<script>
    // 1. Pegar o ID da URL atual (Ex: edicao.php?id=5)
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // 2. Se o ID existir, atualizamos todos os links para repassar esse ID
    if (idInterclasse) {
        document.getElementById('linkModalidades').href = `./edicao_modalidades.php?id=${idInterclasse}`;
        document.getElementById('linkArrecadacao').href = `./edicao_arrecadacao.php?id=${idInterclasse}`;
        document.getElementById('linkRegulamentos').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
        document.getElementById('linkCategorias').href = `./edicao_categorias.php?id=${idInterclasse}`;
        document.getElementById('linkAgenda').href = `./edicao_agenda.php?id=${idInterclasse}`;
        document.getElementById('linkColaboradores').href = `./colaboradores.php?id=${idInterclasse}`;
    } else {
        // Alerta opcional de segurança caso a página seja acessada sem ID
        console.warn("Aviso: ID do interclasse não encontrado na URL.");
    }
</script>

<?php 
require_once '../componentes/footer.php';
?>