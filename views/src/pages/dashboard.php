<?php 
$titulo = "Dashboard";
$textTop = "Menu de Edição";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Estilos específicos para os Cards do Menu Principal (desktop) */
    .dashboard-container {
        background-color: #f8f9fa; /* Fundo cinza claro */
        min-height: 100vh;
    }

    .dash-card {
        background-color: #ffffff;
        border-radius: 24px;
        position: relative;
        padding: 30px 25px 25px 25px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.06);
        display: flex;
        flex-direction: column;
        height: 100%;
        text-decoration: none;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s;
    }

    .dash-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

  .dash-card-red-corner {
        position: absolute;
        top: 0;
        left: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #ed1c24 50%, transparent 50%);
        border-top-left-radius: 24px;
        z-index: 1;
    }

    .dash-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        position: relative;
        z-index: 2;
    }

    /* O círculo branco do ícone sobrepondo a forma vermelha */
    .dash-card-icon {
        width: 65px;
        height: 65px;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 10px 10px rgba(0,0,0,0.1);
        margin-top: -15px; 
        margin-left: -5px;  
    }

    .dash-card-icon i {
        font-size: 1.8rem;
        color: #111;
    }

    .dash-card-title {
        font-weight: 900;
        color: #000;
        font-size: 1.15rem;
        margin-bottom: 0;
        margin-left: 15px;
        margin-top: -10px;
    }

    .dash-card-text {
        color: #4a4a4a;
        font-size: 0.95rem;
        line-height: 1.5;
        position: relative;
        z-index: 2;
        margin-bottom: 0;
    }
</style>

<!-- main mobile -->
<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Editar detalhes do interclasse</p>
    <section>
        <a href="./modalidades.php" id="linkModalidades" class="text-decoration-none text-black">
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


<!-- main desktop -->
<main class="dashboard-container flex-grow-1 d-none d-md-block container">
    <div class="container-fluid px-0 container">
        
        <div class="row g-4 mt-2">
            
            <div class="col-12 col-md-6 col-lg-4">
                <a href="modalidades.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Saiba o que estamos arrecadando este ano, os pontos de entrega e como a sua doação faz a diferença.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="pontuacoes.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-award"></i>
                        </div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Saiba o que estamos arrecadando este ano, os pontos de entrega e como a sua doação faz a diferença.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="agenda.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Saiba o que estamos arrecadando este ano, os pontos de entrega e como a sua doação faz a diferença.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="arrecadacoes.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-basket"></i>
                        </div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Leia as regras oficiais, entenda o sistema de pontuação e as diretrizes de fair play da competição.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="categorias.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-bookmark"></i>
                        </div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Leia as regras oficiais, entenda o sistema de pontuação e as diretrizes de fair play da competição.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="colaboradores.php" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Leia as regras oficiais, entenda o sistema de pontuação e as diretrizes de fair play da competição.</p>
                </a>
            </div>

        </div>
    </div>
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