<?php
$titulo = "Categorias";
$textTop = "Categorias";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-secondary text-center my-3" style="font-size: 14px;">Selecione uma categoria para adicionar turmas</p>
    
    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>

    <section class="d-flex gap-4 mt-3 position-fixed translate-middle" style="width: max-content; top: 85%; left: 50%; z-index: 10; cursor: pointer;">
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar Categoria</button>
        <a href="./home.php" id="btnContinuarMobile" class="btn btn-danger">Continuar</a>
    </section>
</main>

<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block container" style="padding-top: 2rem; padding-bottom: 100px;">
    <div class="container-fluid px-0 position-relative">
        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0" style="background-color: #ed1c24; border-radius: 6px;" onclick="window.history.back()">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0">
                <i class="bi bi-bookmark fs-5"></i> Categorias
            </h4>
        </div>

        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>

        <button class="border border-0 bg-danger shadow rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed" style="height: 60px; width: 60px; bottom: 40px; right: 5%; z-index: 10; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
            <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
        </button>
        
        <div class="d-flex justify-content-end mt-5">
             <a href="./home.php" id="btnContinuarDesktop" class="btn btn-danger px-5 py-2 fw-bold rounded-3 shadow-sm">Continuar</a>
        </div>
    </div>
</main>


<div class="modal fade" id="criarTurma" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="inputNomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="inputNomeTurma" placeholder="Turma A" required>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" onchange="mostrarNomeArquivo()">
                        <p class="text-center" style="font-size: 13px;">Adicione aqui o pdf dos alunos da turma criada</p>
                        <label for="arquivoUpload" class="btn btn-light border rounded-circle p-3" style="cursor:pointer;">
                            <i class="bi bi-upload fs-4"></i>
                        </label>
                        <span id="nomeArquivo" class="text-muted mt-2"></span>
                    </div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalCriarCategoria" tabindex="-1" aria-labelledby="modalNovaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalNovaCategoriaLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6 mb-3">Insira o nome da sua nova categoria:</h2>
                <form id="formNovaCategoria">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: Ensino Médio" id="inputNomeCategoriaNova" required>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-5">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarCategoria">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Repassa o ID do Interclasse na URL para o botão de "Continuar"
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    if (idInterclasse) {
        document.getElementById('btnContinuarMobile').href = `./home.php?id=${idInterclasse}`;
        document.getElementById('btnContinuarDesktop').href = `./home.php?id=${idInterclasse}`;
    }

    // Função para buscar e renderizar as categorias
    async function carregarCategorias() {
        const divMobile = document.getElementById('listaCategoriasMobile');
        const divDesktop = document.getElementById('listaCategoriasDesktop');

        try {
            const response = await fetch('../../../api/categorias.php');
            const categorias = await response.json();

            // Limpa o conteúdo de "Carregando..."
            divMobile.innerHTML = '';
            divDesktop.innerHTML = '';

            if (categorias.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma categoria cadastrada ainda.</p>';
                divMobile.innerHTML = msgVazia;
                divDesktop.innerHTML = msgVazia;
                return;
            }

            // Para cada categoria recebida da API, criamos o HTML
            categorias.forEach(categoria => {
                
                // 1. Injetar HTML no Mobile
                divMobile.innerHTML += `
                    <button data-bs-toggle="modal" data-bs-target="#criarTurma" class="bg-white d-flex m-auto justify-content-between align-items-center shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                        <i class="bi bi-trophy fs-1 text-danger"></i>
                        <h2 class="m-0 fs-5 text-truncate px-3 w-100 text-start">${categoria.nome_categoria}</h2>
                        <picture>
                            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                        </picture>
                    </button>
                `;

                // 2. Injetar HTML no Desktop
                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#criarTurma">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                        <div class="fs-5 text-dark">0</div>
                                    </div>
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                        <div class="fs-5 text-dark">0</div>
                                    </div>
                                </div>

                                <button class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;">
                                    Adicionar turmas <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            divMobile.innerHTML = '<p class="text-danger mt-4">Erro ao carregar categorias.</p>';
            divDesktop.innerHTML = '<p class="text-danger mt-4">Erro ao carregar categorias.</p>';
        }
    }

    // Lógica para enviar Nova Categoria para a API
    document.getElementById('formNovaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault(); // Impede a página de recarregar

        const inputNome = document.getElementById('inputNomeCategoriaNova');
        const btnSalvar = document.getElementById('btnSalvarCategoria');
        
        const dados = {
            nome_categoria: inputNome.value.trim()
        };

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = "Salvando...";

        try {
            const response = await fetch('../../../api/categorias.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (result.success) {
                // Limpa o campo
                inputNome.value = "";
                
                // Fecha o modal do Bootstrap via JS
                const modalEl = document.getElementById('modalCriarCategoria');
                const modalObj = bootstrap.Modal.getInstance(modalEl);
                modalObj.hide();

                // Recarrega a lista de categorias na tela
                carregarCategorias();
            } else {
                alert("Erro: " + result.message);
            }
        } catch (error) {
            console.error("Erro ao criar categoria:", error);
            alert("Erro de conexão com o servidor ao criar categoria.");
        } finally {
            // Reativa o botão independentemente do resultado
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    // Função de apoio para o modal de turmas mostrar o nome do arquivo selecionado
    function mostrarNomeArquivo() {
        const inputUpload = document.getElementById('arquivoUpload');
        const displayNome = document.getElementById('nomeArquivo');
        if (inputUpload.files && inputUpload.files.length > 0) {
            displayNome.innerText = inputUpload.files[0].name;
        } else {
            displayNome.innerText = "";
        }
    }

    // Carrega as categorias assim que a página abrir
    window.onload = carregarCategorias;
</script>

<?php
require_once '../componentes/footer.php';
?>