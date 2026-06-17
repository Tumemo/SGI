<?php
$titulo = "Árvore de Chaveamento";
$textTop = "Chaveamento";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .bracket-container {
        overflow-x: auto;
        padding-bottom: 20px;
    }

    .bracket-wrapper {
        display: flex;
        gap: 40px;
        min-width: fit-content;
        padding: 20px 10px;
    }

    .bracket-round {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 240px;
        max-width: 260px;
        position: relative;
    }

    .bracket-round-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #e30613;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: center;
        padding-bottom: 10px;
        border-bottom: 2px solid #e30613;
        margin-bottom: 10px;
    }

    .bracket-game {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        transition: box-shadow 0.2s;
        position: relative;
    }

    .bracket-game:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }

    .bracket-game.bye {
        opacity: 0.7;
        border-style: dashed;
    }

    .bracket-game.concluido {
        border-color: #198754;
    }

    .matchup-team {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        gap: 8px;
        transition: background 0.15s;
    }

    .matchup-team + .matchup-team {
        border-top: 1px solid #f0f0f0;
    }

    .matchup-team.winner {
        background: #f0fdf4;
        font-weight: 600;
    }

    .matchup-team.winner .team-name::after {
        content: " ✓";
        color: #16a34a;
        font-weight: 700;
    }

    .team-name {
        font-size: 0.85rem;
        color: #1f2937;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .team-score {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        min-width: 28px;
        text-align: center;
        background: #f3f4f6;
        border-radius: 6px;
        padding: 0 6px;
    }

    .matchup-team.winner .team-score {
        background: #dcfce7;
        color: #16a34a;
    }

    .game-status-badge {
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 600;
        text-align: center;
        display: inline-block;
        margin: 4px 14px 8px;
    }

    .game-status-badge.agendado {
        background: #fef3c7;
        color: #92400e;
    }

    .game-status-badge.concluido {
        background: #dcfce7;
        color: #166534;
    }

    .game-status-badge.andamento {
        background: #dbeafe;
        color: #1e40af;
    }

    .game-empty {
        background: transparent;
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 0.8rem;
    }

    .bracket-connector {
        display: none;
    }

    .select-wrapper {
        background: white;
        border-radius: 14px;
        padding: 20px 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
    }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .no-data i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 16px;
    }

    .filtro-box {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }

    .btn-gerar {
        background: #E30613;
        border: none;
        padding: 12px 24px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
    }

    .btn-gerar:hover {
        background: #bb0812;
    }

    .table-custom thead {
        background: #f8fafc;
    }

    .table-custom th {
        color: #6b7280;
        font-size: .9rem;
        font-weight: 600;
    }

    .status {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
    }

    .status-success {
        background: #dcfce7;
        color: #166534;
    }

    .search-input {
        border-radius: 12px;
        padding: 10px 14px;
    }

    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }
</style>

<main class="main-desktop-layout py-4">

    <div class="container-fluid" style="max-width: 96%;">

        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltar"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclasse">Interclasse</span>
            </a>

            <h2 class="fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3-fill" style="color:#e30613;"></i>
                Árvore de Chaveamento
            </h2>

            <p class="text-muted mb-0">
                Visualize o chaveamento mata-mata das modalidades.
            </p>
        </div>

        <div class="filtro-box mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Modalidade</label>
                    <select class="form-select" id="selectModalidade">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>
                <div class="col-lg-3 d-grid">
                    <button class="btn-gerar" id="btnGerarChaveamento">
                        <i class="bi bi-diagram-3-fill me-2"></i> Gerar Chaveamento
                    </button>
                </div>
                <div class="col-lg-6">
                    <div id="msgChaveamento" class="mt-2"></div>
                    <div id="linkVerArvore" class="mt-2 d-none">
                        <a href="#" id="btnVerArvore" class="btn btn-outline-danger btn-sm fw-bold">
                            <i class="bi bi-diagram-3-fill me-1"></i> Ver árvore do chaveamento
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="secaoJogos">
            <div class="card card-custom mb-4">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold">Jogos Realizados</h4>
                            <span class="text-muted">Histórico de partidas concluídas.</span>
                        </div>
                        <input type="text" class="form-control search-input" placeholder="Buscar partida..." style="width:300px" id="inputBuscaJogo">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Partida</th>
                                    <th>Modalidade</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyJogos">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Carregando jogos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div id="bracketArea">
            <div class="no-data">
                <i class="bi bi-diagram-3"></i>
                <p class="fs-5 fw-semibold text-muted mb-1">Nenhum chaveamento carregado</p>
                <p class="text-muted small">Selecione uma modalidade acima para visualizar o chaveamento.</p>
            </div>
        </div>

    </div>

