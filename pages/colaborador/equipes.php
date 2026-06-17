<?php
$tituloPagina = 'SGI - Colaborador - Equipes';
$titulo = 'Equipes';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$paginaAtiva = 'equipes';
$cssExtra = '.transition-hover { transition: all 0.2s ease-in-out; } .transition-hover:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important; }';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <section class="container mt-3">
        <div class="mb-3">
            <select id="filtroModalidadeMobile" class="form-select">
                <option value="">Todas as modalidades</option>
            </select>
        </div>
        <div id="listaEquipesMobile">
            <p class="text-muted small text-center">(Carregando equipes...)</p>
        </div>
    </section>

    <div class="position-fixed" style="bottom: 92px; right: 16px; z-index: 20;">
        <button class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 56px; height: 56px; background-color: #ed1c24; border: none;" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
            <i class="bi bi-plus-lg text-white fs-4"></i>
        </button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div style="border-radius: 12px;">
        <div class="mb-4">
            <a href="./dashboard.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0 fs-5">
                    <i class="bi bi-people fs-4 text-dark"></i> Equipes
                </h4>
            </div>
            <div class="mb-3">
                <select id="filtroModalidadeDesktop" class="form-select" style="max-width: 300px;">
                    <option value="">Todas as modalidades</option>
                </select>
            </div>
        </div>
        <div class="row g-4" id="listaEquipesDesktop">
            <p class="text-muted">(Carregando equipes...)</p>
        </div>
    </div>

    <div class="position-fixed d-flex flex-row align-items-center gap-4 py-3 px-5" style="bottom: 0; right: 0; z-index: 1050; background: transparent;">
        <span class="text-muted small fw-medium">Nenhuma equipe criada ainda?</span>
        <button type="button" class="btn bg-white fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="color: #ed1c24; border: 2px solid #ed1c24; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
            <i class="bi bi-plus-circle"></i> Criar equipe
        </button>
    </div>
</main>

