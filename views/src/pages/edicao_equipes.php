<?php
$tituloPagina = 'SGI - Equipes';
$cssExtra = '
:root {
  --aluno-primary: #dc3545;
  --aluno-primary-dark: #b02a37;
  --aluno-primary-light: #fce4e6;
  --aluno-primary-subtle: #fff0f0;
  --aluno-success: #198754;
  --aluno-bg: #f5f6fa;
  --aluno-surface: #ffffff;
  --aluno-border: #e9ecef;
  --aluno-text: #1a1a2e;
  --aluno-text-secondary: #6c757d;
  --aluno-text-muted: #adb5bd;
  --aluno-radius-sm: 8px;
  --aluno-radius-md: 12px;
  --aluno-radius-lg: 16px;
  --aluno-radius-xl: 24px;
  --aluno-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --aluno-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --aluno-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
  --aluno-shadow-hover: 0 12px 28px rgba(0,0,0,0.15);
  --aluno-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.aluno-page { font-family: "Segoe UI", system-ui, -apple-system, sans-serif; color: var(--aluno-text); }
.aluno-page-header {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
  margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--aluno-border);
}
.aluno-page-header .header-left { display: flex; align-items: center; gap: 1rem; }
.aluno-page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--aluno-text); }
.aluno-page-header .back-link {
  width: 40px; height: 40px; border-radius: var(--aluno-radius-md);
  display: flex; align-items: center; justify-content: center;
  background: var(--aluno-surface); border: 1px solid var(--aluno-border);
  color: var(--aluno-text); text-decoration: none;
  transition: all var(--aluno-transition); flex-shrink: 0;
}
.aluno-page-header .back-link:hover { background: var(--aluno-primary); color: #fff; border-color: var(--aluno-primary); }
.aluno-card {
  background: var(--aluno-surface); border-radius: var(--aluno-radius-lg);
  border: 1px solid var(--aluno-border); overflow: hidden;
  box-shadow: var(--aluno-shadow-sm); margin-bottom: 1rem;
}
.aluno-card .card-header-custom {
  padding: 1rem 1.25rem; font-weight: 500; font-size: 0.95rem;
  border-bottom: 1px solid var(--aluno-border); background: #fafafa;
  display: flex; align-items: center; justify-content: space-between;
}
.aluno-card .card-body-custom { padding: 1rem 1.25rem; }
.aluno-table { width: 100%; border-collapse: collapse; }
.aluno-table td { padding: 0.75rem 0; border-bottom: 1px solid var(--aluno-border); vertical-align: middle; }
.aluno-table tr:last-child td { border-bottom: none; }
.aluno-table tbody tr { transition: background var(--aluno-transition); }
.aluno-table tbody tr:hover td { background: #f8f9fa; }
.btn-aluno { background: var(--aluno-primary); color: #fff; border: none; border-radius: var(--aluno-radius-md); padding: 0.5rem 1.25rem; font-weight: 500; transition: all var(--aluno-transition); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-aluno:hover { background: var(--aluno-primary-dark); color: #fff; }
.btn-aluno { background: transparent; color: var(--aluno-primary); border: 1.5px solid var(--aluno-primary); border-radius: var(--aluno-radius-md); padding: 0.4rem 1rem; font-weight: 500; transition: all var(--aluno-transition); text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; }
.btn-aluno:hover { background: var(--aluno-primary); color: #fff; }
.btn-filter-cat {
  padding: 0.55rem 1.3rem; border-radius: 50px;
  border: 1.5px solid var(--aluno-border); background: var(--aluno-surface);
  color: var(--aluno-text-secondary); font-size: 0.9rem; font-weight: 600;
  cursor: pointer; transition: all var(--aluno-transition);
}
.btn-filter-cat:hover { border-color: var(--aluno-primary); color: var(--aluno-primary); background: var(--aluno-primary-subtle); }
.btn-filter-cat.active { background: var(--aluno-primary); border-color: var(--aluno-primary); color: #fff; }
.aluno-empty { text-align: center; padding: 3rem 1rem; color: var(--aluno-text-secondary); }
.aluno-empty .empty-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--aluno-text-muted); }
.aluno-empty h5 { font-weight: 600; margin-bottom: 0.5rem; }
.aluno-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1.25rem;
  width: 100%;
}
.aluno-empty p { font-size: 0.9rem; max-width: 400px; margin: 0 auto; }
';

include 'componentes/head.php';
include 'componentes/header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3" style="padding-bottom: 5rem;">
    <a href="./dashboard.php" id="btnVoltarEquipesMobile" class="btn btn-aluno btn-sm mb-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Voltar
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
                <a href="./dashboard.php" id="btnVoltarEquipesDesk" class="back-link"><i class="bi bi-arrow-left"></i></a>
                <h1 id="nomeInterclasseEquipes">Equipes</h1>
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
        <div class="modal-content" style="border-radius:var(--aluno-radius-lg);border:none;box-shadow:var(--aluno-shadow-lg);">
            <div class="modal-header border-0" style="padding:1.25rem 1.5rem 0;">
                <h5 class="modal-title" style="color:var(--aluno-primary);font-weight:600;"><i class="bi bi-plus-circle me-2"></i>Criar nova equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1rem 1.5rem 1.5rem;">
                <form id="formCriarEquipe">
                    <label for="selectModalidadeEquipe" class="form-label small text-muted fw-semibold">Modalidade</label>
                    <select id="selectModalidadeEquipe" class="form-select mb-3" style="border-radius:var(--aluno-radius-md);border-color:var(--aluno-border);" required>
                        <option value="" selected disabled>Carregando modalidades...</option>
                    </select>
                    <label for="selectTurmaEquipe" class="form-label small text-muted fw-semibold">Turma</label>
                    <select id="selectTurmaEquipe" class="form-select mb-3" style="border-radius:var(--aluno-radius-md);border-color:var(--aluno-border);" required>
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

            const btns = lista.map((c, i) =>
                `<button class="btn-filter-cat${i === 0 ? ' active' : ''}" data-id="${c.id_categoria}">${esc(c.nome_categoria)}</button>`
            ).join('');

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
            mob.innerHTML = '<p class="text-muted text-center">Nenhuma edição selecionada.</p>';
            desk.innerHTML = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-folder-x"></i></div><h5>Nenhuma edição</h5><p>Selecione um interclasse para ver as equipes.</p></div>';
            return;
        }

        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasseEq);
        if (dados?.nome_interclasse) {
            document.getElementById('nomeInterclasseEquipes').textContent = dados.nome_interclasse;
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

            let urlMod = `${API}modalidades.php?id_interclasse=${encodeURIComponent(idInterclasseEq)}`;
            if (idCategoriaFiltro) {
                urlMod += `&id_categoria=${encodeURIComponent(idCategoriaFiltro)}`;
            }
            const resMod = await fetch(urlMod);
            const modsRaw = await resMod.json();
            let mods = Array.isArray(modsRaw) ? modsRaw : [];

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

            for (const [nomeCat, listaMod] of Object.entries(porCategoria)) {
                for (const m of listaMod) {
                    const rEq = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(m.id_modalidade)}&_t=${Date.now()}`);
                    const equipes = await rEq.json();
                    const arr = Array.isArray(equipes) ? equipes : [];

                    htmlMob += `<div class="aluno-card">
                        <div class="card-header-custom">${esc(m.nome_modalidade)}</div>
                        <div class="card-body-custom">`;

                    if (!arr.length) {
                        htmlMob += '<p class="text-muted small mb-0">Nenhuma equipe.</p>';
                    } else {
                        htmlMob += '<div class="d-flex flex-column gap-1">';
                        arr.forEach(eq => {
                            const qElenco = new URLSearchParams({
                                id: idInterclasseEq,
                                id_equipe: String(eq.id_equipe),
                                id_turma: String(eq.turmas_id_turma),
                                id_modalidade: String(m.id_modalidade),
                                id_categoria: String(m.categorias_id_categoria),
                                nome_turma: eq.nome_turma || '',
                                nome_modalidade: m.nome_modalidade || ''
                            });
                            const hrefElenco = `./elenco_equipe.php?${qElenco.toString()}`;
                            htmlMob += `
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span>${esc(eq.nome_turma || 'Turma')}</span>
                                    <div class="d-flex gap-1">
                                        <a class="btn btn-aluno btn-sm" href="${hrefElenco}"><i class="bi bi-people-fill"></i></a>
                                        ${isAdmin ? `<button class="btn btn-aluno btn-sm" onclick="excluirEquipe(${eq.id_equipe}, '${esc(eq.nome_turma || 'Turma')}')"><i class="bi bi-trash"></i></button>` : ''}
                                    </div>
                                </div>`;
                        });
                        htmlMob += '</div>';
                    }

                    htmlMob += `</div></div>`;

                    htmlDesk += `<div class="aluno-card">
                        <div class="card-header-custom">${esc(m.nome_modalidade)}</div>
                        <div class="card-body-custom" style="padding:0;">`;

                    if (!arr.length) {
                        htmlDesk += '<p class="text-muted small mb-0 px-3 py-3">Nenhuma equipe cadastrada nesta modalidade.</p>';
                    } else {
                        htmlDesk += '<table class="aluno-table"><tbody>';
                        arr.forEach(eq => {
                            const qElenco = new URLSearchParams({
                                id: idInterclasseEq,
                                id_equipe: String(eq.id_equipe),
                                id_turma: String(eq.turmas_id_turma),
                                id_modalidade: String(m.id_modalidade),
                                id_categoria: String(m.categorias_id_categoria),
                                nome_turma: eq.nome_turma || '',
                                nome_modalidade: m.nome_modalidade || ''
                            });
                            const hrefElenco = `./elenco_equipe.php?${qElenco.toString()}`;
                            htmlDesk += `<tr>
                                <td style="padding-left:1.25rem">${esc(eq.nome_turma || 'Turma')}</td>
                                <td class="text-end" style="padding-right:1.25rem">
                                    <a class="btn btn-aluno btn-sm me-1" href="${hrefElenco}"><i class="bi bi-people-fill"></i></a>
                                    ${isAdmin ? `<button class="btn btn-aluno btn-sm" onclick="excluirEquipe(${eq.id_equipe}, '${esc(eq.nome_turma || 'Turma')}')"><i class="bi bi-trash"></i></button>` : ''}
                                </td>
                            </tr>`;
                        });
                        htmlDesk += '</tbody></table>';
                    }

                    htmlDesk += `</div></div>`;
                }
            }

            mob.innerHTML = htmlMob;
            desk.innerHTML = htmlDesk ? `<div class="aluno-card-grid">${htmlDesk}</div>` : '';
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
?>
