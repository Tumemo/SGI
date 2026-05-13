<?php
$titulo = "Arrecadação";
$textTop = "Arrecadação";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="px-3 mt-4">
        <label class="form-label small fw-bold text-muted">Filtrar Categoria:</label>
        <select id="filtroCategoriaMobile" class="form-select shadow-sm mb-3">
            <option value="todos">Todas as Categorias</option>
        </select>
    </div>

    <div class="card shadow m-auto" style="width: 22rem;">
        <div class="card-header fw-bold text-center bg-white">
            Contagem de Itens (Arrecadação)
        </div>
        <ul class="list-group list-group-flush" id="listaArrecadacaoMobile">
            <li class="list-group-item text-center text-muted">(Carregando...)</li>
        </ul>
    </div>
    
    <div class="container mt-3 mb-5 pb-4">
        <div id="barraContinuarArrecadacaoMobile" class="d-none">
            <button id="btnSalvarMobile" class="btn btn-danger w-100 fw-semibold rounded-3 py-2 shadow-sm">Salvar Alterações</button>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 1000px;">
        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>

            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Lançamento de Arrecadações</h4>
                    <div class="mt-3" style="min-width: 250px;">
                        <label class="small fw-bold text-muted">Filtrar por Categoria:</label>
                        <select id="filtroCategoriaDesktop" class="form-select border-0 shadow-sm mt-1">
                            <option value="todos">Todas as Categorias</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.location.reload()" class="btn bg-white fw-semibold rounded-3 px-4 py-2" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <button type="button" id="btnSalvarDesktop" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                        Salvar Dados
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5" id="listaArrecadacaoDesktop"></div>
    </div>
</main>

<div id="barraContinuarArrecadacaoDesktop" class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, #f8f9fa 70%, rgba(248, 249, 250, 0) 100%); padding: 24px 0; z-index: 1020;">
    <div class="container-fluid d-flex justify-content-end align-items-center" style="max-width: 1000px; margin-left: auto; margin-right: auto;">
        <span class="text-muted me-3 small" id="statusSincronizacao">Dados guardados localmente</span>
    </div>
</div>

<?php require_once '../componentes/footer.php'; ?>

<script>
    // Prefixo para evitar conflitos no LocalStorage
    const storagePrefix = 'sgi_items_';
    const paramsArrecadacao = new URLSearchParams(window.location.search);
    const idInterclasseArrecadacao = paramsArrecadacao.get('id');
    
    let todasAsTurmas = []; 

    // Busca valor no LocalStorage ou o valor original vindo da BD
    function getQuantidade(turma) {
        const local = localStorage.getItem(`${storagePrefix}${turma.id_turma}`);
        return local !== null ? Number(local) : Number(turma.qtd_itens_arrecadados || 0);
    }

    function salvarLocal(idTurma, valor) {
        localStorage.setItem(`${storagePrefix}${idTurma}`, String(valor));
        document.getElementById('statusSincronizacao').innerText = "Existem alterações por guardar...";
    }

    function renderizarTelas(categoriaFiltro = 'todos') {
        const listaMobile = document.getElementById('listaArrecadacaoMobile');
        const listaDesktop = document.getElementById('listaArrecadacaoDesktop');
        
        const turmasFiltradas = categoriaFiltro === 'todos' 
            ? todasAsTurmas 
            : todasAsTurmas.filter(t => t.nome_categoria === categoriaFiltro);

        if (turmasFiltradas.length === 0) {
            const msg = '<p class="p-4 text-center text-muted">Nenhuma turma encontrada nesta categoria.</p>';
            listaMobile.innerHTML = msg;
            listaDesktop.innerHTML = msg;
            return;
        }

        // Template Mobile
        listaMobile.innerHTML = turmasFiltradas.map(turma => `
            <li class="list-group-item justify-content-between d-flex align-items-center px-3">
                <div>
                    <span class="fw-bold d-block">${turma.nome_turma}</span>
                    <small class="text-muted">${turma.nome_categoria || 'Geral'}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" class="form-control form-control-sm arrec-input text-center" 
                        data-id-turma="${turma.id_turma}" 
                        value="${getQuantidade(turma)}" style="width: 80px;">
                </div>
            </li>
        `).join('');

        // Template Desktop
        listaDesktop.innerHTML = turmasFiltradas.map(turma => `
            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold text-dark fs-6 d-block">${turma.nome_turma}</span>
                        <span class="badge bg-light text-dark fw-normal border">${turma.nome_categoria || 'Geral'}</span>
                    </div>
                    <div class="input-group" style="max-width: 150px;">
                        <input type="number" class="form-control text-center arrec-input" 
                            data-id-turma="${turma.id_turma}" 
                            value="${getQuantidade(turma)}">
                        <span class="input-group-text bg-light border-start-0 small">Qtd</span>
                    </div>
                </div>
            </div>
        `).join('');

        vincularEventosInputs();
    }

    function vincularEventosInputs() {
        document.querySelectorAll('.arrec-input').forEach(input => {
            input.addEventListener('input', (e) => {
                salvarLocal(e.target.dataset.idTurma, e.target.value);
            });
        });
    }

    async function carregarDados() {
        try {
            const ativo = idInterclasseArrecadacao
                ? await window.SGIInterclasse.getInterclasseById(idInterclasseArrecadacao)
                : await window.SGIInterclasse.getActiveInterclasse();
            
            if (!ativo) return;

            document.getElementById('nomeInterclasseArrecadacao').innerText = ativo.nome_interclasse;
            document.getElementById('barraContinuarArrecadacaoMobile').classList.remove('d-none');

            const res = await fetch(`../../../api/turmas.php?id_interclasse=${ativo.id_interclasse}`);
            todasAsTurmas = await res.json();

            const categorias = [...new Set(todasAsTurmas.map(t => t.nome_categoria))].filter(Boolean);
            const selects = [document.getElementById('filtroCategoriaMobile'), document.getElementById('filtroCategoriaDesktop')];
            
            categorias.forEach(cat => {
                selects.forEach(select => {
                    const opt = document.createElement('option');
                    opt.value = cat; opt.innerText = cat;
                    select.appendChild(opt);
                });
            });

            renderizarTelas();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
        }
    }

