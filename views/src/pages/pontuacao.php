<?php
$tituloPagina = 'SGI - Mesário - Chaveamentos';
$titulo = 'Chaveamentos';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>



<main class="main-desktop-layout py-4">
    <div class="container-fluid" style="max-width: 92%;">

        <div class="mb-5">
            <a href="./home.php"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>
        </div>

        <div class="filtro-box mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Modalidade</label>
                    <select class="form-select" id="selectModalidade">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>
                <div class="col-lg-9">
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
            const fases = { 16: 'Oitavas de final', 8: 'Quartas de final', 4: 'Semifinal', 2: 'Final', 1: 'Campeão' };
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

    document.getElementById('selectModalidade').addEventListener('change', () => {
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
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
