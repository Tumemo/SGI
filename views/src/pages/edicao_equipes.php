<?php
$tituloPagina = 'SGI - Equipes';
$titulo = 'Equipes';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3" style="padding-top: 5rem; padding-bottom: 5rem;">
    <a href="./dashboard.php" id="btnVoltarEquipesMobile" class="btn btn-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <p class="text-secondary text-center small mb-3">Equipes por modalidade e categoria desta edição.</p>

    <div id="filtroCategoriaMobile" class="d-flex flex-nowrap overflow-auto gap-2 pb-2 mb-3"></div>

    <button id="btnCriarEquipeMob" class="btn btn-danger w-100 fw-semibold mb-3 border-0 shadow-sm" style="background-color: #ed1c24; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
        <i class="bi bi-plus-lg me-1"></i> Criar equipe
    </button>
    <div id="listaEquipesMobile" class="d-flex flex-column gap-3"></div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <a href="./dashboard.php" id="btnVoltarEquipesDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-2 px-3 py-2 border-0 text-decoration-none shadow-sm" style="background-color: #ed1c24; border-radius: 6px;">
                    <i class="bi bi-arrow-left-circle fs-5"></i>
                    <span id="nomeInterclasseEquipes" style="font-weight: 400;">Interclasse</span>
                </a>
                <h4 class="text-dark d-flex align-items-center gap-2 mb-0" style="font-weight: 400;">
                    <i class="bi bi-people-fill fs-4"></i> Equipes
                </h4>
            </div>
            <button id="btnCriarEquipeDesk" class="btn btn-danger fw-semibold border-0 shadow-sm px-3 py-2" style="background-color: #ed1c24; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
                    <i class="bi bi-plus-lg me-1"></i> Criar equipe
                </button>
            </div>
            <p class="text-muted small mb-4">Clique em uma categoria para filtrar as modalidades e equipes.</p>
            <div id="filtroCategoria" class="d-flex flex-wrap gap-2 mb-4"></div>
            <div id="listaEquipesDesktop"></div>
        </div>
</main>

