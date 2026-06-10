<?php
$tituloPagina = 'SGI - Mesário - Chaveamentos';
$titulo = 'Chaveamentos';
$mostrarVoltar = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<style>
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

    .filtro-box {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
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
            <a href="./home.php"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

            <h2 class="section-title d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3"></i>
                Chaveamentos
            </h2>

            <p class="text-muted mb-0">
                Visualize e gerencie os chaveamentos das modalidades.
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
                    <input type="text" class="form-control search-input" placeholder="Buscar partida..." style="max-width:300px" id="inputBuscaJogo">
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
        document.getElementById('nomeInterclassePontuacao').innerText = dados?.nome_interclasse || 'Interclasse';
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);
        return idInterclasse;
    }

    async function carregarModalidades() {
        try {
            const resp = await fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
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
            let url = `../../../../api/jogos.php?x=1`;
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
                        <button class="btn btn-link text-decoration-none" onclick="window.location.href='./jogos.php?id_jogo=${j.id_jogo}'">
                            <i class="bi bi-play-circle"></i> Ir para o jogo
                        </button>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            console.error("Erro ao carregar jogos:", e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Erro ao carregar jogos.</td></tr>';
        }
    }

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
            const resp = await fetch('../../../../api/chaveamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idModalidade), tipo_modalidade: 'mata_mata' })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao gerar chaveamento.');

            msgEl.innerHTML = `<p class="text-success fw-bold mb-0">${data.message} (${data.jogos_criados} jogos criados).</p>`;
            const linkArvore = document.getElementById('linkVerArvore');
            linkArvore.classList.remove('d-none');
            document.getElementById('btnVerArvore').href = `../../chaveamento_arvore.php?id=${idInterclasse}`;
            carregarJogos();
        } catch (err) {
            msgEl.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
        }
    };

    document.getElementById('selectModalidade').addEventListener('change', () => {
        document.getElementById('msgChaveamento').innerHTML = '';
        carregarJogos();
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        await Promise.all([
            carregarModalidades(),
            carregarJogos()
        ]);
    });
</script>

<?php
require_once '../../componentes/footer.php';
?>
