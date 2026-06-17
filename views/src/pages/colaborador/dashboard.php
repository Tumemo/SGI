<?php
$tituloPagina = 'SGI - Colaborador - Dashboard';
$titulo = 'Dashboard';
$mostrarVoltar = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <a href="./home.php" class="btn btn-outline-danger btn-sm ms-3 mt-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-house"></i> Voltar ao início
    </a>
    <p class="text-center mt-3 text-secondary">Painel do colaborador</p>
    <section>
        <a href="./modalidades.php" id="linkModalidades" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Modalidades</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./pontuacoes.php" id="linkPontuacoes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-award fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Pontuações</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./locais.php" id="linkLocais" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-geo-alt fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Locais</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./colaboradores.php" id="linkColaboradores" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-people fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Colaboradores</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./arrecadacoes.php" id="linkArrecadacoes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-basket fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Arrecadações</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-bookmarks fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./turmas.php" id="linkTurmas" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-backpack fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Turmas</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./equipes.php" id="linkEquipes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-diagram-3 fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Equipes</h2>
                <picture><img src="../../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
    </section>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <a href="./home.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-house"></i> Voltar ao início
        </a>
        <div class="row g-4 mt-2">
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./modalidades.php" id="linkModalidades" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Visualize e crie novas modalidades esportivas para a competição.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./pontuacoes.php" id="linkPontuacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-award"></i></div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Acompanhe a tabela de pontos e o desempenho das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./locais.php" id="linkLocais" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="dash-card-title">LOCAIS</h5>
                    </div>
                    <p class="dash-card-text">Cadastre e visualize os locais onde os jogos acontecem.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./colaboradores.php" id="linkColaboradores" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-people"></i></div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie a equipe de apoio e voluntários do evento.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./arrecadacoes.php" id="linkArrecadacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-basket"></i></div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Adicione e acompanhe os itens arrecadados na gincana.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./categorias.php" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Configure as divisões da competição por faixa ou nível.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./turmas.php" id="linkTurmas" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-backpack"></i></div>
                        <h5 class="dash-card-title">TURMAS</h5>
                    </div>
                    <p class="dash-card-text">Visualize as turmas participantes e acesse os alunos.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./equipes.php" id="linkEquipes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">EQUIPES</h5>
                    </div>
                    <p class="dash-card-text">Visualize equipes por modalidade e crie novas equipes.</p>
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    async function configurarLinks() {
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) return;
        document.querySelectorAll('[id^="link"]').forEach(link => {
            const href = link.getAttribute('href');
            if (href && !href.includes('home.php')) {
                link.href = href.split('?')[0] + '?id=' + idInterclasse;
            }
        });
    }
    document.addEventListener('DOMContentLoaded', () => configurarLinks().catch(console.error));
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
