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
        padding: 6.5px 24px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
        height: 100%;
    }

    .btn-gerar:hover {
        background: #bb0812;
    }

    .modal-editar-jogo .modal-header {
        background: #E30613;
        color: white;
        border-radius: 16px 16px 0 0;
    }
    .modal-editar-jogo .btn-close {
        filter: brightness(0) invert(1);
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

    .acao-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
        border: none;
    }
    .acao-btn-play {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .acao-btn-play:hover {
        background: #2e7d32;
        color: #fff;
        transform: scale(1.05);
    }
    .acao-btn-editar {
        background: #f3f4f6;
        color: #4b5563;
    }
    .acao-btn-editar:hover {
        background: #4b5563;
        color: #fff;
        transform: scale(1.05);
    }
    .acao-btn i {
        font-size: 1rem;
        line-height: 1;
    }
    .tr-filtro-oculto {
        display: none !important;
    }

    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }

    #filtroCategoriaJogos, #filtroModalidadeJogos{
        border-radius: 10px;
        height: 40px;
        /* border: 1px solid #E30613; */
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
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm" id="filtroModalidadeJogos" style="min-width:160px;">
                                <option value="">Todas modalidades</option>
                            </select>
                            <select class="form-select form-select-sm" id="filtroCategoriaJogos" style="min-width:140px;">
                                <option value="">Todas categorias</option>
                            </select>
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Buscar partida..." style="width:200px" id="inputBuscaJogo">
                        </div>
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

