<?php
$titulo = "Equipes";
$textTop = "Equipes";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom:120px;">
    <div class="container mt-3">
        <div id="listaEquipesMobile">
            <p class="text-center text-muted">(Carregando equipes...)</p>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <h4 class="fw-bold mb-4">Equipes da turma</h4>
        <div class="row g-3" id="listaEquipesDesktop">
            <p class="text-center text-muted">(Carregando equipes...)</p>
        </div>
    </div>
</main>

<script>
    async function carregarEquipesPagina() {
        const urlParams = new URLSearchParams(window.location.search);
        const idInterclasse = urlParams.get('id');
        const idTurma = urlParams.get('id_turma');
        const listaMobile = document.getElementById('listaEquipesMobile');
        const listaDesktop = document.getElementById('listaEquipesDesktop');

        if (!idInterclasse || !idTurma) {
            listaMobile.innerHTML = '<p class="text-center text-muted">Turma não selecionada.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-muted">Turma não selecionada.</p>';
            return;
        }

        try {
            const [resTurmas, resEquipes] = await Promise.all([
                fetch(`../../../api/turmas.php?id_turma=${idTurma}&id_interclasse=${idInterclasse}`),
                fetch(`../../../api/equipes.php?id_turma=${idTurma}`)
            ]);
            const turma = (await resTurmas.json())?.[0];
            const equipes = await resEquipes.json();

            window.SGIInterclasse.updatePageTitle(turma?.nome_turma || 'Equipes');

            if (!Array.isArray(equipes) || equipes.length === 0) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma equipe nesta turma.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma equipe nesta turma.</p>';
                return;
            }

            listaMobile.innerHTML = equipes.map((equipe) => `
                <div class="bg-white rounded-3 shadow-sm p-3 mb-3 d-flex justify-content-between">
                    <span class="fw-semibold">${equipe.nome_equipe || `Equipe #${equipe.id_equipe}`}</span>
                    <span class="text-muted small">ID ${equipe.id_equipe}</span>
                </div>
            `).join('');

            listaDesktop.innerHTML = equipes.map((equipe) => `
                <div class="col-md-6 col-lg-4">
                    <div class="bg-white rounded-3 shadow-sm p-4 h-100">
                        <h6 class="fw-bold mb-2">${equipe.nome_equipe || `Equipe #${equipe.id_equipe}`}</h6>
                        <p class="text-muted mb-0">ID da equipe: ${equipe.id_equipe}</p>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error(error);
            listaMobile.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar equipes.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar equipes.</p>';
        }
    }

    window.addEventListener('load', carregarEquipesPagina);
</script>

<?php
require_once '../componentes/footer.php';
?>
