<?php
$titulo = "Ranking Geral";
$textTop = "Ranking de Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Estilização dos Cards e Barras */
    .btn-categoria { transition: all 0.2s; border-radius: 20px !important; min-width: 120px; }
    .btn-categoria.ativo { 
        background-color: #ed1c24 !important; 
        color: white !important; 
        border-color: #ed1c24 !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .card-turma { transition: transform 0.2s; border-left: 5px solid #dee2e6; }
    .card-turma:hover { transform: translateY(-3px); }
    .posicao-1 { border-left-color: #FFD700 !important; } /* Ouro */
    .posicao-2 { border-left-color: #C0C0C0 !important; } /* Prata */
    .posicao-3 { border-left-color: #CD7F32 !important; } /* Bronze */
    
    .barra-fundo { background-color: #f0f0f0; height: 8px; border-radius: 10px; overflow: hidden; }
    .barra-progresso { background-color: #ed1c24; height: 100%; transition: width 0.8s ease; }
    
    .badge-pontos { background-color: #f8f9fa; color: #333; border: 1px solid #ddd; }
</style>

<main class="container d-md-none py-4" style="margin-bottom: 100px;">
    <div id="msgMob"></div>
    <div id="filtrosMob" class="d-flex overflow-auto gap-2 pb-3 mb-4">
        </div>
    <div id="listaMob"></div>
</main>

<main class="d-none d-md-block container-fluid p-5">
    <div class="row px-lg-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 20px;">
                <h4 class="fw-bold mb-4">Categorias</h4>
                <div id="filtrosDesk" class="d-flex flex-column gap-2">
                    </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0" id="nomeInterclasse">Ranking</h2>
                <span class="badge bg-light text-dark border p-2" id="totalTurmas">0 Turmas</span>
            </div>
            <div id="msgDesk"></div>
            <div id="listaDesk" class="row g-3">
                </div>
        </div>
    </div>
</main>

<script>
    // Configurações Globais
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id'); // Pega o ?id=21 da URL

    let dadosAPI = [];
    let categoriasUnicas = [];

    async function init() {
        if (!idInterclasse) {
            exibirMensagem("ID do Interclasse não fornecido na URL.", "danger");
            return;
        }
        await carregarDados();
    }

    async function carregarDados() {
        const loading = '<div class="text-center p-5"><div class="spinner-border text-danger"></div></div>';
        document.getElementById('listaMob').innerHTML = loading;
        document.getElementById('listaDesk').innerHTML = loading;

        try {
            // Chamada para o seu Back-end passando o filtro de interclasse
            const response = await fetch(`../../../api/ranking.php?id_interclasse=${idInterclasse}`);
            const data = await response.json();

            if (!data || data.length === 0) {
                exibirMensagem("Nenhum dado encontrado para este interclasse.", "warning");
                return;
            }

            dadosAPI = data;
            
            // Extrair categorias únicas presentes nos dados
            categoriasUnicas = [...new Set(data.map(item => item.nome_categoria))];
            
            document.getElementById('nomeInterclasse').innerText = data[0].nome_interclasse;
            document.getElementById('totalTurmas').innerText = `${data.length} Turmas`;

            renderizarFiltros();
            // Inicia exibindo a primeira categoria encontrada
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
            btn.innerText = cat;
            btn.id = `btn-${cat.replace(/\s/g, '')}`;
            btn.onclick = () => filtrarCategoria(cat);
            
            fMob.appendChild(btn.cloneNode(true)); // Mobile precisa de uma cópia do elemento
            // Para o desktop, precisamos reatribuir o evento pois o cloneNode não copia eventos complexos às vezes
            const btnD = btn.cloneNode(true);
            btnD.onclick = () => filtrarCategoria(cat);
            fDesk.appendChild(btnD);
        });
    }

    function filtrarCategoria(categoria) {
        // Atualizar visual dos botões
        document.querySelectorAll('.btn-categoria').forEach(b => {
            b.classList.remove('ativo');
            if (b.innerText === categoria) b.classList.add('ativo');
        });

        const turmasFiltradas = dadosAPI.filter(t => t.nome_categoria === categoria);
        renderizarRanking(turmasFiltradas);
    }

    function renderizarRanking(turmas) {
        const cMob = document.getElementById('listaMob');
        const cDesk = document.getElementById('listaDesk');
        cMob.innerHTML = '';
        cDesk.innerHTML = '';

        const maxPontos = Math.max(...turmas.map(t => t.pontuacao_turma)) || 1;

        turmas.forEach((t, index) => {
            const posicao = index + 1;
            const porcentagem = (t.pontuacao_turma / maxPontos) * 100;
            const classeDestaque = posicao <= 3 ? `posicao-${posicao}` : '';

            const html = `
                <div class="col-12">
                    <div class="card card-turma shadow-sm mb-3 p-3 ${classeDestaque}">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <h3 class="fw-bold m-0 text-secondary" style="width: 40px;">${posicao}º</h3>
                                <div>
                                    <h5 class="fw-bold m-0 text-dark">${t.nome_turma}</h5>
                                    <small class="text-muted">${t.nome_fantasia_turma || t.turno_turma}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-pontos fs-6 px-3 py-2 rounded-pill">
                                    ${t.pontuacao_turma} pts
                                </span>
                            </div>
                        </div>
                        <div class="barra-fundo mt-3">
                            <div class="barra-progresso" style="width: ${porcentagem}%"></div>
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

    window.onload = init;
</script>

<?php require_once '../componentes/footer.php'; ?>