</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    let modalidadesCache = [];

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclasse').innerText = dados?.nome_interclasse || 'Interclasse';

        const btnVoltar = document.getElementById('btnVoltar');
        if (btnVoltar) {
            btnVoltar.href = `./dashboard.php?id=${idInterclasse}`;
        }
        return idInterclasse;
    }

    async function carregarModalidades() {
        try {
            const resp = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            modalidadesCache = Array.isArray(data) ? data : [];
            const select = document.getElementById('selectModalidade');
            select.innerHTML = '<option value="">Todas as modalidades</option>';
            modalidadesCache.forEach(mod => {
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                select.innerHTML += `<option value="${mod.id_modalidade}">${mod.nome_modalidade}${genero}${categoria}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar modalidades:", e);
        }
    }

    function formatarNomePartida(jogo) {
        const tag = jogo.nome_jogo || '';
        const mm = tag.match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 8: 'Oitavas de final', 4: 'Quartas de final', 2: 'Semifinal', 1: 'Final' };
            const fase = fases[largura] || 'Fase ' + largura;
            const confronto = slot + 1;
            const equipes = (jogo.equipes_nomes || '').trim();
            const sufixo = kind === 'B' ? ' (bye)' : '';
            if (equipes) {
                return `${fase} — confronto ${confronto}: ${equipes}${sufixo}`;
            }
            return `${fase} — confronto ${confronto}${sufixo}`;
        }
        return tag || '---';
    }

    async function carregarJogos() {
        const tbody = document.getElementById('tbodyJogos');
        try {
            const idModalidade = document.getElementById('selectModalidade').value;
            let url = `../../../api/jogos.php?id_interclasse=${idInterclasse}`;
            if (idModalidade) url += `&id_modalidade=${idModalidade}`;

            const resp = await fetch(url);
            const data = await resp.json();
            const jogos = Array.isArray(data) ? data : [];

            if (!jogos.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Nenhum jogo encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = jogos.map(j => {
                let dataJogo = '---';
                if (j.data_jogo) {
                    try {
                        dataJogo = new Date(j.data_jogo + (j.inicio_jogo ? 'T' + j.inicio_jogo : '')).toLocaleString('pt-BR');
                    } catch (_) { dataJogo = j.data_jogo; }
                }
                const statusClass = j.status_jogo === 'Concluido' ? 'status-success' : '';
                return `<tr>
                    <td>${formatarNomePartida(j)}</td>
                    <td>${j.nome_modalidade || '---'}</td>
                    <td>${dataJogo}</td>
                    <td><span class="status ${statusClass}">${j.status_jogo || '---'}</span></td>
                    <td class="text-end">
                        <a href="./jogos.php?id_jogo=${j.id_jogo}" class="btn btn-link text-success p-0 me-2" title="Acessar Jogo">
                            <i class="bi bi-play-circle"></i>
                        </a>
                        <button class="btn btn-link btn-edit p-0 ms-2" title="Editar Jogo"
                            onclick="editarJogo(${j.id_jogo},'${j.data_jogo || ''}','${j.inicio_jogo || ''}','${j.termino_jogo || ''}',${j.locais_id_local || 'null'})">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            console.error("Erro ao carregar jogos:", e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Erro ao carregar jogos.</td></tr>';
        }
    }

    const fasesLabel = {
        1: 'Final',
        2: 'Semifinal',
        4: 'Quartas de final',
        8: 'Oitavas de final',
        16: 'Primeira fase'
    };

    function formatFase(faseNivel) {
        return fasesLabel[faseNivel] || `Fase ${faseNivel}`;
    }

    async function carregarArvore(idModalidade) {
        const area = document.getElementById('bracketArea');
        const linkArvore = document.getElementById('linkVerArvore');

        linkArvore.classList.add('d-none');

        if (!idModalidade) {
            area.innerHTML = `
                <div class="no-data">
                    <i class="bi bi-diagram-3"></i>
                    <p class="fs-5 fw-semibold text-muted mb-1">Nenhum chaveamento carregado</p>
                    <p class="text-muted small">Selecione uma modalidade acima para visualizar o chaveamento.</p>
                </div>`;
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const isIndividual = mod && Number(mod.id_tipo_modalidade) === 2;

        if (isIndividual) {
            area.innerHTML = `
                <div class="no-data">
                    <i class="bi bi-info-circle"></i>
                    <p class="fs-5 fw-semibold text-muted mb-1">Modalidade Individual</p>
                    <p class="text-muted small">Modalidades individuais não possuem chaveamento. Utilize a seção de Pontuação Individual para registrar 1º, 2º e 3º lugar.</p>
                </div>`;
            return;
        }

        area.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-danger" role="status"></div><p class="text-muted mt-2">Carregando chaveamento...</p></div>`;

        try {
            const resp = await fetch(`../../../api/chaveamento.php?id_modalidade=${idModalidade}`);
            const data = await resp.json();

            if (!data.success) {
                area.innerHTML = `<div class="no-data"><i class="bi bi-exclamation-triangle text-warning"></i><p class="text-muted">${data.message || 'Erro ao carregar chaveamento.'}</p></div>`;
                return;
            }

            const jogos = data.jogos || [];

            if (jogos.length === 0) {
                area.innerHTML = `
                    <div class="no-data">
                        <i class="bi bi-diagram-3"></i>
                        <p class="fs-5 fw-semibold text-muted mb-1">Nenhum chaveamento gerado</p>
                        <p class="text-muted small">Clique em "Gerar Chaveamento" para criar o chaveamento desta modalidade.</p>
                    </div>`;
                return;
            }

            const rounds = {};
            jogos.forEach(jogo => {
                const nivel = jogo.fase_nivel || 0;
                if (!rounds[nivel]) rounds[nivel] = [];
                rounds[nivel].push(jogo);
            });

            const niveis = Object.keys(rounds).map(Number).sort((a, b) => a - b);

            let html = `<div class="bracket-container"><div class="bracket-wrapper">`;

            niveis.forEach(nivel => {
                const jogosRound = rounds[nivel].sort((a, b) => (a.posicao_na_chave || 0) - (b.posicao_na_chave || 0));
                html += `<div class="bracket-round">`;
                html += `<div class="bracket-round-title">${formatFase(nivel)}</div>`;

                jogosRound.forEach(jogo => {
                    const eqs = jogo.equipes || [];
                    const isBye = jogo.eh_bye;
                    const isConcluido = jogo.status_jogo === 'Concluido' || jogo.status_jogo === 'Finalizado';
                    const vencId = jogo.equipe_vencedora_id;

                    const classes = ['bracket-game'];
                    if (isBye) classes.push('bye');
                    if (isConcluido) classes.push('concluido');

                    html += `<div class="${classes.join(' ')}">`;

                    if (eqs.length === 0) {
                        html += `<div class="matchup-team"><span class="team-name text-muted">A definir</span><span class="team-score">--</span></div>`;
                    } else {
                        eqs.forEach(eq => {
                            const ehVencedor = vencId && Number(eq.id_equipe) === Number(vencId);
                            const cls = ehVencedor ? 'matchup-team winner' : 'matchup-team';
                            const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                            html += `<div class="${cls}">`;
                            html += `<span class="team-name">${nome}</span>`;
                            html += `<span class="team-score">${eq.gols ?? 0}</span>`;
                            html += `</div>`;
                        });
                    }

                    if (isBye) {
                        html += `<div class="game-status-badge concluido">Bye</div>`;
                    } else {
                        const statusClass = (jogo.status_jogo || '').toLowerCase();
                        html += `<div class="game-status-badge ${statusClass}">${jogo.status_jogo || '---'}</div>`;
                    }

                    html += `</div>`;
                });

                html += `</div>`;
            });

            html += `</div></div>`;

            area.innerHTML = html;

        } catch (e) {
            console.error("Erro ao carregar árvore:", e);
            area.innerHTML = `<div class="no-data"><i class="bi bi-exclamation-triangle text-warning"></i><p class="text-muted">Erro ao conectar com o servidor.</p></div>`;
        }
    }

    document.getElementById('selectModalidade').addEventListener('change', function () {
        document.getElementById('msgChaveamento').innerHTML = '';
        carregarArvore(this.value);
        carregarJogos();
    });

    document.getElementById('btnGerarChaveamento').addEventListener('click', async function () {
        const idModalidade = document.getElementById('selectModalidade').value;
        const msgEl = document.getElementById('msgChaveamento');
        const btn = this;

        if (!idModalidade) {
            msgEl.innerHTML = '<p class="text-danger fw-bold mb-0">Selecione uma modalidade primeiro.</p>';
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const tipoId = mod ? Number(mod.id_tipo_modalidade) : 0;

        if (tipoId === 2) {
            msgEl.innerHTML = '<p class="text-info fw-bold mb-0">Use a seção "Pontuação Individual" abaixo para registrar 1º, 2º e 3º lugar.</p>';
            return;
        }

        msgEl.innerHTML = '<p class="text-muted fw-bold mb-0">Gerando chaveamento...</p>';

        try {
            btn.disabled = true;
            const resp = await fetch('../../../api/chaveamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idModalidade), tipo_modalidade: 'mata_mata' })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao gerar chaveamento.');

            msgEl.innerHTML = `<p class="text-success fw-bold mb-0">${data.message} (${data.jogos_criados} jogos criados).</p>`;
            const linkArvore = document.getElementById('linkVerArvore');
            linkArvore.classList.remove('d-none');
            document.getElementById('btnVerArvore').href = `./chaveamento_arvore.php?id=${idInterclasse}`;
            carregarArvore(idModalidade);
            carregarJogos();
        } catch (err) {
            msgEl.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
        }
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await carregarModalidades();
        await carregarJogos();
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