async function salvarNoServidor() {
    const btnDesk = document.getElementById('btnSalvarDesktop');
    const btnMob = document.getElementById('btnSalvarMobile');
    
    // Bloqueia os botões para evitar cliques duplos durante o processamento
    btnDesk.disabled = btnMob.disabled = true;
    const textoOriginal = btnDesk.innerText;
    btnDesk.innerText = "A guardar...";

    // Prepara os dados para envio em lote
    // O backend percorrerá o array 'arrecadacoes' para atualizar cada turma
    const payload = {
        id_interclasse: idInterclasseArrecadacao,
        arrecadacoes: todasAsTurmas.map(t => ({
            id_turma: t.id_turma,
            quantidade: localStorage.getItem(`${storagePrefix}${t.id_turma}`) || t.qtd_itens_arrecadados || 0
        }))
    };

    try {
        // O caminho deve sair de views/src/pages/ para chegar em api/
        const response = await fetch('../../../api/arrecadacao.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify(payload)
        });

        // Verifica se o servidor encontrou o arquivo e respondeu sem erros de sistema
        if (!response.ok) {
            throw new Error(`Erro de rede: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Limpa o cache local do navegador após o sucesso no banco de dados
            todasAsTurmas.forEach(t => {
                localStorage.removeItem(`${storagePrefix}${t.id_turma}`);
            });

            alert('Dados guardados e pontuações calculadas com sucesso!');
            window.location.reload();
        } else {
            // Exibe a mensagem de erro específica retornada pelo PHP (ex: "Dados incompletos")
            alert('Erro do servidor: ' + result.message);
        }
    } catch (error) {
        alert('Erro de comunicação: Verifique se o ficheiro api/arrecadacao.php existe e se o banco de dados está online.');
        console.error('Falha no salvamento:', error);
    } finally {
        // Restaura os botões para o estado inicial
        btnDesk.disabled = btnMob.disabled = false;
        btnDesk.innerText = textoOriginal;
    }
}

    document.getElementById('filtroCategoriaMobile').addEventListener('change', (e) => renderizarTelas(e.target.value));
    document.getElementById('filtroCategoriaDesktop').addEventListener('change', (e) => renderizarTelas(e.target.value));
    document.getElementById('btnSalvarDesktop').addEventListener('click', salvarNoServidor);
    document.getElementById('btnSalvarMobile').addEventListener('click', salvarNoServidor);

    window.addEventListener('load', carregarDados);
</script>