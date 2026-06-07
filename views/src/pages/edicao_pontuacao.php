<?php
$titulo = "Chaveamentos";
$textTop = "Chaveamentos";
$btnVoltar = true;

require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    body {
        background: #f4f6f9;
    }

    .section-title {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1f2937;
    }

    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }

    .pontuacao-card {
        transition: .2s ease;
        min-height: 180px;
    }

    .pontuacao-card:hover {
        transform: translateY(-4px);
    }

    .pontuacao-numero {
        font-size: 3rem;
        font-weight: 700;
        color: #111827;
    }

    .btn-pontos {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #6b7280;
    }

    .btn-pontos:hover {
        color: #dc2626;
    }

    .filtro-box {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
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

    .search-input {
        border-radius: 12px;
        padding: 10px 14px;
    }
</style>

<main class="main-desktop-layout py-4">

    <div class="container-fluid" style="max-width: 92%;">

        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarPontuacao"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

            <h2 class="section-title d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3"></i>
                Gerenciamento de Chaveamentos
            </h2>

            <p class="text-muted mb-0">
                Configure pontuações, filtre modalidades e acompanhe os jogos.
            </p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Pontuações</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-danger fw-bold px-4" id="btnSalvarPontuacao" onclick="salvarPontuacao()">
                    <i class="bi bi-check-lg me-1"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn btn-dark fw-bold px-4 d-none">
                    Continuar <i class="bi bi-arrow-right-circle ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #e2b714;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-trophy fs-4" style="color:#e2b714;"></i>

                        
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-1">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #9ca3af;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award fs-4" style="color:#9ca3af;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-2">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #b87333;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award-fill fs-4" style="color:#b87333;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-3">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #dc2626;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-heart-fill fs-4 text-danger"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            MULTIPLICADOR
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-arr">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div class="filtro-box mb-4">

            <div class="row g-3 align-items-end">

                <div class="col-lg-3">
                    <label class="form-label fw-semibold">
                        Modalidade
                    </label>

                    <select class="form-select" id="selectModalidade">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>

                <div class="col-lg-3 d-grid">
                    <button class="btn-gerar" id="btnGerarChaveamento" onclick="gerarChaveamento()">
                        <i class="bi bi-diagram-3-fill me-2"></i>
                        Gerar Chaveamento
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

        <div class="card card-custom">
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
</main>

<!-- Modal Editar Jogo -->
<div class="modal fade" id="modalEditarJogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Editar Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarJogo">
                    <input type="hidden" id="editIdJogo">
                    <label for="editDataJogo" class="form-label">Data</label>
                    <input id="editDataJogo" type="date" class="form-control mb-3" required>
                    <label for="editInicioJogo" class="form-label">Horário de início</label>
                    <input id="editInicioJogo" type="time" class="form-control mb-3" required>
                    <label for="editTerminoJogo" class="form-label">Horário de término</label>
                    <input id="editTerminoJogo" type="time" class="form-control mb-3">
                    <label for="editLocalJogo" class="form-label">Local</label>
                    <select id="editLocalJogo" class="form-select mb-3"></select>
                    <div id="msgEditarJogo" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoJogo">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';
    let modalidadesCache = [];

    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let atual = parseInt(elemento.innerText);
        if (isNaN(atual)) atual = 0;
        if (atual + valor >= 0) {
            elemento.innerText = atual + valor;
        }
    }

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
        document.getElementById('nomeInterclassePontuacao').innerText = dados?.nome_interclasse || 'Interclasse';
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);

        const btnBack = document.getElementById('btnVoltarPontuacao');
        if (btnBack) {
            btnBack.href = `./dashboard.php?id=${idInterclasse}`;
        }

        if (dados) {
            document.getElementById('pontos-1').innerText = dados.ponto_1_lugar || 0;
            document.getElementById('pontos-2').innerText = dados.ponto_2_lugar || 0;
            document.getElementById('pontos-3').innerText = dados.ponto_3_lugar || 0;
            document.getElementById('pontos-arr').innerText = dados.valor_item_arrecadacao || 0;
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
            let url = `../../../api/jogos.php?x=1`;
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

    window.salvarPontuacao = async function() {
        const btn = document.getElementById('btnSalvarPontuacao');
        const pontos1 = parseInt(document.getElementById('pontos-1').innerText);
        const pontos2 = parseInt(document.getElementById('pontos-2').innerText);
        const pontos3 = parseInt(document.getElementById('pontos-3').innerText);
        const pontosArr = parseInt(document.getElementById('pontos-arr').innerText);

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const formData = new FormData();
            formData.append('ponto_1_lugar', pontos1);
            formData.append('ponto_2_lugar', pontos2);
            formData.append('ponto_3_lugar', pontos3);
            formData.append('valor_item_arrecadacao', pontosArr);

            const resp = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao salvar.');

            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvo!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
            }, 2000);
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
        }
    };

    window.gerarChaveamento = async function() {
        const idModalidade = document.getElementById('selectModalidade').value;
        const msgEl = document.getElementById('msgChaveamento');
        const btn = document.getElementById('btnGerarChaveamento');

        if (!idModalidade) {
            msgEl.innerHTML = '<p class="text-danger fw-bold mb-0">Selecione uma modalidade primeiro.</p>';
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const tipoNome = mod?.nome_tipo_modalidade || '';

        if (tipoNome === 'Individual') {
            msgEl.innerHTML = '<p class="text-warning fw-bold mb-0">Modalidade individual requer registro manual de ranking (1º, 2º, 3º lugar).</p>';
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
            carregarJogos();
        } catch (err) {
            msgEl.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
        }
    };

    window.editarJogo = async function(id, data, inicio, termino, idLocal) {
        document.getElementById('editIdJogo').value = id;
        document.getElementById('editDataJogo').value = data;
        document.getElementById('editInicioJogo').value = inicio ? inicio.slice(0, 5) : '';
        document.getElementById('editTerminoJogo').value = termino ? termino.slice(0, 5) : '';
        document.getElementById('msgEditarJogo').innerHTML = '';

        const selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';
        try {
            const resp = await fetch('../../../api/locais.php');
            const result = await resp.json();
            const locais = (result && result.data) || [];
            selectLocal.innerHTML = '<option value="">Selecione um local</option>';
            locais.forEach(l => {
                const sel = Number(l.id_local) === Number(idLocal) ? ' selected' : '';
                selectLocal.innerHTML += `<option value="${l.id_local}"${sel}>${l.nome_local}</option>`;
            });
        } catch (e) {
            selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarJogo')).show();
    }

    document.getElementById('formEditarJogo').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editIdJogo').value;
        const data = document.getElementById('editDataJogo').value;
        const inicio = document.getElementById('editInicioJogo').value;
        const termino = document.getElementById('editTerminoJogo').value;
        const btn = document.getElementById('btnSalvarEdicaoJogo');
        const msg = document.getElementById('msgEditarJogo');

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            const idLocal = document.getElementById('editLocalJogo').value;
            const resp = await fetch('../../../api/jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_jogo: Number(id),
                    data_jogo: data,
                    inicio_jogo: inicio + ':00',
                    termino_jogo: termino ? termino + ':00' : null,
                    locais_id_local: idLocal ? Number(idLocal) : null
                })
            });
            const result = await resp.json();
            if (!result.success) throw new Error(result.message || 'Erro ao salvar.');
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Jogo atualizado com sucesso.</p>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarJogo')).hide();
            carregarJogos();
        } catch (err) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Salvar';
        }
    });

    document.getElementById('selectModalidade').addEventListener('change', () => {
        document.getElementById('msgChaveamento').innerHTML = '';
        carregarJogos();
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        const btnContinuar = document.getElementById('btnContinuarPontuacao');
        if (btnContinuar) {
            btnContinuar.href = `./edicao_resumo.php?id=${idInterclasse}&modo=create`;
            if (modo === 'create') btnContinuar.classList.remove('d-none');
        }

        await Promise.all([
            carregarModalidades(),
            carregarJogos()
        ]);
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
