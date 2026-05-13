<?php
$titulo = "Equipes";
$textTop = "Equipes";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none p-3" style="padding-top: 5rem; padding-bottom: 5rem;">
    <p class="text-secondary text-center small mb-3">Equipes por modalidade e categoria desta edição.</p>
    <div id="listaEquipesMobile" class="d-flex flex-column gap-3"></div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarEquipesDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 text-decoration-none shadow-sm" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i>
                <span id="nomeInterclasseEquipes" style="font-weight: 400;">Interclasse</span>
            </a>
            <h4 class="text-dark d-flex align-items-center gap-2 mb-0" style="font-weight: 400;">
                <i class="bi bi-people fs-4"></i> Equipes
            </h4>
            <p class="text-muted small mt-2 mb-0">Lista das equipes cadastradas nas modalidades. Toque para abrir o elenco da turma.</p>
        </div>
        <div id="listaEquipesDesktop"></div>
    </div>
</main>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasseEq = params.get('id');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
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
            document.getElementById('btnVoltarEquipesDesk').href = `./dashboard.php?id=${encodeURIComponent(idInterclasseEq)}`;
            window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
        }

        mob.innerHTML = '<p class="text-muted text-center">Carregando…</p>';
        desk.innerHTML = '<p class="text-muted">Carregando…</p>';

        try {
            const resMod = await fetch(`${API}modalidades.php`);
            const todas = await resMod.json();
            const mods = (Array.isArray(todas) ? todas : []).filter(
                (m) => String(m.interclasses_id_interclasse) === String(idInterclasseEq)
            );
            if (!mods.length) {
                const msg = '<p class="text-muted text-center w-100">Nenhuma modalidade nesta edição.</p>';
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
                htmlMob += `<h6 class="text-danger mt-3 mb-2" style="font-weight:400;">${esc(nomeCat)}</h6>`;
                htmlDesk += `<h5 class="text-danger mt-4 mb-3" style="font-weight:400;">${esc(nomeCat)}</h5>`;

                for (const m of listaMod) {
                    const rEq = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(m.id_modalidade)}`);
                    const equipes = await rEq.json();
                    const arr = Array.isArray(equipes) ? equipes : [];

                    htmlMob += `<div class="card border-0 shadow-sm rounded-3 mb-2"><div class="card-body py-2 px-3"><div class="small text-muted">${esc(m.nome_modalidade)}</div>`;
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
                                    <a class="btn btn-sm btn-danger rounded-3" href="${hrefElenco}">Elenco</a>
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
                            htmlDesk += `<tr><td><a class="text-decoration-none text-dark" href="${hrefTurma}">${esc(eq.nome_turma || 'Turma')}</a></td><td class="text-end"><a class="btn btn-sm btn-outline-danger rounded-3" href="${hrefElenco}">Elenco</a></td></tr>`;
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

    document.addEventListener('DOMContentLoaded', () => {
        carregarEquipes();
    });
</script>

<?php
require_once '../componentes/footer.php';
