<?php
$tituloPagina = 'SGI - Equipes';
include 'componentes/head.php';
include 'componentes/header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3 sgi-inline-d6522d52" >
    <a href="./dashboard.php" id="btnVoltarEquipesMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipesMob">Interclasse</span>
    </a>
    <p class="text-secondary text-center small mb-3">Equipes por modalidade e categoria desta edição.</p>

    <div id="filtroCategoriaMobile" class="d-flex flex-nowrap overflow-auto gap-2 pb-2 mb-3"></div>

    <?php if ($isAdmin): ?>
    <button id="btnCriarEquipeMob" class="btn btn-aluno w-100 fw-semibold mb-3" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
        <i class="bi bi-plus-lg"></i>
    </button>
    <?php endif; ?>
    <div id="listaEquipesMobile" class="d-flex flex-column gap-3"></div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <div class="header-left">
                <a href="./dashboard.php" id="btnVoltarEquipesDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipesDesk">Interclasse</span>
                </a>
                <h1 id="nomeInterclasseEquipes" class="sgi-inline-12a59c06">Equipes</h1>
            </div>
            <?php if ($isAdmin): ?>
            <button id="btnCriarEquipeDesk" class="btn btn-aluno" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">
                <i class="bi bi-plus-lg"></i>
            </button>
            <?php endif; ?>
        </div>

        <div id="filtroCategoria" class="d-flex flex-wrap gap-2 mb-4"></div>

        <div id="listaEquipesDesktop">
            <div class="aluno-loading text-center py-4 text-muted">Carregando...</div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalCriarEquipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sgi-inline-910958c1" >
            <div class="modal-header border-0 sgi-inline-d4384255" >
                <h5 class="modal-title sgi-inline-919e66cc" ><i class="bi bi-plus-circle me-2"></i>Criar nova equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body sgi-inline-e7df035e" >
                <form id="formCriarEquipe">
                    <label for="selectModalidadeEquipe" class="form-label small text-muted fw-semibold">Modalidade</label>
                    <select id="selectModalidadeEquipe" class="form-select mb-3 sgi-inline-0fd5584e"  required>
                        <option value="" selected disabled>Carregando modalidades...</option>
                    </select>
                    <label for="selectTurmaEquipe" class="form-label small text-muted fw-semibold">Turma</label>
                    <select id="selectTurmaEquipe" class="form-select mb-3 sgi-inline-0fd5584e"  required>
                        <option value="" selected disabled>Carregando turmas...</option>
                    </select>
                    <div id="msgCriarEquipe" class="text-center mb-2 small"></div>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-aluno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-aluno" id="btnSalvarEquipe"><i class="bi bi-check-lg"></i></button>
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
    const idCategoriaUrl = params.get('id_categoria');
    const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

    let modalidadesCache = [];
    let turmasCache = [];
    const cardsAbertos = new Map();

    if (idInterclasseEq) {
        ['btnVoltarEquipesMobile', 'btnVoltarEquipesDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasseEq}`;
        });
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function infoEquipe(eq) {
        const excedeu = eq.excedeu_limite === true || eq.excedeu_limite === '1' || eq.excedeu_limite === 1;
        const total = Number(eq.total_alunos) || 0;
        const limite = Number(eq.limite_maximo) || 0;
        let contador = '';
        if (limite > 0) {
            const cor = excedeu ? 'text-danger' : 'text-success';
            contador = `<span class="aluno-equipe-contador ${cor}"><i class="bi bi-people-fill me-1"></i>${total}/${limite}</span>`;
        }
        return { excedeu, contador };
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

            const btns = lista.map(c =>
                `<button class="btn-filter-cat" data-id="${c.id_categoria}">${esc(c.nome_categoria)}</button>`
            ).join('');

            const desk = document.getElementById('filtroCategoria');
            const mob = document.getElementById('filtroCategoriaMobile');
            if (desk) desk.innerHTML = btns;
            if (mob) mob.innerHTML = btns;

            const btnAlvo = document.querySelector(
                `#filtroCategoria [data-id="${idCategoriaUrl}"], #filtroCategoriaMobile [data-id="${idCategoriaUrl}"]`
            ) || document.querySelector('#filtroCategoria button, #filtroCategoriaMobile button');
            if (btnAlvo) ativarCategoria(btnAlvo);
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    }

    function montarCard(m, turmas, equipesPorTurma) {
        let htmlTurmas = '';

        if (!turmas.length) {
            htmlTurmas = '<p class="text-muted small mb-0">Nenhuma turma vinculada a esta modalidade.</p>';
        } else {
            htmlTurmas = turmas.map(t => {
                const idTurma = String(t.id_turma);
                const eqsTurma = equipesPorTurma[idTurma] || [];
                const qtd = eqsTurma.length;
                const temExcedida = eqsTurma.some(eq => infoEquipe(eq).excedeu);
                return `<div class="aluno-turma-item">
                        <div>
                            <div class="fw-semibold">${esc(t.nome_turma)}</div>
                            <div class="aluno-turma-contador"><i class="bi bi-people-fill me-1"></i>${qtd} equipe${qtd === 1 ? '' : 's'}${temExcedida ? '<i class="fas fa-exclamation-triangle aluno-turma-alerta" title="Esta turma possui equipe com alunos acima do limite"></i>' : ''}</div>
                        </div>
                        <button type="button" class="btn btn-aluno btn-sm ver-equipes-btn" data-mod="${m.id_modalidade}" data-turma="${idTurma}" data-turma-nome="${esc(t.nome_turma)}" title="Ver equipes">
                            <i class="fas fa-users-cog"></i>
                        </button>
                    </div>`;
            }).join('');
        }

        return `<div class="aluno-card" data-mod="${m.id_modalidade}" data-mod-cat="${m.categorias_id_categoria}" data-mod-nome="${esc(m.nome_modalidade)}">
            <div class="card-header-custom">
                <span>${esc(m.nome_modalidade)}</span>
                <button type="button" class="btn btn-aluno btn-sm aluno-voltar-btn voltar-btn" title="Voltar às turmas">
                    <i class="bi bi-arrow-left"></i>
                </button>
            </div>
            <div class="card-body-custom">
                <div class="aluno-card-view turmas-view active">${htmlTurmas}</div>
                <div class="aluno-card-view equipes-view">
                    <div class="aluno-equipes-content"></div>
                </div>
            </div>
        </div>`;
    }

    function montarEquipesHtml(eqs, card) {
        if (!eqs.length) {
            return '<p class="text-muted small text-center py-3">Nenhuma equipe nesta turma.</p>';
        }
        const modId = card.dataset.mod;
        const modCat = card.dataset.modCat;
        const modNome = card.dataset.modNome;
        return eqs.map(eq => {
            const info = infoEquipe(eq);
            const qElenco = new URLSearchParams({
                id: idInterclasseEq,
                id_equipe: String(eq.id_equipe),
                id_turma: String(eq.turmas_id_turma),
                id_modalidade: String(modId),
                id_categoria: String(modCat),
                nome_turma: eq.nome_turma || '',
                nome_modalidade: modNome || ''
            });
            const hrefElenco = `./elenco_equipe.php?${qElenco.toString()}`;
            return `<div class="aluno-equipe-item ${info.excedeu ? 'equipe-excedida' : ''}">
                    <div>
                        <div class="aluno-equipe-nome">${esc(eq.nome_equipe || eq.nome_turma)}</div>
                        <div>${info.contador}</div>
                    </div>
                    <div class="d-flex gap-1">
                        <a class="btn btn-aluno btn-sm" href="${hrefElenco}" title="Ver elenco"><i class="bi bi-people-fill"></i></a>
                        ${isAdmin ? `<button class="btn btn-aluno btn-sm" onclick="excluirEquipe(${eq.id_equipe}, '${esc(eq.nome_turma || 'Turma')}')" title="Excluir equipe"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </div>`;
        }).join('');
    }

    async function abrirEquipes(cardEl, modId, turmaId) {
        if (!cardEl) return;
        const turmasView = cardEl.querySelector('.turmas-view');
        const equipesView = cardEl.querySelector('.equipes-view');
        const content = cardEl.querySelector('.aluno-equipes-content');
        if (!equipesView || !content) return;

        cardsAbertos.set(String(modId), String(turmaId));
        cardEl.classList.add('equipes-aberta');
        if (turmasView) turmasView.classList.remove('active');
        equipesView.classList.add('active');

        content.innerHTML = '<p class="text-muted small text-center py-3"><i class="bi bi-hourglass-split me-1"></i>Carregando equipes…</p>';
        try {
            const rEq = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(modId)}&id_turma=${encodeURIComponent(turmaId)}&_t=${Date.now()}`);
            const equipes = await rEq.json();
            const arr = Array.isArray(equipes) ? equipes : [];
            content.innerHTML = montarEquipesHtml(arr, cardEl);
        } catch (e) {
            console.error(e);
            content.innerHTML = '<p class="text-danger small text-center py-3">Erro ao carregar as equipes.</p>';
        }
    }

    function voltarTurmas(cardEl) {
        if (!cardEl) return;
        const turmasView = cardEl.querySelector('.turmas-view');
        const equipesView = cardEl.querySelector('.equipes-view');
        if (turmasView) turmasView.classList.add('active');
        if (equipesView) equipesView.classList.remove('active');
        cardEl.classList.remove('equipes-aberta');
        cardsAbertos.delete(String(cardEl.dataset.mod));
    }

    function restaurarCardsAbertos(container) {
        if (!container) return;
        cardsAbertos.forEach((turmaId, modId) => {
            const cardEl = container.querySelector(`.aluno-card[data-mod="${modId}"]`);
            if (cardEl) abrirEquipes(cardEl, modId, turmaId);
        });
    }

    function handleCardClick(e) {
        const btnEquipes = e.target.closest('.ver-equipes-btn');
        if (btnEquipes) {
            abrirEquipes(btnEquipes.closest('.aluno-card'), btnEquipes.dataset.mod, btnEquipes.dataset.turma);
            return;
        }
        const btnVoltar = e.target.closest('.voltar-btn');
        if (btnVoltar) {
            voltarTurmas(btnVoltar.closest('.aluno-card'));
        }
    }

    async function carregarEquipes() {
        const mob = document.getElementById('listaEquipesMobile');
        const desk = document.getElementById('listaEquipesDesktop');
        if (!idInterclasseEq) {
            mob.innerHTML = '<p class="text-muted text-center">Nenhuma edição selecionada.</p>';
            desk.innerHTML = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-folder-x"></i></div><h5>Nenhuma edição</h5><p>Selecione um interclasse para ver as equipes.</p></div>';
            return;
        }

        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasseEq);
        if (dados?.nome_interclasse) {
            document.getElementById('nomeInterclasseEquipes').textContent = dados.nome_interclasse;
            ['nomeInterclasseEquipesMob', 'nomeInterclasseEquipesDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = dados.nome_interclasse;
            });
            window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
        }

        mob.innerHTML = '<p class="text-muted text-center">Carregando…</p>';
        desk.innerHTML = '<div class="aluno-loading text-center py-4 text-muted">Carregando...</div>';

        const idCategoriaFiltro = obterIdCategoriaFiltro();

        try {
            if (isAdmin) {
                await fetch(`${API}CriarEquipes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_interclasse: parseInt(idInterclasseEq) })
                });
            }

            let urlTurmas = `${API}turmas.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`;
            if (idCategoriaFiltro) {
                urlTurmas += `&id_categoria=${encodeURIComponent(idCategoriaFiltro)}`;
            }
            const resTurmas = await fetch(urlTurmas);
            const turmasRaw = await resTurmas.json();
            const turmas = Array.isArray(turmasRaw) ? turmasRaw : [];

            let urlMod = `${API}modalidades.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`;
            if (idCategoriaFiltro) {
                urlMod += `&id_categoria=${encodeURIComponent(idCategoriaFiltro)}`;
            }
            const resMod = await fetch(urlMod);
            const modsRaw = await resMod.json();
            const mods = Array.isArray(modsRaw) ? modsRaw : [];

            if (!mods.length) {
                mob.innerHTML = '<p class="text-muted text-center w-100">Nenhuma modalidade encontrada para o filtro selecionado.</p>';
                desk.innerHTML = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-folder-x"></i></div><h5>Nenhuma modalidade</h5><p>Nenhuma modalidade encontrada para o filtro selecionado.</p></div>';
                return;
            }

            const porCategoria = {};
            mods.forEach(m => {
                const cat = m.nome_categoria || 'Categoria';
                if (!porCategoria[cat]) porCategoria[cat] = [];
                porCategoria[cat].push(m);
            });

            let htmlMob = '';
            let htmlDesk = '';

            for (const [, listaMod] of Object.entries(porCategoria)) {
                for (const m of listaMod) {
                    const rEq = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(m.id_modalidade)}&_t=${Date.now()}`);
                    const equipes = await rEq.json();
                    const arr = Array.isArray(equipes) ? equipes : [];

                    const equipesPorTurma = {};
                    arr.forEach(eq => {
                        const chave = String(eq.turmas_id_turma);
                        if (!equipesPorTurma[chave]) equipesPorTurma[chave] = [];
                        equipesPorTurma[chave].push(eq);
                    });

                    const turmasDaModalidade = turmas.filter(
                        t => String(t.categorias_id_categoria) === String(m.categorias_id_categoria)
                    );

                    const card = montarCard(m, turmasDaModalidade, equipesPorTurma);
                    htmlMob += card;
                    htmlDesk += card;
                }
            }

            mob.innerHTML = htmlMob;
            desk.innerHTML = htmlDesk ? `<div class="aluno-card-grid">${htmlDesk}</div>` : '';

            restaurarCardsAbertos(mob);
            restaurarCardsAbertos(desk);
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
            ? '<option value="" selected disabled>Selecione a turma</option>' + turmasFiltradas.map(t =>
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
                m => String(m.interclasses_id_interclasse) === String(idInterclasseEq)
            ) : [];
            turmasCache = Array.isArray(turmas) ? turmas : [];

            const selMod = document.getElementById('selectModalidadeEquipe');
            selMod.innerHTML = modalidadesCache.length
                ? '<option value="" selected disabled>Selecione a modalidade</option>' + modalidadesCache.map(m => {
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
            msg.innerHTML = '<span class="text-danger">Selecione a modalidade e a turma.</span>';
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
            msg.innerHTML = `<span class="text-danger">${esc(err.message)}</span>`;
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

    ['listaEquipesMobile', 'listaEquipesDesktop'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', handleCardClick);
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
                ['btnVoltarEquipesMobile', 'btnVoltarEquipesDesk'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.href = `./dashboard.php?id=${idInterclasseEq}`;
                });
            }
        }
        await carregarCategorias();
        carregarEquipes();
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
