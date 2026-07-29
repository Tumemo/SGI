<?php
$tituloPagina = 'SGI - Ranking Geral';
$titulo = 'Ranking de Turmas';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
.btn-categoria { transition: all 0.2s; border-radius: 50px !important; min-width: 100px; border: 1.5px solid #E5E7EB; background: #fff; color: #4B5563; font-weight: 600; font-size: .9rem; padding: .55rem 1.3rem; white-space: nowrap; }
.rk-stat-chip { font-size: .9rem; padding: .55rem 1.1rem; }
.btn-categoria:hover { border-color: #dc3545 !important; color: #dc3545 !important; background: #fff5f5 !important; }
.btn-categoria.ativo { background: #dc3545 !important; color: #fff !important; border-color: #dc3545 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'ranking';
?>

<!-- ======================== MOBILE ======================== -->
<main class="d-md-none py-3 px-3" style="margin-bottom: 100px;">
    <div id="msgMob"></div>

    <header class="rk-mobile-header">
        <div>
            <h1 class="rk-mobile-header__title d-none" id="nomeInterclasse"></h1>
        </div>
        <div class="rk-stat-chip">
            <span>&#x1F465;</span>
            <span id="totalTurmas">0 Turmas</span>
        </div>
    </header>

    <div id="filtrosMob" class="rk-filters-mobile"></div>
    <div id="listaMob" class="rk-ranking-list"></div>
</main>

<!-- ======================== DESKTOP ======================== -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-4 py-4">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-3 flex-wrap">
            <div id="filtrosDesk" class="d-flex overflow-auto gap-2"></div>
            <div class="rk-stat-chip flex-shrink-0">
                <span>&#x1F465;</span>
                <span id="totalTurmasDesk">0 Turmas</span>
            </div>
        </div>

        <div id="msgDesk"></div>
        <div id="listaDesk" class="d-flex flex-column gap-3"></div>
    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    let dadosAPI = [];
    let categoriasUnicas = [];

    async function init() {
        if (!idInterclasse) {
            try {
                const ativo = await window.SGIInterclasse.getActiveInterclasse();
                if (ativo) {
                    window.location.href = `./ranking.php?id=${ativo.id_interclasse}`;
                    return;
                }
            } catch (_) {}
            exibirMensagem("Nenhum interclasse ativo encontrado.", "danger");
            return;
        }
        await carregarDados();
    }

    async function carregarDados() {
        const loading = '<div class="rk-loading"><div class="spinner-border text-danger"></div></div>';
        document.getElementById('listaMob').innerHTML = loading;
        document.getElementById('listaDesk').innerHTML = loading;

        try {
            const response = await fetch(`../../../api/ranking.php?id_interclasse=${idInterclasse}`);
            const data = await response.json();

            if (!data || data.length === 0) {
                exibirMensagem("Nenhum dado encontrado para este interclasse.", "warning");
                return;
            }

            dadosAPI = data;

            const catRes = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const catData = await catRes.json();
            categoriasUnicas = Array.isArray(catData) ? catData.map(c => c.nome_categoria) : [];

            document.querySelectorAll('#nomeInterclasse').forEach(el => el.innerText = data[0].nome_interclasse);
            document.getElementById('totalTurmas').innerText = `${data.length} Turmas`;
            const ttd = document.getElementById('totalTurmasDesk');
            if (ttd) ttd.innerText = `${data.length} Turmas`;

            renderizarFiltros();
            filtrarCategoria(categoriasUnicas[0]);

        } catch (error) {
            console.error("Erro:", error);
            exibirMensagem("Erro ao conectar com o servidor.", "danger");
        }
    }

    function renderizarFiltros() {
        const fMob = document.getElementById('filtrosMob');
        const fDesk = document.getElementById('filtrosDesk');
        fMob.innerHTML = '';
        fDesk.innerHTML = '';

        categoriasUnicas.forEach(cat => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-secondary btn-categoria';
            btn.textContent = cat;
            btn.onclick = () => filtrarCategoria(cat);

            fMob.appendChild(btn.cloneNode(true));
            const btnD = btn.cloneNode(true);
            btnD.onclick = () => filtrarCategoria(cat);
            fDesk.appendChild(btnD);
        });
    }

    function filtrarCategoria(categoria) {
        document.querySelectorAll('.btn-categoria').forEach(b => {
            b.classList.remove('ativo');
            if (b.textContent.trim() === categoria) b.classList.add('ativo');
        });

        const turmasFiltradas = dadosAPI.filter(t => t.nome_categoria === categoria);
        document.getElementById('totalTurmas').innerText = `${turmasFiltradas.length} Turmas`;
        const ttd = document.getElementById('totalTurmasDesk');
        if (ttd) ttd.innerText = `${turmasFiltradas.length} Turmas`;
        renderizarRanking(turmasFiltradas);
    }

    function renderizarRanking(turmas) {
        const cMob = document.getElementById('listaMob');
        const cDesk = document.getElementById('listaDesk');
        cMob.innerHTML = '';
        cDesk.innerHTML = '';

        if (!turmas.length) {
            const empty = '<div class="rk-empty"><i class="bi bi-inbox"></i><p>Nenhuma turma nesta categoria.</p></div>';
            cMob.innerHTML = empty;
            cDesk.innerHTML = empty;
            return;
        }

        const maxPontos = Math.max(...turmas.map(t => t.pontuacao_sem_penalidade || t.pontuacao_turma)) || 1;

        const medals = ['&#x1F947;', '&#x1F948;', '&#x1F949;'];

        turmas.forEach((t, index) => {
            const posicao = index + 1;
            const ptsSemPenalidade = t.pontuacao_sem_penalidade ?? t.pontuacao_turma;
            const ptsComPenalidade = t.pontuacao_turma;
            const perdeu = ptsSemPenalidade - ptsComPenalidade;
            const porcentagemSem = (ptsSemPenalidade / maxPontos) * 100;
            const porcentagemCom = (ptsComPenalidade / maxPontos) * 100;
            const classeDestaque = posicao <= 3 ? `posicao-${posicao}` : '';
            const isTop3 = posicao <= 3;

            const html = `
                <div class="rk-card-wrapper ${isTop3 ? 'rk-card-wrapper--top' : ''}" style="animation-delay: ${index * 0.07}s">
                    <div class="card card-turma rk-rank-card ${classeDestaque} ${isTop3 ? 'rk-rank-card--podium' : ''}">
                        ${isTop3 ? `<div class="rk-rank-card__medal">${medals[posicao - 1]}</div>` : ''}

                        <div class="rk-rank-card__head">
                            <div class="rk-rank-card__pos ${isTop3 ? 'rk-rank-card__pos--podium' : ''}">${posicao}°</div>
                            <div class="rk-rank-card__info">
                                <div class="rk-rank-card__name">${t.nome_turma}</div>
                                <div class="rk-rank-card__detail"><i class="bi bi-mortarboard-fill"></i> ${t.nome_fantasia_turma || t.turno_turma}</div>
                            </div>
                            <div class="rk-rank-card__badge badge-pontos ${isTop3 ? 'rk-rank-card__badge--podium' : ''}">
                                <span class="rk-rank-card__pts">${ptsComPenalidade}</span>
                                <span class="rk-rank-card__pts-label">pts</span>
                            </div>
                        </div>

                        <div class="rk-rank-card__bars">
                            <div class="rk-bar-group">
                                <div class="rk-bar-group__header">
                                    <span><i class="bi bi-star"></i> Pontuação esperada</span>
                                    <span class="rk-bar-group__val">${ptsSemPenalidade} pts</span>
                                </div>
                                <div class="barra-fundo" style="height: 8px;">
                                    <div class="barra-progresso rk-bar--expected" style="width: ${porcentagemSem}%;"></div>
                                </div>
                            </div>
                            <div class="rk-bar-group">
                                <div class="rk-bar-group__header">
                                    <span class="text-danger fw-semibold"><i class="bi bi-flag-fill"></i> Pontuação final</span>
                                    <span class="rk-bar-group__val fw-bold">${ptsComPenalidade} pts${perdeu > 0 ? ` <span class="text-danger">(-${perdeu})</span>` : ''}</span>
                                </div>
                                <div class="barra-fundo" style="height: 12px;">
                                    <div class="barra-progresso rk-bar--final" style="width: ${porcentagemCom}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            cMob.innerHTML += html;
            cDesk.innerHTML += html;
        });
    }

    function exibirMensagem(texto, tipo) {
        const alerta = `<div class="alert alert-${tipo} text-center">${texto}</div>`;
        document.getElementById('msgMob').innerHTML = alerta;
        document.getElementById('msgDesk').innerHTML = alerta;
    }

    window.addEventListener('load', init);
</script>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>
