<?php
$titulo = "Detalhes da Modalidade";
$textTop = "Modalidade";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <div id="resumoModalidadeMobile" class="bg-white rounded-3 shadow-sm p-3 mb-3">
            <p class="text-muted m-0">(Carregando modalidade...)</p>
        </div>
        <h6 class="fw-bold">Turmas vinculadas</h6>
        <div id="listaTurmasMobile"><p class="text-muted">(Carregando...)</p></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <a href="#" class="btn btn-danger d-inline-flex align-items-center gap-2 mb-4 border-0 shadow-sm text-decoration-none" style="border-radius: 6px; padding: 8px 15px;" id="btnVoltarDashboardDesktop">
            <i class="bi bi-arrow-left-circle fs-5"></i>
            <span id="nomeInterModalidadeDet" style="font-weight: 400;">Interclasse</span>
        </a>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Detalhes da modalidade</h4>
        </div>
        <div id="resumoModalidadeDesktop" class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <p class="text-muted m-0">(Carregando modalidade...)</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="bg-white rounded-3 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-3">Turmas vinculadas</h5>
                    <div id="listaTurmasDesktop"><p class="text-muted">(Carregando...)</p></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bg-white rounded-3 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-3">Equipes cadastradas</h5>
                    <div id="listaEquipesDesktop"><p class="text-muted">(Carregando...)</p></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    async function carregarDetalhesModalidade() {
        const params = new URLSearchParams(window.location.search);
        const idInterclasse = params.get('id');
        const idModalidade = params.get('id_modalidade');
        if (!idInterclasse || !idModalidade) return;

        document.getElementById('btnVoltarDashboardDesktop').href = `./dashboard.php?id=${idInterclasse}`;
        const ic = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        if (ic?.nome_interclasse) {
            const el = document.getElementById('nomeInterModalidadeDet');
            if (el) el.textContent = ic.nome_interclasse;
        }

        try {
            const [resModalidade, resEquipes] = await Promise.all([
                fetch(`../../../api/modalidades.php?id_modalidade=${idModalidade}`),
                fetch(`../../../api/equipes.php?id_modalidade=${idModalidade}`)
            ]);
            const modalidades = await resModalidade.json();
            const equipes = await resEquipes.json();
            const modalidade = (Array.isArray(modalidades) ? modalidades : [])[0];
            if (!modalidade) throw new Error('Modalidade não encontrada.');

            const turmasUnicas = [...new Set((equipes || []).map((item) => item.nome_turma).filter(Boolean))];
            const qtdEquipes = Array.isArray(equipes) ? equipes.length : 0;

            const resumoHtml = `
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">${modalidade.nome_modalidade}</h5>
                        <p class="text-muted mb-1">Categoria: ${modalidade.nome_categoria || '-'}</p>
                        <p class="text-muted mb-0">Tipo: ${modalidade.nome_tipo_modalidade || '-'}</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-danger mb-2">${qtdEquipes} equipe(s)</div><br>
                        <div class="badge bg-secondary">${turmasUnicas.length} turma(s)</div>
                    </div>
                </div>
            `;
            document.getElementById('resumoModalidadeDesktop').innerHTML = resumoHtml;
            document.getElementById('resumoModalidadeMobile').innerHTML = resumoHtml;

            const htmlTurmas = turmasUnicas.length
                ? turmasUnicas.map((nome) => `<div class="border rounded-3 px-3 py-2 mb-2">${nome}</div>`).join('')
                : '<p class="text-muted mb-0">Nenhuma turma vinculada.</p>';
            const htmlEquipes = qtdEquipes
                ? equipes.map((item) => `<div class="border rounded-3 px-3 py-2 mb-2">Equipe #${item.id_equipe} - ${item.nome_turma || '-'}</div>`).join('')
                : '<p class="text-muted mb-0">Nenhuma equipe cadastrada.</p>';

            document.getElementById('listaTurmasDesktop').innerHTML = htmlTurmas;
            document.getElementById('listaTurmasMobile').innerHTML = htmlTurmas;
            document.getElementById('listaEquipesDesktop').innerHTML = htmlEquipes;
        } catch (error) {
            document.getElementById('resumoModalidadeDesktop').innerHTML = `<p class="text-danger m-0">${error.message}</p>`;
            document.getElementById('resumoModalidadeMobile').innerHTML = `<p class="text-danger m-0">${error.message}</p>`;
            document.getElementById('listaTurmasDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaTurmasMobile').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaEquipesDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
        }
    }

    window.addEventListener('load', carregarDetalhesModalidade);
</script>

<?php
require_once '../componentes/footer.php';
?>
