<?php
$tituloPagina = 'SGI - Ranking';
$cssExtra = '
        .pos-1 { border-left: 5px solid #FFD700 !important; }
        .pos-2 { border-left: 5px solid #C0C0C0 !important; }
        .pos-3 { border-left: 5px solid #CD7F32 !important; }
        .barra-fundo { background-color: #f0f0f0; height: 8px; border-radius: 10px; overflow: hidden; }
        .barra-progresso { background-color: #ed1c24; height: 100%; transition: width 0.8s ease; }
        .btn-categoria { transition: all 0.2s; border-radius: 20px !important; min-width: 100px; }
        .btn-categoria.ativo { background-color: #ed1c24 !important; color: white !important; border-color: #ed1c24 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
';
include 'componentes/head.php';
$titulo = 'Ranking';
$mostrarVoltar = true;
$urlVoltar = './home.php';
?>

    <main class="container py-4">
        <h1 class="visually-hidden">Ranking - Interclasses</h1>

        <div id="filtros" class="d-flex overflow-auto gap-2 pb-3 mb-4"></div>

        <div id="listaRanking" class="d-flex flex-column gap-3">
            <div class="text-center text-muted py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status"><span class="visually-hidden">Carregando...</span></div>
                Carregando ranking...
            </div>
        </div>
    </main>

<?php
$paginaAtiva = 'ranking';
include 'componentes/nav.php';
?>

    <script>
        function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

        function numeroCategoria(nome) {
            const romanos = { I: 1, II: 2, III: 3, IV: 4, V: 5, VI: 6, VII: 7, VIII: 8, IX: 9, X: 10 };
            const partes = String(nome || '').trim().split(/\s+/);
            const ultimo = partes[partes.length - 1] || '';
            if (/^\d+$/.test(ultimo)) return parseInt(ultimo, 10);
            if (romanos[ultimo.toUpperCase()]) return romanos[ultimo.toUpperCase()];
            return Number.MAX_SAFE_INTEGER;
        }

        const params = new URLSearchParams(window.location.search);
        let idInterclasse = params.get('id');
        let dadosAPI = [];
        let categoriasUnicas = [];

        async function init() {
            if (!idInterclasse) {
                try {
                    const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                    if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
                    
                    const data = await res.json();
                    const lista = Array.isArray(data) ? data : [data];
                    
                    const ativo = lista.find(i => String(i.status_interclasse) === '1');
                    if (ativo) {
                        idInterclasse = ativo.id_interclasse;
                        
                        const url = new URL(window.location);
                        url.searchParams.set('id', idInterclasse);
                        window.history.replaceState({}, '', url);
                    }
                } catch (e) {
                    console.error("Erro ao carregar interclasse ativo:", e);
                    document.getElementById('listaRanking').innerHTML = `<p class="text-center text-danger py-5">Erro ao buscar interclasse ativo: ${e.message}</p>`;
                    return;
                }
            }
            
            if (!idInterclasse) {
                document.getElementById('listaRanking').innerHTML = '<p class="text-center text-muted py-5">Nenhum interclasse selecionado ou ativo no momento.</p>';
                return;
            }
            await carregarDados();
        }

        async function carregarDados() {
            const container = document.getElementById('listaRanking');
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-danger"></div></div>';

            try {
                const response = await fetch(`../../../../api/ranking.php?id_interclasse=${idInterclasse}`);
                
                // 1. Tratamento de erro HTTP (Servidor offline, 404, 500)
                if (!response.ok) {
                    throw new Error(`O servidor retornou um erro HTTP ${response.status}: ${response.statusText}`);
                }

                // 2. Lê a resposta como Texto primeiro (Para capturar erros do PHP não formatados como JSON)
                const textData = await response.text();
                if (!textData || textData.trim() === '') {
                    throw new Error("A API retornou uma resposta completamente vazia (em branco).");
                }

                // 3. Tenta converter para JSON
                let data;
                try {
                    data = JSON.parse(textData);
                } catch (e) {
                    console.error("Resposta crua da API (Não é JSON válido):", textData);
                    throw new Error(`Falha ao converter os dados (Formato Inválido). O PHP pode estar imprimindo erros. Verifique o console. Resposta: ${textData.substring(0, 100)}...`);
                }

                // 4. Trata se a API retornou um objeto de erro documentado
                if (data.error || data.erro) {
                    throw new Error(`Erro retornado pela API: ${data.error || data.erro}`);
                }

                // 5. Verifica se os dados estão vazios
                if (!data || (Array.isArray(data) && data.length === 0)) {
                    container.innerHTML = '<p class="text-center text-muted py-5">Nenhum resultado disponível para esta edição.</p>';
                    return;
                }

                // 6. Normaliza para array (mesmo se for apenas 1 objeto)
                const rankingArray = Array.isArray(data) ? data : [data];

                // 7. Valida a estrutura dos dados
                if (!rankingArray[0] || rankingArray[0].nome_categoria === undefined) {
                    console.warn("Objeto recebido:", rankingArray[0]);
                    throw new Error("Os dados recebidos da API não contêm a coluna 'nome_categoria'. Verifique a consulta SQL do seu arquivo de API.");
                }

                dadosAPI = rankingArray;
                categoriasUnicas = [...new Set(rankingArray.map(item => item.nome_categoria))].sort((a, b) => numeroCategoria(a) - numeroCategoria(b));

                renderizarFiltros();
                if (categoriasUnicas.length > 0) filtrarCategoria(categoriasUnicas[0]);
                
            } catch (error) {
                console.error("Erro detalhado do Ranking:", error);
                container.innerHTML = `
                    <div class="alert alert-danger m-3 shadow-sm border-0" role="alert">
                        <h4 class="alert-heading fs-5"><i class="bi bi-bug me-2"></i> Erro de Diagnóstico</h4>
                        <p class="mb-1">Houve um problema técnico ao carregar o ranking. Leia o erro abaixo:</p>
                        <hr class="my-2">
                        <p class="mb-0 text-break fw-bold text-danger" style="font-family: monospace;">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderizarFiltros() {
            const container = document.getElementById('filtros');
            container.innerHTML = '';
            categoriasUnicas.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-outline-secondary btn-categoria';
                btn.innerText = cat;
                btn.onclick = () => filtrarCategoria(cat);
                container.appendChild(btn);
            });
        }

        function filtrarCategoria(categoria) {
            document.querySelectorAll('.btn-categoria').forEach(b => {
                b.classList.remove('ativo');
                if (b.innerText === categoria) b.classList.add('ativo');
            });
            renderizarRanking(dadosAPI.filter(t => t.nome_categoria === categoria));
        }

        function renderizarRanking(turmas) {
            const container = document.getElementById('listaRanking');
            const maxPontos = Math.max(...turmas.map(t => t.pontuacao_sem_penalidade || t.pontuacao_turma)) || 1;

            container.innerHTML = turmas.map((t, index) => {
                const posicao = index + 1;
                const ptsSemPenalidade = t.pontuacao_sem_penalidade ?? t.pontuacao_turma;
                const ptsComPenalidade = t.pontuacao_turma;
                const perdeu = ptsSemPenalidade - ptsComPenalidade;
                const porcentagemSem = (ptsSemPenalidade / maxPontos) * 100;
                const porcentagemCom = (ptsComPenalidade / maxPontos) * 100;
                return `
                    <div class="bg-white rounded-3 shadow-sm p-3 ${posicao <= 3 ? `pos-${posicao}` : ''}">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fw-bold fs-4 text-secondary" style="width: 40px;">${posicao}º</span>
                                <div>
                                    <strong class="d-block">${esc(t.nome_turma)}</strong>
                                    <small class="text-muted">${esc(t.nome_fantasia_turma || t.turno_turma || '')}</small>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-6">${ptsComPenalidade} pts</span>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Sem penalidades</span>
                                <span>${ptsSemPenalidade} pts</span>
                            </div>
                            <div class="barra-fundo" style="height:6px;">
                                <div class="barra-progresso" style="width:${porcentagemSem}%;background-color:#adb5bd;"></div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold text-danger">Pontuação final</span>
                                <span class="fw-semibold">${ptsComPenalidade} pts${perdeu > 0 ? ` <span class="text-danger">(-${perdeu})</span>` : ''}</span>
                            </div>
                            <div class="barra-fundo" style="height:8px;">
                                <div class="barra-progresso" style="width:${porcentagemCom}%;"></div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>