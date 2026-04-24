<?php
$titulo = "Resumo";
$textTop = "Interclasse 2026";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="mt-4 d-flex justify-content-center align-items-center flex-column position-relative d-md-none" style="margin-bottom: 120px;">
    <a href="./novaEdicao_modalidades.php" id="linkEditarModalidadesMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Modalidades</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoModalidadesMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="./edicao_regulamentos.php" id="linkEditarRegulamentosMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Regulamentos</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoRegulamentosMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <section class="d-flex gap-4 mt-3 position-fixed translate-middle" style="width: max-content; top: 85%; left: 50%; z-index: 10; cursor: pointer;">
        <a href="./edicao_regulamentos.php" id="btnVoltarMobile" class="btn btn-dark">Voltar</a>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">Adicionar categoria</button>
    </section>
</main>


<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block" style="padding-top: 2rem; padding-bottom: 120px;">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 shadow-sm" style="border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>
        </div>

        <div class="d-flex flex-column gap-3 mb-5">
            <a href="./novaEdicao_modalidades.php" id="linkEditarModalidadesDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Modalidades</h6>
                    <span class="text-secondary text-truncate" id="resumoModalidadesDesktop" style="font-size: 0.95rem;">
                        (Carregando...)
                    </span>
                </div>
            </a>

            <a href="./edicao_regulamentos.php" id="linkEditarRegulamentosDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Regulamento</h6>
                    <span class="text-secondary text-truncate" id="resumoRegulamentosDesktop" style="font-size: 0.95rem;">
                        (Carregando...)
                    </span>
                </div>
            </a>
        </div>
    </div>

    <div class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, rgba(248,249,250,1) 70%, rgba(248,249,250,0) 100%); padding: 30px 0;">
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3" style="max-width: 80%;">
            <a href="./edicao_regulamentos.php" id="btnVoltarDesktop" class="text-decoration-none">
                <button class="btn btn-outline-danger bg-white fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Voltar
                </button>
            </a>
            <button class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">
                Adicionar categoria
            </button>
        </div>
    </div>
</main>


<div class="modal fade" id="adicionarCategoria" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formNovaCategoria">
                <div class="modal-body">
                    <div class="mb-5">
                        <label for="inputNomeCategoria" class="form-label">Insira o nome da categoria:</label>
                        <input type="text" class="form-control" id="inputNomeCategoria" placeholder="EX: Ensino Médio" required>
                    </div>

                    <div class="d-flex justify-content-center gap-4 mb-0">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnCriarCategoria">Criar</button>
                    </div>
                </div>
            </form>
            
            <div class="mb-1 border border-0"></div>
        </div>
    </div>
</div>


<script>
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // Transferindo IDs para os links
    if (idInterclasse) {
        document.getElementById('btnVoltarMobile').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
        document.getElementById('btnVoltarDesktop').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
        
        document.getElementById('linkEditarModalidadesMobile').href = `./novaEdicao_modalidades.php?id=${idInterclasse}`;
        document.getElementById('linkEditarModalidadesDesktop').href = `./novaEdicao_modalidades.php?id=${idInterclasse}`;
        
        document.getElementById('linkEditarRegulamentosMobile').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
        document.getElementById('linkEditarRegulamentosDesktop').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
    }

    // Carregar Resumos
    async function carregarResumos() {
        if (!idInterclasse) return;

        // --- Tentativa de carregar Modalidades ---
        try {
            const resMod = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const dataMod = await resMod.json();
            
            let textoModalidades = "(Nenhuma modalidade cadastrada)";
            
            if (dataMod && dataMod.length > 0) {
                const nomes = dataMod.map(m => m.nome_modalidade).join(', ');
                textoModalidades = `(${nomes})`;
            }

            document.getElementById('resumoModalidadesMobile').innerText = textoModalidades;
            document.getElementById('resumoModalidadesDesktop').innerText = textoModalidades;
        } catch (error) {
            // Em caso de erro, mostramos diretamente que não há nada
            const textoPadrao = "(Nenhuma modalidade cadastrada)";
            document.getElementById('resumoModalidadesMobile').innerText = textoPadrao;
            document.getElementById('resumoModalidadesDesktop').innerText = textoPadrao;
        }

        // --- Tentativa de carregar Regulamentos ---
        try {
            const resReg = await fetch(`../../../api/regulamentos.php?id_interclasse=${idInterclasse}`);
            const dataReg = await resReg.json();
            
            let textoRegulamento = "(Nenhum regulamento cadastrado)";

            if (dataReg && dataReg.success !== false && dataReg.pontos_1_lugar !== undefined) {
                textoRegulamento = `(1º lugar ${dataReg.pontos_1_lugar} pts, 2º lugar ${dataReg.pontos_2_lugar} pts, 3º lugar ${dataReg.pontos_3_lugar} pts)`;
            }

            document.getElementById('resumoRegulamentosMobile').innerText = textoRegulamento;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoRegulamento;
            
        } catch (error) {
            const textoPadrao = "(Nenhum regulamento cadastrado)";
            document.getElementById('resumoRegulamentosMobile').innerText = textoPadrao;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoPadrao;
        }
    }

    // Modal Nova Categoria
    document.getElementById('formNovaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!idInterclasse) {
            alert("Erro: ID do interclasse não encontrado!");
            return;
        }

        const inputNome = document.getElementById('inputNomeCategoria').value;
        const btnCriar = document.getElementById('btnCriarCategoria');
        
        btnCriar.disabled = true;
        btnCriar.innerHTML = "Criando...";

        const dadosCategoria = {
            id_interclasse: idInterclasse,
            nome_categoria: inputNome.trim()
        };

        try {
            const response = await fetch('../../../api/categorias.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosCategoria)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = `./edicao_categorias.php?id=${idInterclasse}`;
            } else {
                alert("Erro ao criar categoria: " + (result.message || "Tente novamente."));
                btnCriar.disabled = false;
                btnCriar.innerHTML = "Criar";
            }
        } catch (error) {
            console.error("Erro na requisição:", error);
            alert("Aviso: Conexão com API falhou, mas redirecionando para testes de layout.");
            window.location.href = `./edicao_categorias.php?id=${idInterclasse}`;
        }
    });

    window.onload = carregarResumos;
</script>

<?php
require_once '../componentes/footer.php';
?>