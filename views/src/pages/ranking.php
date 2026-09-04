<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resgata o nível buscando principalmente por 'nivel_usuario' da tabela
$nivelRaw = $_SESSION['nivel_usuario'] ?? $_SESSION['nivel'] ?? $_SESSION['usuario_nivel'] ?? $_SESSION['nivel_acesso'] ?? $_SESSION['perfil'] ?? 99;

if (is_numeric($nivelRaw)) {
    $nivelNum = (int)$nivelRaw;
    $eAdmin = ($nivelNum === 0 || $nivelNum === 1);
} else {
    $eAdmin = (strtolower((string)$nivelRaw) === 'admin');
}

$tituloPagina = 'SGI - Ranking Geral';
$titulo = 'Ranking de Turmas';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'ranking';
?>

<!-- ======================== MOBILE ======================== -->
<main class="d-md-none py-3 px-3 sgi-inline-9f1598af" >
    <div id="msgMob"></div>

    <header class="rk-mobile-header mb-2">
        <div>
            <h1 class="rk-mobile-header__title d-none" id="nomeInterclasse"></h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($eAdmin): ?>
                <button type="button" class="btn btn-sm btn-outline-dark btn-imprimir" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            <?php endif; ?>

            <div class="rk-stat-chip">
                <span>&#x1F465;</span>
                <span id="totalTurmas">0 Turmas</span>
            </div>
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
            
            <div class="d-flex align-items-center gap-3">
                <?php if ($eAdmin): ?>
                    <button type="button" class="btn btn-outline-dark fw-bold btn-imprimir" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimir Ranking
                    </button>
                <?php endif; ?>

                <div class="rk-stat-chip flex-shrink-0">
                    <span>&#x1F465;</span>
                    <span id="totalTurmasDesk">0 Turmas</span>
                </div>
            </div>
        </div>

        <div id="msgDesk"></div>
        <div id="listaDesk" class="d-flex flex-column gap-3"></div>
    </div>
</main>

