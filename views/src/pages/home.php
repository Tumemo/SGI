<?php
$titulo = "Home";
$textTop = "";
$btnVoltar = false;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class=" d-md-none">
    <button class="mx-4 btn btn-danger d-flex gap-2 mt-3 align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus-circle"></i>Criar Nova Edição
    </button>
    <div id="caixaListar" class="pb-5 mb-2"></div>


    <!-- Dado estatico caso nescessario -->

    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3" style="width: 80%;">
        <a href="./edicao.php" class="text-decoration-none text-dark">
            <div>
                <h2 class="m-0">Interclasse ???</h2>
                <p class="text-secondary m-0">(Em Andamento)</p>
            </div>
        </a>
        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
    </div>
</main>



<!-- main desktop -->
<main class="d-none d-md-flex">

    <!-- conteudo do home -->
    <section class="mt-4 container">

        <h1 class="fs-2">Edições do interclasse</h1>

        <button class=" btn btn-outline-danger d-flex gap-2 mt-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i>Criar Nova Edição
        </button>

        <!-- table -->
        <div>
            <!-- thead -->
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-4 1875rem">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <!-- tbody -->
            <div class="mt-2">
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>

            </div>
        </div>

    </section>
</main>



<!-- modal criar nova edição  -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Edição</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6">Insira o nome da sua nova edição:</h2>
                <form id="formulario">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: interclasse 2026" id="nomeNovaEdicao" required>
                    </div>
                    <div class="mt-4">
                        <label for="anoNovaEdicao" class="form-label fs-6">Ano</label>
                        <input type="number" class="form-control" placeholder="Ex: 2026" id="anoNovaEdicao" value="2026" required>
                    </div>

                    <div id="caixaMensagem"></div>

                    <div class="d-flex justify-content-center gap-2 mt-5 pt-5">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <!-- <button type="submit" class="btn btn-danger" id="btnCriar">Criar</button> -->
                        <a href="./novaEdicao_modalidades.php" class="btn btn-danger" id="criarNovaEdicao">Criar</a>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>



<!-- script da pagina -->

<script>
    // 1. Função para Listar (Mantive sua lógica, apenas limpei)
    async function listarInterclasses() {
        const listar = document.getElementById('caixaListar');
        try {
            const res = await axios.get("../../../api/interclasse.php");
            if (!res.data || res.data.length === 0) {
                listar.innerHTML = `<p class="text-center">Nenhum interclasse encontrado</p>`;
                return;
            }

            listar.innerHTML = res.data.map((item) => `
                <a href="./edicao.php?id=${item.id_interclasse}" class="text-decoration-none text-dark">
                    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1" style="width: 90%;">
                        <div>
                            <h2 class="m-0 fs-3">${item.nome_interclasse}</h2>
                            <p class="text-secondary m-0">${item.ano_interclasse}</p>
                        </div>
                        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                    </div>
                </a>
            `).join('');
        } catch (error) {
            console.error(error);
            listar.innerHTML = `<p class="mt-3 text-center text-danger">Erro ao carregar dados!</p>`;
        }
    }

    // 2. Lógica para Criar Novo Interclasse
    const formulario = document.getElementById("formulario");
    const caixaMensagem = document.getElementById('caixaMensagem');

    formulario.addEventListener("submit", async (event) => {
        event.preventDefault(); // Impede a página de recarregar

        const nome = document.getElementById('nomeNovaEdicao').value;
        const ano = document.getElementById('anoNovaEdicao').value;

        // Monta o objeto conforme sua API espera (JSON)
        const novoInterclasse = {
            nome_interclasse: nome.trim(),
            ano_interclasse: ano
        };

        try {
            // Desativa o botão para evitar cliques duplos
            document.getElementById('btnCriar').disabled = true;

            const res = await axios.post("../../../api/interclasse.php", novoInterclasse);

            if (res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center mt-2">Criado com sucesso!</p>`;

                // Limpa o formulário
                formulario.reset();

                // Atualiza a lista automaticamente
                listarInterclasses();

                // Opcional: Fecha o modal após 1.5 segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal'));
                    modal.hide();
                    caixaMensagem.innerHTML = '';
                }, 1500);

            } else {
                throw new Error(res.data.message || "Erro desconhecido");
            }
        } catch (error) {
            console.error("ERRO DETALHADO:", error.response ? error.response.data : error.message);
            caixaMensagem.innerHTML = `<p class="text-danger text-center mt-2">Erro: ${error.response?.data?.message || error.message}</p>`;
        } finally {
            document.getElementById('btnCriar').disabled = false;
        }
    });

    // Inicia a listagem ao carregar a página
    window.onload = listarInterclasses;
</script>

<?php
require_once '../componentes/footer.php';
?>