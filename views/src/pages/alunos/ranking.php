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
include 'componentes/header.php';
?>

    <main class="container py-4">
        <h1 class="visually-hidden">Ranking - Interclasses</h1>
        <h2 class="text-secondary fs-6 text-center mb-4 fw-normal" id="nomeInterclasseRanking">Carregando...</h2>

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

        const params = new URLSearchParams(window.location.search);
        let idInterclasse = params.get('id');
        let dadosAPI = [];
        let categoriasUnicas = [];

        async function init() {
            if (!idInterclasse) {
                try {
                    const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                    const lista = await res.json();
                    const ativo = (lista || []).find(i => String(i.status_interclasse) === '1');
                    if (ativo) idInterclasse = ativo.id_interclasse;
                } catch (e) {}
            }
            if (!idInterclasse) {
                document.getElementById('listaRanking').innerHTML = '<p class="text-center text-muted">Nenhum interclasse selecionado.</p>';
                return;
            }
            await carregarDados();
        }

        async function carregarDados() {
            const container = document.getElementById('listaRanking');
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-danger"></div></div>';

            try {
                const res = await fetch(`../../../../api/ranking.php?id_interclasse=${idInterclasse}`);
                const data = await res.json();

                if (!data || data.length === 0) {
                    container.innerHTML = '<p class="text-center text-muted py-5">Nenhum resultado disponível para esta edição.</p>';
                    return;
                }

                dadosAPI = data;
                categoriasUnicas = [...new Set(data.map(item => item.nome_categoria))];
                document.getElementById('nomeInterclasseRanking').innerText = data[0].nome_interclasse || 'Ranking';

                renderizarFiltros();
                if (categoriasUnicas.length > 0) filtrarCategoria(categoriasUnicas[0]);
            } catch (error) {
                console.error(error);
                container.innerHTML = '<p class="text-center text-danger py-5">Erro ao carregar ranking.</p>';
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
