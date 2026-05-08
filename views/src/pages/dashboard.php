<?php
$titulo = "Dashboard";
$textTop = "Menu de Edição";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>


<!-- main mobile -->
<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Editar detalhes do interclasse</p>
    <section>
        <a href="./modalidades.php" id="linkModalidades" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Modalidades</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./edicao_arrecadacao.php" id="linkArrecadacao" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-basket fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Arrecadação</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./edicao_pontuacao.php" id="linkRegulamentos" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-award fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Pontuações</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./edicao_categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-bookmarks fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./edicao_agenda.php" id="linkAgenda" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-calendar fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Agenda</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>

        <a href="./colaboradores.php" id="linkColaboradores" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-people fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Colaboradores</h2>
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
        <div id="avisoFinalizacaoInterclasse" class="d-none alert alert-warning d-flex justify-content-between align-items-center mb-4">
            <span>Esta edição ainda não foi finalizada. Conclua as etapas para ativá-la.</span>
            <a id="linkConcluirInterclasse" class="btn btn-sm btn-danger" href="#">Concluir criação</a>
        </div>

        <div class="row g-4 mt-2">

            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_modalidades.php" id="linkModalidades" class="dash-card">
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
                <a href="edicao_pontuacao.php" id="linkPontuacoes" class="dash-card">
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
                <a href="edicao_agenda.php" id="linkAgenda" class="dash-card">
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
                <a href="edicao_arrecadacao.php" id="linkArrecadacoes" class="dash-card">
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
                <a href="edicao_categorias.php" id="linkCategorias" class="dash-card">
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
                <a href="colaboradores.php" id="linkColaboradores" class="dash-card">
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
    async function configurarLinksDashboard() {
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) return;
        const dadosInterclasse = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const aviso = document.getElementById('avisoFinalizacaoInterclasse');
        if (dadosInterclasse && String(dadosInterclasse.status_interclasse) !== '1') {
            aviso.classList.remove('d-none');
            document.getElementById('linkConcluirInterclasse').href = `./edicao_resumo.php?id=${idInterclasse}`;
        } else {
            aviso.classList.add('d-none');
        }

        document.querySelectorAll('#linkModalidades').forEach(link => {
            link.href = `./edicao_modalidades.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkPontuacoes').forEach(link => {
            link.href = `./edicao_pontuacao.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkArrecadacoes').forEach(link => {
            link.href = `./edicao_arrecadacao.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkRegulamentos').forEach(link => {
            link.href = `./edicao_pontuacao.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkCategorias').forEach(link => {
            link.href = `./edicao_categorias.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkAgenda').forEach(link => {
            link.href = `./edicao_agenda.php?id=${idInterclasse}&modo=view`;
        });
        document.querySelectorAll('#linkColaboradores').forEach(link => {
            link.href = `./colaboradores.php?id=${idInterclasse}&modo=view`;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        configurarLinksDashboard().catch(console.error);
    });
</script>

<?php
require_once '../componentes/footer.php';
?>