<?php
$tituloPagina = 'SGI - Detalhes da Modalidade';
$titulo = 'Modalidade';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';

$cssExtra = '
/* ══ Detalhes da Modalidade — layout reformulado ══ */
.mdd-container { width: 100%; max-width: none; margin: 0 auto; }
.mdd-container--mobile { padding: 0 16px; }

.mdd-head { margin-bottom: 26px; }
.mdd-head__kicker {
    font-size: 0.78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: #9ca3af; margin: 0 0 6px 0;
}
.mdd-head__title {
    font-size: 2rem; font-weight: 800; color: #1f2937;
    margin: 0 0 6px 0; line-height: 1.15;
}
.mdd-head__sub { font-size: 0.95rem; color: #6b7280; margin: 0; }
.mdd-head__sub i { color: #E30613; margin-right: 6px; }

.mdd-hero {
    background: #ffffff; border: 1px solid #e5e7eb;
    border-top: 4px solid #E30613;
    border-radius: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    padding: 28px 30px; margin-bottom: 28px;
}
.mdd-hero__top {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 16px; flex-wrap: wrap; margin-bottom: 22px;
}
.mdd-hero__name { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.mdd-hero__name-text { font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; }
.mdd-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fde8ea; color: #E30613;
    border: 1px solid #f5c2c7;
    font-size: 0.74rem; font-weight: 700;
    padding: 4px 12px; border-radius: 999px; white-space: nowrap;
}
.mdd-hero__actions { display: flex; gap: 10px; flex-wrap: wrap; }
.mdd-btn-edit {
    display: inline-flex; align-items: center; gap: 7px;
    background: #E30613; color: #fff; border: none;
    border-radius: 8px; padding: 9px 18px;
    font-weight: 600; font-size: 0.9rem;
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}
.mdd-btn-edit:hover { background: #c40411; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(227,6,19,0.28); }
.mdd-btn-delete {
    display: inline-flex; align-items: center; gap: 7px;
    background: #ffffff; color: #dc3545;
    border: 1px solid #f1aeb5; border-radius: 8px;
    padding: 9px 18px; font-weight: 600; font-size: 0.9rem;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}
.mdd-btn-delete:hover { background: #dc3545; color: #fff; border-color: #dc3545; transform: translateY(-1px); }
.mdd-btn-edit:disabled, .mdd-btn-delete:disabled { opacity: .6; transform: none; box-shadow: none; }

.mdd-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.mdd-info__item { background: #f8f9fb; border: 1px solid #eef0f3; border-radius: 12px; padding: 16px 18px; }
.mdd-info__label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; margin-bottom: 6px; }
.mdd-info__value { font-size: 1.3rem; font-weight: 800; color: #1f2937; line-height: 1.2; }

.mdd-hero__footer { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; border-top: 1px solid #eef0f3; padding-top: 20px; }
.mdd-stat { display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #eef0f3; border-radius: 12px; padding: 14px 18px; }
.mdd-stat__icon { width: 46px; height: 46px; border-radius: 11px; background: #fde8ea; color: #E30613; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.mdd-stat__num { font-size: 1.6rem; font-weight: 800; color: #E30613; line-height: 1; margin-bottom: 2px; }
.mdd-stat__label { font-size: 0.82rem; color: #6b7280; font-weight: 500; }

.mdd-panel { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.05); padding: 24px; }
.mdd-panel__header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.mdd-panel__icon { width: 38px; height: 38px; border-radius: 9px; background: #fde8ea; color: #E30613; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
.mdd-panel__title { font-size: 1.05rem; font-weight: 700; color: #1f2937; margin: 0; }
.mdd-panel__count { margin-left: auto; background: #f8f9fb; border: 1px solid #eef0f3; color: #6b7280; font-size: 0.78rem; font-weight: 700; padding: 3px 11px; border-radius: 999px; }

.mdd-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; }
.mdd-turma, .mdd-equipe {
    display: flex; align-items: center; gap: 14px;
    background: #ffffff; border: 1px solid #eef0f3; border-radius: 12px;
    padding: 14px 16px;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.mdd-turma:hover, .mdd-equipe:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.06); border-color: #f5c2c7; }
.mdd-turma__icon, .mdd-equipe__icon { width: 42px; height: 42px; border-radius: 10px; background: #fde8ea; color: #E30613; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.mdd-turma__name, .mdd-equipe__name { font-size: 1.05rem; font-weight: 700; color: #1f2937; margin: 0; line-height: 1.2; }
.mdd-turma__meta, .mdd-equipe__meta { font-size: 0.78rem; color: #9ca3af; font-weight: 500; }

.mdd-empty { text-align: center; padding: 30px 16px; border: 1px dashed #e5e7eb; border-radius: 12px; background: #fafbfc; }
.mdd-empty__icon { font-size: 1.7rem; color: #d1d5db; margin-bottom: 8px; }
.mdd-empty__title { font-size: 0.95rem; font-weight: 600; color: #6b7280; margin-bottom: 3px; }
.mdd-empty__desc { font-size: 0.82rem; color: #9ca3af; margin: 0; }

@media (max-width: 991.98px) {
    .mdd-info { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 767.98px) {
    .mdd-head__title { font-size: 1.55rem; }
    .mdd-hero { padding: 20px; }
    .mdd-hero__top { flex-direction: column; }
    .mdd-hero__actions { width: 100%; }
    .mdd-btn-edit, .mdd-btn-delete { flex: 1; justify-content: center; }
    .mdd-info { gap: 10px; }
    .mdd-hero__footer { grid-template-columns: 1fr; gap: 10px; }
    .mdd-list { grid-template-columns: 1fr; }
    .mdd-panel { padding: 18px; }
}
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="mdd-container mdd-container--mobile">
        <div class="mdd-head">
            <div class="mdd-head__kicker">Detalhes da modalidade</div>
            <h2 class="mdd-head__title" id="nomeModalidadeHeadMobile">Modalidade</h2>
            <p class="mdd-head__sub"><i class="bi bi-info-circle"></i> Informações da modalidade</p>
        </div>
        <div id="resumoModalidadeMobile" class="mdd-hero">
            <p class="text-muted m-0">(Carregando modalidade...)</p>
        </div>
        <div class="mdd-panel mb-3">
            <div class="mdd-panel__header">
                <div class="mdd-panel__icon"><i class="bi bi-mortarboard"></i></div>
                <h3 class="mdd-panel__title">Turmas vinculadas</h3>
                <span class="mdd-panel__count" id="countTurmasMobile">0</span>
            </div>
            <div id="listaTurmasMobile" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
        </div>
        <div class="mdd-panel">
            <div class="mdd-panel__header">
                <div class="mdd-panel__icon"><i class="bi bi-people"></i></div>
                <h3 class="mdd-panel__title">Equipes cadastradas</h3>
                <span class="mdd-panel__count" id="countEquipesMobile">0</span>
            </div>
            <div id="listaEquipesMobile" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="mdd-container">
        <a href="#" id="btnVoltarDashboardDesktop" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterModalidadeDet">Interclasse</span>
        </a>
        <div class="mdd-head">
            <div class="mdd-head__kicker">Detalhes da modalidade</div>
            <h2 class="mdd-head__title" id="nomeModalidadeHeadDesktop">Modalidade</h2>
            <p class="mdd-head__sub"><i class="bi bi-info-circle"></i> Informações da modalidade</p>
        </div>
        <div id="resumoModalidadeDesktop" class="mdd-hero">
            <p class="text-muted m-0">(Carregando modalidade...)</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="mdd-panel h-100">
                    <div class="mdd-panel__header">
                        <div class="mdd-panel__icon"><i class="bi bi-mortarboard"></i></div>
                        <h3 class="mdd-panel__title">Turmas vinculadas</h3>
                        <span class="mdd-panel__count" id="countTurmasDesktop">0</span>
                    </div>
                    <div id="listaTurmasDesktop" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mdd-panel h-100">
                    <div class="mdd-panel__header">
                        <div class="mdd-panel__icon"><i class="bi bi-people"></i></div>
                        <h3 class="mdd-panel__title">Equipes cadastradas</h3>
                        <span class="mdd-panel__count" id="countEquipesDesktop">0</span>
                    </div>
                    <div id="listaEquipesDesktop" class="mdd-list"><p class="text-muted">(Carregando...)</p></div>
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
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" id="editMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Equipes por Turma (Opcional):</label>
                        <input type="number" class="form-control" id="editMaxEquipes" min="1">
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
    const PERMITE_EXCLUIR = <?= $isAdmin ? 'true' : 'false' ?>;

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
            const nomeModalidade = esc(modalidade.nome_modalidade);

            ['nomeModalidadeHeadDesktop', 'nomeModalidadeHeadMobile'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = nomeModalidade || 'Modalidade';
            });

            const btnExcluir = PERMITE_EXCLUIR
                ? '<button class="mdd-btn-delete" onclick="excluirModalidade()"><i class="bi bi-trash3"></i> Excluir</button>'
                : '';

            const resumoHtml = `
                <div class="mdd-hero__top">
                    <div class="mdd-hero__name">
                        <h2 class="mdd-hero__name-text">${nomeModalidade}</h2>
                        ${modalidade.nome_categoria ? `<span class="mdd-badge"><i class="bi bi-tag"></i> ${esc(modalidade.nome_categoria)}</span>` : ''}
                    </div>
                    <div class="mdd-hero__actions">
                        <button class="mdd-btn-edit" onclick="abrirModalEdicao()"><i class="bi bi-pencil-square"></i> Editar</button>
                        ${btnExcluir}
                    </div>
                </div>
                <div class="mdd-info">
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Categoria</div>
                        <div class="mdd-info__value">${esc(modalidade.nome_categoria || '-')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Tipo</div>
                        <div class="mdd-info__value">${esc(modalidade.nome_tipo_modalidade || '-')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Limite de inscritos</div>
                        <div class="mdd-info__value">${esc(modalidade.max_inscrito_modalidade || 'Ilimitado')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Máx. equipes por turma</div>
                        <div class="mdd-info__value">${esc(modalidade.max_equipes || 'Ilimitado')}</div>
                    </div>
                </div>
                <div class="mdd-hero__footer">
                    <div class="mdd-stat">
                        <div class="mdd-stat__icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="mdd-stat__num">${qtdEquipes}</div>
                            <div class="mdd-stat__label">Equipes cadastradas</div>
                        </div>
                    </div>
                    <div class="mdd-stat">
                        <div class="mdd-stat__icon"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <div class="mdd-stat__num">${turmasUnicas.length}</div>
                            <div class="mdd-stat__label">Turmas vinculadas</div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('resumoModalidadeDesktop').innerHTML = resumoHtml;
            document.getElementById('resumoModalidadeMobile').innerHTML = resumoHtml;

            const htmlTurmas = turmasUnicas.length
                ? turmasUnicas.map((nome) => `
                    <div class="mdd-turma">
                        <div class="mdd-turma__icon"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="mdd-turma__name">${esc(nome)}</div>
                            <div class="mdd-turma__meta">Turma participante</div>
                        </div>
                    </div>`).join('')
                : `<div class="mdd-empty">
                    <div class="mdd-empty__icon"><i class="bi bi-inbox"></i></div>
                    <div class="mdd-empty__title">Nenhuma turma vinculada</div>
                    <p class="mdd-empty__desc">Esta modalidade ainda não possui turmas vinculadas.</p>
                   </div>`;
            const htmlEquipes = qtdEquipes
                ? equipes.map((item) => `
                    <div class="mdd-equipe">
                        <div class="mdd-equipe__icon"><i class="bi bi-shield-fill"></i></div>
                        <div>
                            <div class="mdd-equipe__name">${item.nome_equipe ? esc(item.nome_equipe) : `Equipe #${esc(item.id_equipe)}`}</div>
                            <div class="mdd-equipe__meta">${esc(item.nome_turma || 'Turma não informada')}</div>
                        </div>
                    </div>`).join('')
                : `<div class="mdd-empty">
                    <div class="mdd-empty__icon"><i class="bi bi-inbox"></i></div>
                    <div class="mdd-empty__title">Nenhuma equipe cadastrada</div>
                    <p class="mdd-empty__desc">Esta modalidade ainda não possui equipes cadastradas.</p>
                   </div>`;

            document.getElementById('listaTurmasDesktop').innerHTML = htmlTurmas;
            document.getElementById('listaTurmasMobile').innerHTML = htmlTurmas;
            document.getElementById('listaEquipesDesktop').innerHTML = htmlEquipes;
            const elEquipesMobile = document.getElementById('listaEquipesMobile');
            if (elEquipesMobile) elEquipesMobile.innerHTML = htmlEquipes;

            const setCount = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            setCount('countTurmasDesktop', turmasUnicas.length);
            setCount('countTurmasMobile', turmasUnicas.length);
            setCount('countEquipesDesktop', qtdEquipes);
            setCount('countEquipesMobile', qtdEquipes);
        } catch (error) {
            document.getElementById('resumoModalidadeDesktop').innerHTML = `<p class="text-danger m-0">${error.message}</p>`;
            document.getElementById('resumoModalidadeMobile').innerHTML = `<p class="text-danger m-0">${error.message}</p>`;
            document.getElementById('listaTurmasDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaTurmasMobile').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaEquipesDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            const elEquipesMobile = document.getElementById('listaEquipesMobile');
            if (elEquipesMobile) elEquipesMobile.innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
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
        document.getElementById('editMaxInscritos').value = modalidadeAtual.max_inscrito_modalidade || '';
        document.getElementById('editMaxEquipes').value = modalidadeAtual.max_equipes || '';
        document.getElementById('msgEditarModalidade').innerHTML = '';

        await Promise.all([
            carregarTiposEdicao(modalidadeAtual.id_tipo_modalidade),
            carregarCategoriasEdicao(modalidadeAtual.categorias_id_categoria)
        ]);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarModalidade'));
        modal.show();
    }

    async function excluirModalidade() {
        if (!modalidadeAtual) return;
        if (!confirm(`Tem certeza que deseja excluir a modalidade "${modalidadeAtual.nome_modalidade}"?`)) return;

        const btn = document.querySelector('.mdd-btn-delete');
        const originalText = btn?.innerHTML || '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-trash3"></i> Excluindo...'; }

        try {
            const resp = await fetch('../../../api/modalidades.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: modalidadeAtual.id_modalidade })
            });
            const data = await resp.json();

            if (!resp.ok || data.success === false) {
                alert(data.message || 'Erro ao excluir.');
                return;
            }

            const params = new URLSearchParams(window.location.search);
            const idInterclasse = params.get('id');
            window.location.href = `./edicao_modalidades.php?id=${idInterclasse}&modo=view`;
        } catch (e) {
            alert('Erro de conexão.');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        }
    }

    document.getElementById('formEditarModalidade').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!modalidadeAtual) return;

        const btn = document.getElementById('btnSalvarEdicao');
        const msg = document.getElementById('msgEditarModalidade');

        const dados = {
            id_modalidade: modalidadeAtual.id_modalidade,
            nome_modalidade: document.getElementById('editNomeModalidade').value.trim(),
            max_inscrito_modalidade: parseInt(document.getElementById('editMaxInscritos').value) || 0,
            max_equipes: (() => { const v = document.getElementById('editMaxEquipes').value; return v === '' ? null : parseInt(v); })(),
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