<div class="modal fade" id="modalCriarEquipe" tabindex="-1" aria-labelledby="modalCriarEquipeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalCriarEquipeLabel">Criar nova equipe</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCriarEquipe">
                    <div class="mb-3">
                        <label for="selectModalidadeEquipe" class="form-label fw-medium">Modalidade</label>
                        <select id="selectModalidadeEquipe" class="form-select" required>
                            <option value="" selected disabled>Carregando modalidades...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="inputNomeEquipe" class="form-label fw-medium">Nome da equipe</label>
                        <input type="text" class="form-control" id="inputNomeEquipe" placeholder="Ex: Turma A" required>
                    </div>
                    <div id="caixaMensagemEquipe" class="mt-3"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEquipe">Criar equipe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = null;
    let modalidadesLista = [];
    let equipesLista = [];

    async function carregarModalidades() {
        try {
            const response = await axios.get('../../../../api/modalidades.php?x=1');
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidadesLista = modalidades.filter((m) => String(m.interclasses_id_interclasse) === String(idInterclasse));

            const selects = [
                document.getElementById('filtroModalidadeMobile'),
                document.getElementById('filtroModalidadeDesktop'),
                document.getElementById('selectModalidadeEquipe')
            ];

            selects.forEach((sel) => {
                if (!sel) return;
                if (sel.id === 'selectModalidadeEquipe') {
                    sel.innerHTML = '<option value="" selected disabled>Selecione a modalidade</option>';
                } else {
                    sel.innerHTML = '<option value="">Todas as modalidades</option>';
                }
                modalidadesLista.forEach((m) => {
                    sel.innerHTML += `<option value="${m.id_modalidade}">${esc(m.nome_modalidade)}${m.genero_modalidade ? ' (' + esc(m.genero_modalidade) + ')' : ''}</option>`;
                });
            });
        } catch (error) {
            console.error('Erro ao carregar modalidades:', error);
        }
    }

    function renderizarEquipes() {
        const filtroMobile = document.getElementById('filtroModalidadeMobile');
        const filtroDesktop = document.getElementById('filtroModalidadeDesktop');
        const idModalidadeFiltro = filtroMobile?.value || filtroDesktop?.value || '';

        let equipesFiltradas = equipesLista;
        if (idModalidadeFiltro) {
            equipesFiltradas = equipesLista.filter((e) => String(e.modalidades_id_modalidade) === String(idModalidadeFiltro));
        }

        const divMobile = document.getElementById('listaEquipesMobile');
        const divDesktop = document.getElementById('listaEquipesDesktop');

        if (!equipesFiltradas.length) {
            const msg = '<p class="text-muted mt-4 text-center w-100">Nenhuma equipe encontrada.</p>';
            if (divMobile) divMobile.innerHTML = msg;
            if (divDesktop) divDesktop.innerHTML = msg;
            return;
        }

        if (divMobile) {
            divMobile.innerHTML = equipesFiltradas.map((equipe) => `
                <div class="bg-white d-flex align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3 w-100">
                    <i class="bi bi-people fs-4"></i>
                    <div class="text-start px-3 w-100">
                        <h2 class="m-0 fs-5 text-truncate">${esc(equipe.nome_equipe || 'Equipe #' + equipe.id_equipe)}</h2>
                        <p class="m-0 text-muted small">${esc(equipe.nome_turma || '')}${equipe.nome_turma && equipe.nome_modalidade ? ' &middot; ' : ''}${esc(equipe.nome_modalidade || '')}</p>
                    </div>
                </div>
            `).join('');
        }

        if (divDesktop) {
            divDesktop.innerHTML = equipesFiltradas.map((equipe) => `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border border-light-subtle shadow-sm h-100 py-4 px-4" style="border-radius: 10px;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-people fs-4 text-dark"></i>
                            <div>
                                <h5 class="m-0 fw-bold fs-6">${esc(equipe.nome_equipe || 'Equipe #' + equipe.id_equipe)}</h5>
                                <p class="m-0 text-muted small">${esc(equipe.nome_turma || '')}${equipe.nome_turma && equipe.nome_modalidade ? ' &middot; ' : ''}${esc(equipe.nome_modalidade || '')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    async function carregarEquipes() {
        try {
            const response = await axios.get(`../../../../api/equipes.php?id_interclasse=${encodeURIComponent(idInterclasse)}`);
            let equipes = response.data.data || response.data;
            if (!Array.isArray(equipes)) equipes = [];
            equipesLista = equipes;
            renderizarEquipes();
        } catch (error) {
            console.error('Erro ao carregar equipes:', error);
            const divMobile = document.getElementById('listaEquipesMobile');
            const divDesktop = document.getElementById('listaEquipesDesktop');
            const msg = '<p class="text-danger mt-4 text-center w-100">Erro ao carregar equipes.</p>';
            if (divMobile) divMobile.innerHTML = msg;
            if (divDesktop) divDesktop.innerHTML = msg;
        }
    }

    document.getElementById('filtroModalidadeMobile')?.addEventListener('change', renderizarEquipes);
    document.getElementById('filtroModalidadeDesktop')?.addEventListener('change', renderizarEquipes);

    document.getElementById('formCriarEquipe')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarEquipe');
        const msg = document.getElementById('caixaMensagemEquipe');
        const idModalidade = document.getElementById('selectModalidadeEquipe').value;
        const nomeEquipe = document.getElementById('inputNomeEquipe').value.trim();

        if (!idModalidade || !nomeEquipe) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Preencha todos os campos.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';
            msg.innerHTML = '';
            const response = await axios.post('../../../../api/equipes.php', {
                acao: 'criar_equipe',
                modalidades_id_modalidade: idModalidade,
                nome_equipe: nomeEquipe,
                interclasses_id_interclasse: idInterclasse
            });
            if (response.data.success) {
                msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Equipe criada com sucesso!</p>';
                document.getElementById('formCriarEquipe').reset();
                await carregarEquipes();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCriarEquipe')).hide();
                    msg.innerHTML = '';
                }, 1000);
            } else {
                throw new Error(response.data.message || 'Erro ao criar equipe.');
            }
        } catch (error) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Criar equipe';
        }
    });

    window.addEventListener('load', async () => {
        idInterclasse = await window.SGIInterclasse.resolveId();
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo encontrado.');
            window.location.href = 'home.php';
            return;
        }
        await Promise.all([
            carregarModalidades(),
            carregarEquipes()
        ]);
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