<div class="modal fade" id="modalCriarEquipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Criar nova equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCriarEquipe">
                    <label for="selectModalidadeEquipe" class="form-label">Modalidade</label>
                    <select id="selectModalidadeEquipe" class="form-select mb-3" required>
                        <option value="" selected disabled>Carregando modalidades...</option>
                    </select>
                    <label for="selectTurmaEquipe" class="form-label">Turma</label>
                    <select id="selectTurmaEquipe" class="form-select mb-3" required>
                        <option value="" selected disabled>Carregando turmas...</option>
                    </select>
                    <div id="msgCriarEquipe" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEquipe">Criar equipe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    let idInterclasseEq = params.get('id');
    const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

    let modalidadesCache = [];
    let turmasCache = [];

    if (idInterclasseEq) {
        document.getElementById('btnVoltarEquipesMobile').href = `./dashboard.php?id=${idInterclasseEq}`;
        document.getElementById('btnVoltarEquipesDesk').href = `./dashboard.php?id=${idInterclasseEq}`;
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function obterIdCategoriaFiltro() {
        const btn = document.querySelector('#filtroCategoria .active, #filtroCategoriaMobile .active');
        return btn?.dataset.id || '';
    }

    function ativarCategoria(btn) {
        if (!btn) return;
        const id = btn.dataset.id;
        ['filtroCategoria', 'filtroCategoriaMobile'].forEach(idContainer => {
            const c = document.getElementById(idContainer);
            if (!c) return;
            c.querySelectorAll('button').forEach(b => {
                b.classList.toggle('active', b.dataset.id === id);
            });
        });
    }

    async function carregarCategorias() {
        if (!idInterclasseEq) return;
        try {
            const res = await fetch(`${API}categorias.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`);
            const cats = await res.json();
            const lista = Array.isArray(cats) ? cats : [];

            const btns = lista.map((c, i) => `<button class="btn btn-filter-cat${i === 0 ? ' active' : ''}" data-id="${c.id_categoria}">${esc(c.nome_categoria)}</button>`).join('');

            const desk = document.getElementById('filtroCategoria');
            const mob = document.getElementById('filtroCategoriaMobile');
            if (desk) desk.innerHTML = btns;
            if (mob) mob.innerHTML = btns;
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    }

    async function carregarEquipes() {
        const mob = document.getElementById('listaEquipesMobile');
        const desk = document.getElementById('listaEquipesDesktop');
        if (!idInterclasseEq) {
            const msg = '<p class="text-muted text-center">Nenhuma edição selecionada.</p>';
            mob.innerHTML = msg;
            desk.innerHTML = msg;
            return;
        }

        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasseEq);
        if (dados?.nome_interclasse) {
            document.getElementById('nomeInterclasseEquipes').textContent = dados.nome_interclasse;
            window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
        }

        mob.innerHTML = '<p class="text-muted text-center">Carregando…</p>';
        desk.innerHTML = '<p class="text-muted">Carregando…</p>';

        const idCategoriaFiltro = obterIdCategoriaFiltro();

        try {
            if (isAdmin) {
                await fetch(`${API}CriarEquipes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_interclasse: parseInt(idInterclasseEq) })
                });
            }

            let urlMod = `${API}modalidades.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`;
            if (idCategoriaFiltro) {
                urlMod += `&id_categoria=${encodeURIComponent(idCategoriaFiltro)}`;
            }
            const resMod = await fetch(urlMod);
            const modsRaw = await resMod.json();
            let mods = Array.isArray(modsRaw) ? modsRaw : [];

            if (!mods.length) {
                const msg = '<p class="text-muted text-center w-100">Nenhuma modalidade encontrada para o filtro selecionado.</p>';
                mob.innerHTML = msg;
                desk.innerHTML = msg;
                return;
            }

            const porCategoria = {};
            mods.forEach((m) => {
                const cat = m.nome_categoria || 'Categoria';
                if (!porCategoria[cat]) porCategoria[cat] = [];
                porCategoria[cat].push(m);
            });

            let htmlMob = '';
            let htmlDesk = '';

            for (const [nomeCat, listaMod] of Object.entries(porCategoria)) {
                for (const m of listaMod) {
                    const rEq = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(m.id_modalidade)}&_t=${Date.now()}`);
                    const equipes = await rEq.json();
                    const arr = Array.isArray(equipes) ? equipes : [];

                    htmlMob += `<div class="card border-0 shadow-sm rounded-3 mb-2">
                        <div class="card-body py-2 px-3">
                            <div class="small text-muted">${esc(m.nome_modalidade)}</div>`;

                    if (!arr.length) {
                        htmlMob += '<p class="text-muted small mb-0">Nenhuma equipe.</p></div></div>';
                    } else {
                        htmlMob += '<ul class="list-group list-group-flush">';
                        arr.forEach((eq) => {
                            const qElenco = new URLSearchParams({
                                id: idInterclasseEq,
                                id_equipe: String(eq.id_equipe),
                                id_turma: String(eq.turmas_id_turma),
                                id_categoria: String(m.categorias_id_categoria),
                                nome_turma: eq.nome_turma || '',
                                nome_modalidade: m.nome_modalidade || ''
                            });
                            const hrefElenco = `./elenco_equipe.php?${qElenco.toString()}`;
                            const hrefTurma = `./equipes.php?id=${encodeURIComponent(idInterclasseEq)}&id_categoria=${encodeURIComponent(m.categorias_id_categoria)}&id_turma=${encodeURIComponent(eq.turmas_id_turma)}`;
                            htmlMob += `<li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <a class="text-decoration-none text-dark flex-grow-1" href="${hrefTurma}"><span>${esc(eq.nome_turma || 'Turma')}</span></a>
                                    <div class="d-flex gap-1">
                                        ${isAdmin ? `<button class="btn btn-sm btn-outline-danger rounded-3"
                                            onclick="excluirEquipe(${eq.id_equipe}, '${esc(eq.nome_turma || 'Turma')}')"><i class="bi bi-trash"></i> Excluir</button>` : ''}
                                        <a class="btn btn-sm btn-danger rounded-3" href="${hrefElenco}">Elenco</a>
                                    </div>
                                </div>
                            </li>`;
                        });
                        htmlMob += '</ul></div></div>';
                    }

                    htmlDesk += `<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">`;
                    htmlDesk += `<h6 class="mb-3" style="font-weight:400;">${esc(m.nome_modalidade)}</h6>`;

                    if (!arr.length) {
                        htmlDesk += '<p class="text-muted small mb-0">Nenhuma equipe cadastrada nesta modalidade.</p>';
                    } else {
                        htmlDesk += '<div class="table-responsive"><table class="table table-borderless align-middle mb-0"><tbody>';
                        arr.forEach((eq) => {
                            const qElenco = new URLSearchParams({
                                id: idInterclasseEq,
                                id_equipe: String(eq.id_equipe),
                                id_turma: String(eq.turmas_id_turma),
                                id_categoria: String(m.categorias_id_categoria),
                                nome_turma: eq.nome_turma || '',
                                nome_modalidade: m.nome_modalidade || ''
                            });
                            const hrefElenco = `./elenco_equipe.php?${qElenco.toString()}`;
                            const hrefTurma = `./equipes.php?id=${encodeURIComponent(idInterclasseEq)}&id_categoria=${encodeURIComponent(m.categorias_id_categoria)}&id_turma=${encodeURIComponent(eq.turmas_id_turma)}`;
                            htmlDesk += `<tr>
                                <td><a class="text-decoration-none text-dark" href="${hrefTurma}">${esc(eq.nome_turma || 'Turma')}</a></td>
                                <td class="text-end">
                                    ${isAdmin ? `<button class="btn btn-sm btn-outline-danger rounded-3 me-1"
                                        onclick="excluirEquipe(${eq.id_equipe}, '${esc(eq.nome_turma || 'Turma')}')"><i class="bi bi-trash"></i> Excluir</button>` : ''}
                                    <a class="btn btn-sm btn-outline-danger rounded-3" href="${hrefElenco}">Elenco</a>
                                </td>
                            </tr>`;
                        });
                        htmlDesk += '</tbody></table></div>';
                    }

                    htmlDesk += '</div></div>';
                }
            }

            mob.innerHTML = htmlMob;
            desk.innerHTML = htmlDesk;
        } catch (e) {
            console.error(e);
            mob.innerHTML = '<p class="text-danger text-center">Erro ao carregar equipes.</p>';
            desk.innerHTML = '<p class="text-danger">Erro ao carregar equipes.</p>';
        }
    }

    function filtrarTurmasPorModalidade() {
        const selMod = document.getElementById('selectModalidadeEquipe');
        const selTurma = document.getElementById('selectTurmaEquipe');
        const idModalidade = selMod.value;

        if (!idModalidade) {
            selTurma.innerHTML = '<option value="" selected disabled>Selecione uma modalidade primeiro</option>';
            selTurma.disabled = true;
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const idCategoria = mod ? String(mod.categorias_id_categoria) : null;

        const turmasFiltradas = idCategoria
            ? turmasCache.filter(t => String(t.categorias_id_categoria) === idCategoria)
            : turmasCache;

        selTurma.innerHTML = turmasFiltradas.length
            ? '<option value="" selected disabled>Selecione a turma</option>' + turmasFiltradas.map((t) =>
                `<option value="${t.id_turma}">${esc(t.nome_turma)}</option>`
              ).join('')
            : '<option value="" selected disabled>Nenhuma turma nesta categoria</option>';
        selTurma.disabled = !turmasFiltradas.length;
    }

    async function carregarSelectsEquipe() {
        if (!idInterclasseEq) return;
        try {
            const [resMod, resTurmas] = await Promise.all([
                fetch(`${API}modalidades.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`),
                fetch(`${API}turmas.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`)
            ]);
            const modalidades = await resMod.json();
            const turmas = await resTurmas.json();

            modalidadesCache = Array.isArray(modalidades) ? modalidades.filter(
                (m) => String(m.interclasses_id_interclasse) === String(idInterclasseEq)
            ) : [];
            turmasCache = Array.isArray(turmas) ? turmas : [];

            const selMod = document.getElementById('selectModalidadeEquipe');
            selMod.innerHTML = modalidadesCache.length
                ? '<option value="" selected disabled>Selecione a modalidade</option>' + modalidadesCache.map((m) => {
                    const cat = m.nome_categoria ? ` — ${esc(m.nome_categoria)}` : '';
                    return `<option value="${m.id_modalidade}">${esc(m.nome_modalidade)}${cat} (${esc(m.genero_modalidade)})</option>`;
                }).join('')
                : '<option value="" selected disabled>Nenhuma modalidade encontrada</option>';
            selMod.disabled = !modalidadesCache.length;

            filtrarTurmasPorModalidade();
        } catch (e) {
            console.error('Erro ao carregar selects:', e);
        }
    }

    document.getElementById('formCriarEquipe').addEventListener('submit', async function(e) {
        e.preventDefault();
        const idModalidade = document.getElementById('selectModalidadeEquipe').value;
        const idTurma = document.getElementById('selectTurmaEquipe').value;
        const msg = document.getElementById('msgCriarEquipe');
        const btn = document.getElementById('btnSalvarEquipe');

        if (!idModalidade || !idTurma) {
            msg.innerHTML = '<p class="text-danger small">Selecione a modalidade e a turma.</p>';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Criando…';
        msg.innerHTML = '';

        try {
            const resp = await fetch(`${API}equipes.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'criar_equipe',
                    modalidades_id_modalidade: Number(idModalidade),
                    turmas_id_turma: Number(idTurma),
                    status_equipe: '1'
                })
            });
            const data = await resp.json();
            if (data.success === false) throw new Error(data.message || 'Erro ao criar equipe.');

            bootstrap.Modal.getInstance(document.getElementById('modalCriarEquipe')).hide();
            this.reset();
            carregarEquipes();
        } catch (err) {
            msg.innerHTML = `<p class="text-danger small">${esc(err.message)}</p>`;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Criar equipe';
        }
    });

    document.getElementById('modalCriarEquipe').addEventListener('show.bs.modal', carregarSelectsEquipe);
    document.getElementById('selectModalidadeEquipe').addEventListener('change', filtrarTurmasPorModalidade);

    document.getElementById('filtroCategoria')?.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        ativarCategoria(btn);
        carregarEquipes();
    });
    document.getElementById('filtroCategoriaMobile')?.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        ativarCategoria(btn);
        carregarEquipes();
    });

    window.excluirEquipe = async function(id, nome) {
        if (!confirm(`Excluir a equipe "${nome}"?`)) return;
        try {
            const resp = await fetch(`${API}equipes.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_equipe: id })
            });
            const data = await resp.json();
            if (data.success === false) throw new Error(data.message || 'Erro ao excluir.');
            carregarEquipes();
        } catch (err) {
            alert(err.message);
        }
    };

    window.addEventListener('pageshow', async () => {
        if (!isAdmin) {
            const btnMob = document.getElementById('btnCriarEquipeMob');
            const btnDesk = document.getElementById('btnCriarEquipeDesk');
            if (btnMob) btnMob.style.display = 'none';
            if (btnDesk) btnDesk.style.display = 'none';
        }
        if (!idInterclasseEq) {
            const resolved = await window.SGIInterclasse.resolveId();
            if (resolved) {
                idInterclasseEq = resolved;
                document.getElementById('btnVoltarEquipesMobile').href = `./dashboard.php?id=${idInterclasseEq}`;
                document.getElementById('btnVoltarEquipesDesk').href = `./dashboard.php?id=${idInterclasseEq}`;
            }
        }
        await carregarCategorias();
        carregarEquipes();
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