<div class="modal fade modal-editar-jogo" id="modalEditarJogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarJogo" onsubmit="return salvarEdicaoJogo(event)">
                <div class="modal-body px-4">
                    <input type="hidden" id="editIdJogo">
                    <div class="bg-light rounded-3 p-3 mb-3" id="editResumoPartida">
                        <div class="small text-muted mb-1">Partida</div>
                        <div class="fw-semibold" id="editNomePartida">---</div>
                        <div class="small text-muted mt-1" id="editModalidadePartida"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data</label>
                        <input type="date" class="form-control" id="editDataJogo" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Início</label>
                            <input type="time" class="form-control" id="editInicioJogo">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Término</label>
                            <input type="time" class="form-control" id="editTerminoJogo">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Local</label>
                        <select class="form-select" id="editLocalJogo">
                            <option value="">Selecione um local</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="editStatusJogo">
                            <option value="Agendado">Agendado</option>
                            <option value="Andamento">Andamento</option>
                            <option value="Concluido">Concluído</option>
                        </select>
                    </div>
                    <div id="msgEditarJogo" class="small"></div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4" id="btnSalvarJogo">
                        <i class="bi bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
            const selectJogos = document.getElementById('filtroModalidadeJogos');
            selectJogos.innerHTML = '<option value="">Todas modalidades</option>';
            modalidadesCache.forEach(mod => {
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                const label = `${mod.nome_modalidade}${genero}${categoria}`;
                select.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                selectJogos.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar modalidades:", e);
        }
    }

    async function carregarCategorias() {
        const select = document.getElementById('filtroCategoriaJogos');
        try {
            const resp = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            const categorias = Array.isArray(data) ? data : [];
            select.innerHTML = '<option value="">Todas categorias</option>';
            categorias.forEach(c => {
                select.innerHTML += `<option value="${c.id_categoria}">${c.nome_categoria}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar categorias:", e);
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

    let _editIdJogo = null;

    function editarJogo(btn) {
        let jogo;
        try {
            jogo = JSON.parse(btn.dataset.jogo);
        } catch (e) {
            console.error('Erro ao parsear dados do jogo:', e);
            return;
        }

        _editIdJogo = jogo.id_jogo;

        document.getElementById('editIdJogo').value = jogo.id_jogo || '';
        document.getElementById('editNomePartida').textContent = formatarNomePartida(jogo);
        document.getElementById('editModalidadePartida').textContent = jogo.nome_modalidade || '';
        document.getElementById('editDataJogo').value = jogo.data_jogo || '';
        document.getElementById('editInicioJogo').value = jogo.inicio_jogo || '';
        document.getElementById('editTerminoJogo').value = jogo.termino_jogo || '';
        document.getElementById('editStatusJogo').value = jogo.status_jogo || 'Agendado';
        document.getElementById('msgEditarJogo').innerHTML = '';

        var modalEl = document.getElementById('modalEditarJogo');
        var selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';

        fetch('../../../api/locais.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (_editIdJogo !== jogo.id_jogo) return;
                const locais = data.success && Array.isArray(data.data) ? data.data : [];
                selectLocal.innerHTML = '<option value="">Selecione um local</option>';
                locais.forEach(function(l) {
                    var sel = Number(l.id_local) === Number(jogo.locais_id_local) ? 'selected' : '';
                    selectLocal.innerHTML += '<option value="' + l.id_local + '" ' + sel + '>' + l.nome_local + '</option>';
                });
            })
            .catch(function() {
                if (_editIdJogo !== jogo.id_jogo) return;
                selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
            });

        var modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    async function salvarEdicaoJogo(e) {
        e.preventDefault();
        var btn = document.getElementById('btnSalvarJogo');
        var msgEl = document.getElementById('msgEditarJogo');
        msgEl.innerHTML = '';

        var id_jogo = document.getElementById('editIdJogo').value;
        var data_jogo = document.getElementById('editDataJogo').value;
        var inicio_jogo = document.getElementById('editInicioJogo').value;
        var termino_jogo = document.getElementById('editTerminoJogo').value;
        var locais_id_local = document.getElementById('editLocalJogo').value;
        var status_jogo = document.getElementById('editStatusJogo').value;

        if (!data_jogo) {
            msgEl.innerHTML = '<span class="text-danger fw-bold">Selecione a data do jogo.</span>';
            return;
        }

        var payload = { id_jogo: Number(id_jogo), data_jogo: data_jogo, status_jogo: status_jogo };
        if (inicio_jogo) payload.inicio_jogo = inicio_jogo;
        if (termino_jogo) payload.termino_jogo = termino_jogo;
        if (locais_id_local) payload.locais_id_local = Number(locais_id_local);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Salvando...';

        try {
            var resp = await fetch('../../../api/jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var data = await resp.json();

            if (data.success) {
                msgEl.innerHTML = '<span class="text-success fw-bold">Jogo atualizado com sucesso!</span>';
                setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarJogo'));
                    if (m) m.hide();
                    carregarJogos();
                }, 1000);
            } else {
                msgEl.innerHTML = '<span class="text-danger fw-bold">' + (data.message || 'Erro ao atualizar jogo.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger fw-bold">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

    async function carregarJogos() {
        const tbody = document.getElementById('tbodyJogos');
        try {
            const idModalidade = document.getElementById('filtroModalidadeJogos').value;
            const idCategoria = document.getElementById('filtroCategoriaJogos').value;
            if (!idModalidade && !idCategoria) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Selecione uma modalidade ou categoria para ver os jogos.</td></tr>';
                return;
            }
            let url = `../../../api/jogos.php?id_interclasse=${idInterclasse}`;
            if (idModalidade) url += `&id_modalidade=${idModalidade}`;
            if (idCategoria) url += `&id_categoria=${idCategoria}`;

            const resp = await fetch(url);
            const data = await resp.json();
            let jogos = Array.isArray(data) ? data : [];

            jogos = jogos.filter(function(j) {
                var nomes = (j.equipes_nomes || '').trim();
                return nomes.indexOf(' vs ') !== -1;
            });

            if (!jogos.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Nenhum jogo encontrado.</td></tr>';
                return;
            }

            var minLargura = Infinity;
            jogos.forEach(function(j) {
                var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
                if (m) {
                    var l = parseInt(m[1], 10);
                    if (l > 0 && l < minLargura) minLargura = l;
                }
            });

            tbody.innerHTML = jogos.map(function(j) {
                let dataJogo = '---';
                if (j.data_jogo) {
                    try {
                        dataJogo = new Date(j.data_jogo + (j.inicio_jogo ? 'T' + j.inicio_jogo : '')).toLocaleString('pt-BR');
                    } catch (_) { dataJogo = j.data_jogo; }
                }
                const statusClass = j.status_jogo === 'Concluido' ? 'status-success' : '';
                var nomePartida = formatarNomePartida(j);
                var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
                if (m && parseInt(m[1], 10) === minLargura) {
                    var fases = { 8: 'Oitavas de final', 4: 'Quartas de final', 2: 'Semifinal', 1: 'Final' };
                    var faseOriginal = fases[minLargura] || '';
                    if (faseOriginal !== 'Final') {
                        nomePartida = nomePartida.replace(faseOriginal, 'Final');
                    }
                }
                return `<tr>
                    <td>${nomePartida}</td>
                    <td>${j.nome_modalidade || '---'}</td>
                    <td>${dataJogo}</td>
                    <td><span class="status ${statusClass}">${j.status_jogo || '---'}</span></td>
                    <td class="text-end">
                        <a href="./jogos.php?id_jogo=${j.id_jogo}" class="acao-btn acao-btn-play" title="Acessar Jogo">
                            <i class="bi bi-play-fill"></i>
                        </a>
                        <button class="acao-btn acao-btn-editar ms-1" title="Editar Jogo"
                            data-jogo='${JSON.stringify(j).replace(/'/g, "&#39;")}'
                            onclick="editarJogo(this)">
                            <i class="bi bi-pencil"></i>
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

            const niveis = Object.keys(rounds).map(Number).sort((a, b) => b - a);

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
                            const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                            html += `<div class="matchup-team">`;
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
    });

    document.getElementById('filtroModalidadeJogos').addEventListener('change', carregarJogos);
    document.getElementById('filtroCategoriaJogos').addEventListener('change', carregarJogos);

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

    document.getElementById('inputBuscaJogo').addEventListener('keyup', function () {
        var termo = this.value.toLowerCase().trim();
        var linhas = document.querySelectorAll('#tbodyJogos tr');
        linhas.forEach(function(tr) {
            if (termo === '') {
                tr.classList.remove('tr-filtro-oculto');
                return;
            }
            var texto = tr.textContent.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                tr.classList.remove('tr-filtro-oculto');
            } else {
                tr.classList.add('tr-filtro-oculto');
            }
        });
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await carregarModalidades();
        await carregarCategorias();
        await carregarJogos();
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
