<?php
$tituloPagina = 'SGI - Detalhes da Modalidade';
$titulo = 'Modalidade';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
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

<div class="modal fade" id="modalEditarModalidade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Modalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarModalidade">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="editNomeModalidade" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="editTipoModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="editCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarModalidade" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicao">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let modalidadeAtual = null;

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

            modalidadeAtual = modalidade;

            const turmasUnicas = [...new Set((equipes || []).map((item) => item.nome_turma).filter(Boolean))];
            const qtdEquipes = Array.isArray(equipes) ? equipes.length : 0;

            const resumoHtml = `
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">
                            <span id="nomeModalidadeDisplay">${modalidade.nome_modalidade}</span>
                        </h5>
                        <p class="text-muted mb-1">Categoria: <span id="catModalidadeDisplay">${modalidade.nome_categoria || '-'}</span></p>
                        <p class="text-muted mb-0">Tipo: <span id="tipoModalidadeDisplay">${modalidade.nome_tipo_modalidade || '-'}</span></p>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm btn-outline-primary mb-2" onclick="abrirModalEdicao()">Editar</button><br>
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

    async function carregarTiposEdicao(selectedId) {
        const select = document.getElementById('editTipoModalidade');
        try {
            const resp = await fetch('../../../api/tipoModalidade.php');
            const tipos = await resp.json();
            select.innerHTML = '<option value="" disabled>Selecione...</option>';
            tipos.forEach(t => {
                const sel = t.id_tipo_modalidade == selectedId ? 'selected' : '';
                select.innerHTML += `<option value="${t.id_tipo_modalidade}" ${sel}>${t.nome_tipo_modalidade}</option>`;
            });
        } catch (e) {
            select.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function carregarCategoriasEdicao(selectedId) {
        const params = new URLSearchParams(window.location.search);
        const idInterclasse = params.get('id');
        const select = document.getElementById('editCategoriaModalidade');
        try {
            const resp = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const cats = await resp.json();
            select.innerHTML = '<option value="" disabled>Selecione...</option>';
            cats.forEach(c => {
                const sel = c.id_categoria == selectedId ? 'selected' : '';
                select.innerHTML += `<option value="${c.id_categoria}" ${sel}>${c.nome_categoria}</option>`;
            });
        } catch (e) {
            select.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function abrirModalEdicao() {
        if (!modalidadeAtual) return;

        document.getElementById('editNomeModalidade').value = modalidadeAtual.nome_modalidade;
        document.getElementById('msgEditarModalidade').innerHTML = '';

        await Promise.all([
            carregarTiposEdicao(modalidadeAtual.id_tipo_modalidade),
            carregarCategoriasEdicao(modalidadeAtual.categorias_id_categoria)
        ]);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarModalidade'));
        modal.show();
    }

    document.getElementById('formEditarModalidade').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!modalidadeAtual) return;

        const btn = document.getElementById('btnSalvarEdicao');
        const msg = document.getElementById('msgEditarModalidade');

        const dados = {
            id_modalidade: modalidadeAtual.id_modalidade,
            nome_modalidade: document.getElementById('editNomeModalidade').value.trim(),
            tipos_modalidades_id_tipo_modalidade: parseInt(document.getElementById('editTipoModalidade').value),
            categorias_id_categoria: parseInt(document.getElementById('editCategoriaModalidade').value)
        };

        if (!dados.nome_modalidade) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">O nome não pode estar vazio.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const resp = await fetch('../../../api/modalidades.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao atualizar.');

            msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Salvo com sucesso!</p>';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarModalidade')).hide();
                carregarDetalhesModalidade();
            }, 800);
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });

    window.addEventListener('load', carregarDetalhesModalidade);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