<!-- Modal: histórico de pontuações da turma -->
<div class="modal fade" id="modalHistoricoTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow sgi-inline-0e984564" >
            <div class="modal-header border-0 pb-0 px-4 pt-3">
                <h5 class="modal-title fw-bold" id="htrTitulo">Histórico de Pontos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body htr-modal-body" id="htrCorpo">
                <div class="text-center py-5"><div class="spinner-border text-danger"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
    const IS_ADMIN = <?= $eAdmin ? 'true' : 'false' ?>;
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    let dadosAPI = [];
    let categoriasUnicas = [];

    async function init() {
        if (!IS_ADMIN) {
            bloquearAcessoRanking();
            return;
        }

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

    function bloquearAcessoRanking() {
        const mensagemOculta = `
            <div class="text-center py-5">
                <i class="bi bi-lock-fill text-warning display-1"></i>
                <h3 class="fw-bold mt-3">Ranking Oculto</h3>
                <p class="text-muted fs-6">O ranking deste interclasse está restrito apenas para os administradores!</p>
            </div>
        `;
        const mob = document.getElementById('listaMob');
        const desk = document.getElementById('listaDesk');
        if (mob) mob.innerHTML = mensagemOculta;
        if (desk) desk.innerHTML = mensagemOculta;
    }

    async function carregarDados() {
        if (!IS_ADMIN) {
            bloquearAcessoRanking();
            return;
        }

        const loading = '<div class="rk-loading"><div class="spinner-border text-danger"></div></div>';
        document.getElementById('listaMob').innerHTML = loading;
        document.getElementById('listaDesk').innerHTML = loading;

        try {
            const response = await fetch(`../../../api/ranking.php?id_interclasse=${idInterclasse}`);
            const data = await response.json();

            // Trata o bloqueio retornado de forma limpa pela API
            if (data && data.bloqueado) {
                bloquearAcessoRanking();
                return;
            }

            if (!data || !Array.isArray(data) || data.length === 0) {
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
                <div class="rk-card-wrapper ${isTop3 ? 'rk-card-wrapper--top' : ''} sgi-inline-666499cd"  data-sgi-index="${index}">
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
                                <div class="barra-fundo sgi-inline-65fd1499" >
                                    <div class="barra-progresso rk-bar--expected sgi-inline-95b73db3"  data-sgi-width="${porcentagemSem}"></div>
                                </div>
                            </div>
                            <div class="rk-bar-group">
                                <div class="rk-bar-group__header">
                                    <span class="text-danger fw-semibold"><i class="bi bi-flag-fill"></i> Pontuação final</span>
                                    <span class="rk-bar-group__val fw-bold">${ptsComPenalidade} pts${perdeu > 0 ? ` <span class="text-danger">(-${perdeu})</span>` : ''}</span>
                                </div>
                                <div class="barra-fundo sgi-inline-9d3cb190" >
                                    <div class="barra-progresso rk-bar--final sgi-inline-f2316fc1"  data-sgi-width="${porcentagemCom}"></div>
                                </div>
                            </div>
                        </div>

                        <div class="rk-hist-footer">
                            <button type="button" class="rk-hist-btn w-100" onclick="abrirHistorico(${t.id_turma}, '${jsEsc(t.nome_turma)}')">
                                <i class="bi bi-clock-history"></i> Ver histórico de pontos
                            </button>
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

    /* ==================== HISTÓRICO DE PONTOS (MODAL) ==================== */

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function jsEsc(s) { return String(s == null ? '' : s).replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

    function fmtData(s) {
        if (!s) return '—';
        const d = new Date(String(s).includes('T') ? s : s.replace(' ', 'T'));
        if (isNaN(d)) return s;
        return d.toLocaleDateString('pt-BR');
    }
    function fmtDataHora(s) {
        if (!s) return '—';
        const d = new Date(String(s).includes('T') ? s : s.replace(' ', 'T'));
        if (isNaN(d)) return s;
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    const medalhas = { 1: '🥇', 2: '🥈', 3: '🥉' };

    function badgeColocacao(pos) {
        if (pos === null || pos === undefined) return '';
        const medalha = medalhas[pos] || '';
        return `<span class="htr-colocacao">${medalha}${pos}º</span>`;
    }

    function secaoAbertura(icone, titulo, contagem) {
        return `
            <div class="htr-secao">
                <div class="htr-secao__head">
                    <i class="bi bi-${icone}"></i><span>${titulo}</span>
                    <span class="htr-secao__count">${contagem}</span>
                </div>`;
    }

    function renderHistorico(d) {
        const t = d.turma;
        const soma = d.resumo.arrecadacao_pontos + d.resumo.esportes_pontos - d.resumo.penalidades_pontos;
        const difere = soma !== t.pontuacao_turma;

        let html = '';

        html += `
            <div class="htr-titulo">
                <div>
                    <div class="htr-turma-nome">${esc(t.nome_turma)}</div>
                    <div class="htr-turma-sub">${esc(t.nome_fantasia_turma || '')} · ${esc(t.nome_categoria)} · ${esc(t.turno_turma || '')}</div>
                </div>
                <div class="htr-total">${t.pontuacao_turma}<small>pts</small></div>
            </div>
            <div class="htr-resumo">
                <div class="htr-chip htr-chip--verde">
                    <i class="bi bi-box-seam"></i>
                    <span class="htr-chip__valor">+${d.arrecadacao.pontos} pts</span>
                    <span class="htr-chip__rotulo">Arrecadação</span>
                </div>
                <div class="htr-chip htr-chip--roxo">
                    <i class="bi bi-trophy"></i>
                    <span class="htr-chip__valor">+${d.esportes.pontos_total} pts</span>
                    <span class="htr-chip__rotulo">Esportes</span>
                </div>
                <div class="htr-chip htr-chip--vermelho">
                    <i class="bi bi-flag"></i>
                    <span class="htr-chip__valor">-${d.penalidades.pontos_total} pts</span>
                    <span class="htr-chip__rotulo">Penalidades</span>
                </div>
            </div>
            ${difere ? `<div class="htr-aviso"><i class="bi bi-info-circle"></i> Soma das origens: ${soma} pts. O total salvo na turma é ${t.pontuacao_turma} pts (diferença de ${Math.abs(soma - t.pontuacao_turma)} pts).</div>` : ''}
        `;

        /* Arrecadação */
        html += secaoAbertura('box-seam', 'Arrecadação', d.arrecadacao.registros.length);
        if (d.arrecadacao.registros.length) {
            html += '<div class="htr-lista">';
            d.arrecadacao.registros.forEach(r => {
                html += `
                    <div class="htr-linha">
                        <div class="htr-linha__info">
                            <div class="htr-linha__titulo">${r.quantidade} kg</div>
                            <div class="htr-linha__sub">${fmtDataHora(r.data)} · por ${esc(r.registrado_por)}</div>
                        </div>
                        <span class="htr-linha__pts htr-pts--mais">+${r.pontos}</span>
                    </div>`;
            });
            html += '</div>';
            html += `<div class="htr-secao-total">Total: <b>${d.arrecadacao.itens} kg</b> × ${d.interclasse.valor_item_arrecadacao} pts = <b>+${d.arrecadacao.pontos} pts</b></div>`;
        } else {
            html += '<div class="htr-vazio">Nenhuma arrecadação registrada.</div>';
        }
        html += '</div>';

        /* Esportes */
        html += secaoAbertura('trophy', 'Esportes', d.esportes.modalidades.length);
        if (d.esportes.modalidades.length) {
            html += '<div class="htr-mods">';
            d.esportes.modalidades.forEach(m => {
                html += `
                    <div class="htr-mod">
                        <div class="htr-mod__head">
                            <div class="htr-mod__nome">
                                ${badgeColocacao(m.colocacao)}
                                <b>${esc(m.nome_modalidade)}</b>
                                <span class="htr-mod__tipo">${esc(m.tipo)} · ${esc(m.nome_categoria)}</span>
                            </div>
                            <span class="htr-mod__pts">+${m.pontos} pts</span>
                        </div>
                        ${m.alunos.length ? `<div class="htr-mod__alunos">${m.alunos.map(a => `<span class="htr-aluno">${esc(a.nome_usuario)}</span>`).join('')}</div>` : ''}
                        ${m.itens.length ? `<div class="htr-lista htr-lista--mod">${m.itens.map(it => `
                            <div class="htr-linha">
                                <div class="htr-linha__info">
                                    <div class="htr-linha__titulo">${esc(it.descricao)}</div>
                                    <div class="htr-linha__sub">${esc(it.detalhe)}</div>
                                </div>
                                <span class="htr-linha__pts htr-pts--mais">+${it.pontos}</span>
                            </div>`).join('')}</div>` : ''}
                    </div>`;
            });
            html += '</div>';
        } else {
            html += '<div class="htr-vazio">Nenhuma pontuação esportiva até o momento.</div>';
        }
        html += '</div>';

        /* Penalidades */
        html += secaoAbertura('flag', 'Penalidades', d.penalidades.ocorrencias.length);
        if (d.penalidades.ocorrencias.length) {
            html += '<div class="htr-lista">';
            d.penalidades.ocorrencias.forEach(o => {
                html += `
                    <div class="htr-linha">
                        <div class="htr-linha__info">
                            <div class="htr-linha__titulo">${esc(o.titulo)}${o.aluno ? ` <span class="htr-aluno">${esc(o.aluno)}</span>` : ''}</div>
                            <div class="htr-linha__sub">${fmtData(o.data)}${o.descricao ? ' · ' + esc(o.descricao) : ''}</div>
                        </div>
                        <span class="htr-linha__pts htr-pts--menos">-${o.pontos}</span>
                    </div>`;
            });
            html += '</div>';
            html += `<div class="htr-secao-total">Total descontado: <b>-${d.penalidades.pontos_total} pts</b></div>`;
        } else {
            html += '<div class="htr-vazio">Nenhuma penalidade applied.</div>';
        }
        html += '</div>';

        document.getElementById('htrCorpo').innerHTML = html;
    }

    async function abrirHistorico(idTurma, nomeTurma) {
        document.getElementById('htrTitulo').innerText = `Histórico de Pontos`;
        document.getElementById('htrCorpo').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-danger"></div></div>';

        const modalEl = document.getElementById('modalHistoricoTurma');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        try {
            const response = await fetch(`../../../api/historico_turma.php?id_turma=${idTurma}&id_interclasse=${idInterclasse}`);
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erro ao carregar histórico.');
            }

            document.getElementById('htrTitulo').innerText = `Histórico de Pontos`;
            renderHistorico(data);
        } catch (err) {
            document.getElementById('htrCorpo').innerHTML = `
                <div class="alert alert-danger m-3 shadow-sm border-0" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${esc(err.message)}
                </div>`;
        }
    }

    window.addEventListener('load', init);
</script>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>
