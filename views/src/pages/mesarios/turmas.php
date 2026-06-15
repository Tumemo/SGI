<?php
$tituloPagina = 'SGI - Mesário - Turmas';
$titulo = 'Turmas';
$mostrarVoltar = true;
$urlVoltar = './categorias.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<style>
    .turma-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .turma-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>

<main class="d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="./categorias.php" id="btnVoltarTurmasMob" class="btn btn-danger btn-sm mb-3 rounded-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <h5 style="font-weight: 400;" id="tituloCategoriaMob">Turmas</h5>
    <p class="text-muted small" id="subtituloMob">Carregando...</p>

    <input type="text" id="inputBuscaTurmaMobile" class="form-control w-100 mb-3 form-control-sm rounded-pill" placeholder="Buscar turma">

    <div id="listaTurmasMobile" class="d-flex flex-column gap-2">
        <p class="text-muted text-center">Carregando...</p>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 800px;">
        <a href="./categorias.php" id="btnVoltarTurmasDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <h4 style="font-weight: 400;" id="tituloCategoriaDesk">Turmas</h4>
        <p class="text-muted" id="subtituloDesk">Carregando...</p>

        <div class="bg-white rounded-3 shadow-sm p-2 d-flex align-items-center mb-4">
            <i class="bi bi-search text-muted ms-3"></i>
            <input type="text" id="inputBuscaTurma" class="form-control border-0 shadow-none bg-transparent" placeholder="Buscar turma">
        </div>

        <div id="listaTurmas" class="d-flex flex-column gap-3">
            <div class="text-center mt-5">
                <p class="text-muted fs-5">Carregando turmas...</p>
            </div>
        </div>
    </div>
</main>

<script>
    const API = '../../../../api/';
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const idCategoria = urlParams.get('id_categoria');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    if (idInterclasse && idCategoria) {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);
    }

    async function carregarTurmas() {
        const container = document.getElementById('listaTurmas');
        const containerMob = document.getElementById('listaTurmasMobile');
        const subtituloDesk = document.getElementById('subtituloDesk');
        const subtituloMob = document.getElementById('subtituloMob');
        const tituloDesk = document.getElementById('tituloCategoriaDesk');
        const tituloMob = document.getElementById('tituloCategoriaMob');

        if (!idInterclasse || !idCategoria) {
            container.innerHTML = '<div class="text-center mt-5"><p class="text-muted">Parâmetros inválidos.</p></div>';
            containerMob.innerHTML = '<p class="text-muted text-center">Parâmetros inválidos.</p>';
            return;
        }

        try {
            const [resCategoria, resTurmas] = await Promise.all([
                fetch(`${API}categorias.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`${API}turmas.php?id_categoria=${idCategoria}&id_interclasse=${idInterclasse}`).then(r => r.json())
            ]);

            const cat = Array.isArray(resCategoria) ? resCategoria.find(c => String(c.id_categoria) === String(idCategoria)) : null;
            const nomeCat = cat?.nome_categoria || 'Categoria';
            tituloDesk.textContent = `${nomeCat} - Turmas`;
            tituloMob.textContent = `${nomeCat} - Turmas`;

            const turmas = Array.isArray(resTurmas) ? resTurmas : [];

            if (turmas.length === 0) {
                const msg = '<div class="text-center mt-5"><p class="text-muted fs-5">Nenhuma turma encontrada nesta categoria.</p></div>';
                container.innerHTML = msg;
                containerMob.innerHTML = '<p class="text-muted text-center">Nenhuma turma encontrada nesta categoria.</p>';
                subtituloDesk.textContent = '0 turmas';
                subtituloMob.textContent = '0 turmas';
                return;
            }

            subtituloDesk.textContent = `${turmas.length} turma(s)`;
            subtituloMob.textContent = `${turmas.length} turma(s)`;
            renderizarTurmas(turmas);
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-center mt-5"><p class="text-danger">Erro ao carregar turmas.</p></div>';
            containerMob.innerHTML = '<p class="text-danger text-center">Erro ao carregar turmas.</p>';
        }
    }

    let todasTurmas = [];

    function renderizarTurmas(turmas) {
        todasTurmas = turmas;
        const container = document.getElementById('listaTurmas');
        const containerMob = document.getElementById('listaTurmasMobile');
        const html = turmas.map(turma => `
            <a href="./turma_alunos.php?id=${idInterclasse}&id_categoria=${idCategoria}&id_turma=${turma.id_turma}" class="text-decoration-none">
                <div class="turma-card bg-white rounded-3 shadow-sm p-4 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-5">${esc(turma.nome_turma)}</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </a>
        `).join('');
        container.innerHTML = html;
        containerMob.innerHTML = html;
    }

    function filtrarTurmas(termo) {
        const t = (termo || '').toLowerCase();
        const filtradas = todasTurmas.filter(tur => tur.nome_turma.toLowerCase().includes(t));
        renderizarTurmas(filtradas);
    }

    document.getElementById('inputBuscaTurma').addEventListener('input', (e) => {
        const mob = document.getElementById('inputBuscaTurmaMobile');
        if (mob) mob.value = e.target.value;
        filtrarTurmas(e.target.value);
    });
    document.getElementById('inputBuscaTurmaMobile').addEventListener('input', (e) => {
        const desk = document.getElementById('inputBuscaTurma');
        if (desk) desk.value = e.target.value;
        filtrarTurmas(e.target.value);
    });

    document.addEventListener('DOMContentLoaded', carregarTurmas);
</script>

<?php
require_once '../../componentes/footer.php';
?>
