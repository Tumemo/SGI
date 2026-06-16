<?php
$tituloPagina = 'SGI - Mesário - Dashboard';
$titulo = 'Dashboard';
$mostrarVoltar = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<!-- main mobile -->
<main class="d-md-none" style="margin-bottom: 120px;">
    <a href="./home.php" class="btn btn-outline-danger btn-sm ms-3 mt-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-house"></i> Voltar ao início
    </a>
    <p class="text-center mt-3 text-secondary" id="subtituloMobile">Selecione uma opção</p>
    <section>
        <a href="./categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-bookmarks fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./agenda.php" id="linkAgenda" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-calendar fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Agenda</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./pontuacao.php" id="linkChaveamentos" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-diagram-3 fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Chaveamentos</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
    </section>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <a href="./home.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-house"></i> Voltar ao início
        </a>

        <h1 class="fs-2 mb-1" id="tituloDashboard">Dashboard</h1>
        <p class="text-muted mb-4" id="subtituloDesktop">Carregando...</p>

        <div class="row g-4 mt-2">
            <div class="col-12 col-md-6 col-lg-4">
                <a href="categorias.php" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-bookmark"></i>
                        </div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Visualize as categorias da competição, suas turmas, equipes e partidas.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="agenda.php" id="linkAgenda" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Visualize o cronograma dos jogos, acesse o placar e acompanhe os resultados das partidas.</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="pontuacao.php" id="linkChaveamentos" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h5 class="dash-card-title">CHAVEAMENTOS</h5>
                    </div>
                    <p class="dash-card-text">Visualize os chaveamentos e acesse os confrontos das modalidades.</p>
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    (async function() {
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');

        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }

        if (!idInterclasse) {
            document.getElementById('subtituloMobile').textContent = 'Nenhum interclasse selecionado.';
            document.getElementById('subtituloDesktop').textContent = 'Nenhum interclasse selecionado.';
            return;
        }

        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        if (dados) {
            document.getElementById('tituloDashboard').textContent = dados.nome_interclasse;
            document.getElementById('subtituloMobile').textContent = 'Visualize informações da competição';
            document.getElementById('subtituloDesktop').textContent = 'Visualize informações da competição';
            window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
        }

        const modoParam = 'modo=view';

        document.querySelectorAll('#linkCategorias').forEach(link => {
            link.href = `./categorias.php?id=${idInterclasse}&${modoParam}`;
        });
        document.querySelectorAll('#linkAgenda').forEach(link => {
            link.href = `./agenda.php?id=${idInterclasse}&${modoParam}`;
        });
        document.querySelectorAll('#linkChaveamentos').forEach(link => {
            link.href = `./pontuacao.php?id=${idInterclasse}&${modoParam}`;
        });
    })();
</script>

<?php
require_once '../../componentes/footer.php';
?>
