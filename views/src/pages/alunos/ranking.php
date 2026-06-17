<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modalidades - Interclasse 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            
        }

        /* Container Principal do App */
        /* Container Principal do App */
.app-container {
    display: flex;
    width: 100vw;  /* Ocupa 100% da largura da janela visual */
    height: 100vh; /* Ocupa 100% da altura da janela visual */
    background-color: #f4f4f4;
   
    overflow: hidden;
   
}

   .sidebar {
    width: 70px;
    background: #e60012;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    align-items: center;
    padding: 25px 0;
    height: 100vh;
    flex-shrink: 0;
}

.sidebar a {
    color: white;
    text-decoration: none;
    opacity: 0.7;
    transition: all .2s ease;
    display: flex;
    justify-content: center;
    align-items: center;
}

.sidebar a i {
    font-size: 25px;
    width: 25px;
    height: 25px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.sidebar a:hover,
.sidebar a.active {
    opacity: 1;
    transform: scale(1.1);
}

        /* --- CONTEÚDO PRINCIPAL --- */
       .main-content{
    flex:1;
    padding:40px;
    overflow-y:auto;
    background:#f6f7fb;
}
        /* Botão Vermelho Superior */
        .badge-interclasse {
            background-color: #e30613;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 15px;
            border-radius: 4px;
            width: fit-content;
            margin-bottom: 25px;
            text-decoration: none;
        }

        /* Título Modalidades */
        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            color: #000000;
            font-weight: 700;
            margin-bottom: 30px;
        }

        /* --- GRID DE MODALIDADES --- */
        .modalidades-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Card de Modalidade Padrão (Não selecionado) */
        .modalidade-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            color: #000000; /* Letra preta por padrão */
            position: relative;
            user-select: none;
            transition: transform 0.15s, background-color 0.2s, color 0.2s;
        }

 .modalidade-card {
    font-size: 18px;
    font-weight: 500;
}
        .modalidade-card:hover {
            transform: translateY(-2px);
        }

        /* REQUISITO: Card Selecionado (Fundo verde, letra e ícone em branco) */
        .modalidade-card.selected {
            background-color: #5cb85c; /* Tom de verde idêntico ao da imagem */
            color: #ffffff !important; /* Letra branca forcada */
        }

        /* Checkmark redondo verde no canto superior direito do card selecionado */
        .modalidade-card.selected::after {
            content: "✓";
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background-color: #5cb85c;
            border: 2px solid #ffffff;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* --- ÁREA INFERIOR DE SALVAMENTO --- */
        .footer-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .counter-text {
            font-size: 14px;
            color: #333333;
            font-weight: 500;
        }

        /* REQUISITO: Botão verde de salvar */
      .btn-save {
    background: linear-gradient(135deg, #e60012, #ff3344);
    color: white;
    border: none;
    padding: 14px 28px;
    min-width: 220px;
    height: 50px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    box-shadow: 0 10px 25px rgba(230, 0, 18, .25);

    transition: all .25s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(230, 0, 18, .35);
}

.btn-save:active {
    transform: scale(.98);
}

        /* Texto sutil "Confirmar" na parte inferior */
        .bottom-label {
            font-size: 14px;
            color: #b3b3b3;
            font-weight: 500;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <aside class="sidebar">
    <a href="#"><i class="bi bi-person-gear"></i></a>

    <a href="home.php">
        <i class="bi bi-house"></i>
    </a>

    <a href="ranking.php" class="active">
        <i class="bi bi-trophy"></i>
    </a>

    <a href="agenda.php">
        <i class="bi bi-calendar3"></i>
    </a>

    <a href="notificacao.php">
        <i class="bi bi-bell"></i>
    </a>

    <a href="#" class="logout">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</aside>

        <main class="main-content">
            
            

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
            const response = await fetch(`../../../../api/ranking.php?id_interclasse=${idInterclasse}`);
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

    window.addEventListener('load', init);
</script>


</body>
</html>