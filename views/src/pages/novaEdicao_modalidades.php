<?php
$titulo = "Nova Edição - Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class=" position-relative d-md-none" style="margin-bottom: 120px;">
    <section class="m-auto d-flex flex-column gap-3 mt-5" style="width: 90%;">
        <section class="rounded bg-white">
            <form id="formMobile" class="input-group my-3 m-auto" style="width: 90%;">
                <input type="text" id="inputModalidadeMobile" placeholder="Nome da nova modalidade" class="form-control" style="font-size: 0.7rem;" required>
                <button type="submit" class="btn btn-danger" id="btnAdicionarMobile">Adicionar</button>
            </form>
            <section class="my-3 m-auto" style="width: 90%;" id="listarModalidadesMobile">
                </section>
        </section>
    </section>
    <a href="./edicao_regulamentos.php" id="btnContinuarMobile" class="btn btn-danger position-absolute position-fixed translate-middle" style="width: max-content;  top: 87%; left: 50%; z-index: 10; cursor: pointer;">Continuar</a>
</main>

<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block" style="padding-top: 2rem;">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-4">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse
            </button>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trophy fs-5"></i> Modalidades
                </h5>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2" style="color: #ed1c24; border: 1px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <i class="bi bi-plus-circle"></i> Adicionar
                    </button>

                    <a href="./edicao_regulamentos.php" id="btnContinuarDesktop" class="text-decoration-none">
                        <button class="btn fw-semibold rounded-3 px-4 py-2 text-white w-100 w-sm-auto" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                            Continuar
                        </button>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 mt-2" id="listarModalidadesDesktop">
            </div>
    </div>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4 p-2">

            <div class="modal-header border-0 pb-0 justify-content-center">
                <h5 class="modal-title fw-bold text-center w-100" style="color: #ed1c24;">
                    CRIAR NOVA MODALIDADE
                </h5>
            </div>

            <form id="formDesktop">
                <div class="modal-body pt-3 pb-3">
                    <p class="text-dark mb-2 fw-medium" style="font-size: 0.95rem;">
                        Insira o nome da sua nova modalidade:
                    </p>
                    <input
                        type="text"
                        class="form-control form-control-lg shadow-sm rounded-3 text-secondary"
                        placeholder="EX: Futsal"
                        style="font-size: 0.95rem; border: 1px solid #dee2e6;"
                        id="inputModalidadeDesktop" required>
                    
                    <div id="msgModalidadeDesktop"></div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2">
                    <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2" data-bs-dismiss="modal" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;" id="btnAdicionarDesktop">
                        Criar
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    // 1. Pegar o ID do Interclasse que veio pela URL
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // 2. Repassar o ID para a página de Regulamentos
    if (idInterclasse) {
        document.getElementById('btnContinuarMobile').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
        document.getElementById('btnContinuarDesktop').href = `./edicao_regulamentos.php?id=${idInterclasse}`;
    }

    // 3. Função para listar as modalidades
    async function ListarModalidades() {
        try {
            const response = await fetch('../../../api/modalidades.php');
            const data = await response.json();

            const containerDesktop = document.getElementById('listarModalidadesDesktop');
            const containerMobile = document.getElementById('listarModalidadesMobile');
            
            containerDesktop.innerHTML = '';
            containerMobile.innerHTML = '';

            if (data && data.length > 0) {
                data.forEach(modalidade => {
                    // O nome que a API devolve é nome_modalidade
                    const nome = modalidade.nome_modalidade;

                    containerDesktop.innerHTML += `
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-trophy fs-5 text-dark"></i>
                                    <span class="fw-bold text-dark fs-6">${nome}</span>
                                </div>
                                <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                            </div>
                        </div>
                    `;

                    containerMobile.innerHTML += `
                        <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 ps-4 w-100 rounded m-auto">${nome}</h2>
                    `;
                });
            } else {
                containerDesktop.innerHTML = '<p class="text-muted w-100 mt-3">Nenhuma modalidade adicionada ainda.</p>';
            }
        } catch (error) {
            console.error("Erro ao listar modalidades:", error);
        }
    }

    // 4. Lógica para criar a modalidade mandando os dados EXATOS que o PHP pede
    async function criarModalidade(nomeDigitado, btnSubmit) {
        // Objeto formatado com as chaves exatas exigidas pela sua API
        const novaModalidade = {
            nome_modalidade: nomeDigitado.trim(),
            genero_modalidade: 'M', // Valor padrão obrigatório
            max_inscrito_modalidade: 10, // Opcional, enviando valor padrão
            tipos_modalidades_id_tipo_modalidade: 1, // ID padrão para evitar erro
            categorias_id_categoria: 1 // ID padrão para evitar erro
        };

        try {
            btnSubmit.disabled = true;

            const response = await fetch('../../../api/modalidades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(novaModalidade)
            });

            const result = await response.json();

            if (result.success) {
                ListarModalidades(); // Atualiza a lista na tela
                return true;
            } else {
                alert("Erro ao criar modalidade: " + (result.message || "Desconhecido"));
                return false;
            }
        } catch (error) {
            console.error("Erro na requisição:", error);
            alert("Erro ao conectar com o servidor.");
            return false;
        } finally {
            btnSubmit.disabled = false;
        }
    }

    // 5. Submit do Mobile
    document.getElementById('formMobile').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('inputModalidadeMobile');
        const btn = document.getElementById('btnAdicionarMobile');
        
        const sucesso = await criarModalidade(input.value, btn);
        if (sucesso) input.value = ''; 
    });

    // 6. Submit do Desktop
    document.getElementById('formDesktop').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('inputModalidadeDesktop');
        const btn = document.getElementById('btnAdicionarDesktop');
        const msg = document.getElementById('msgModalidadeDesktop');
        
        const sucesso = await criarModalidade(input.value, btn);
        if (sucesso) {
            input.value = ''; 
            msg.innerHTML = `<p class="text-success text-center mt-2 mb-0">Adicionado!</p>`;
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal'));
                modal.hide();
                msg.innerHTML = '';
            }, 1000);
        }
    });

    // Inicia a listagem
    window.onload = ListarModalidades;
</script>

<?php
require_once '../componentes/footer.php';
?>