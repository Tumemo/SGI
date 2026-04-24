<?php 
$titulo = "Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="position-relative" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Escolha uma modalidade para editar</p>
    
    <section id="listaModalidades" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando modalidades...)</p>
    </section>

    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed" style="height: 60px; width: 60px; bottom: 40px; right: 5%; z-index: 10; cursor: pointer; top: 80%;" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </button>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Modalidade</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaModalidade">
                    <div class="mb-3">
                        <label for="inputNomeModalidade" class="form-label">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="inputNomeModalidade" placeholder="Ex: Futsal Masculino" required>
                    </div>

                    <div id="caixaMensagemModalidade" class="mt-3"></div>

                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarModalidade">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // 1. Pegar o ID do Interclasse que veio pela URL
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // 2. Função para buscar e renderizar as modalidades (agora usando Axios)
    async function carregarModalidades() {
        const divLista = document.getElementById('listaModalidades');

        if (!idInterclasse) {
            divLista.innerHTML = '<p class="text-danger mt-4 text-center">ID do Interclasse não encontrado na URL.</p>';
            return;
        }

        try {
            const response = await axios.get(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const modalidades = response.data;

            divLista.innerHTML = '';

            // Se não houver nada cadastrado
            if (!modalidades || modalidades.length === 0 || modalidades.success === false) {
                divLista.innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada para este interclasse.</p>';
                return;
            }

            // Para cada modalidade recebida, cria o HTML e joga na tela
            modalidades.forEach(modalidade => {
                const linkEdicao = `./modalidades.php?id_interclasse=${idInterclasse}&id_modalidade=${modalidade.id_modalidade}`;

                divLista.innerHTML += `
                    <a href="${linkEdicao}" class="text-decoration-none text-black w-100 d-flex justify-content-center">
                        <div class="bg-white d-flex justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                            <i class="bi bi-trophy fs-2 text-danger"></i>
                            <h2 class="m-0 fs-4 text-truncate px-3 w-100 text-start">${modalidade.nome_modalidade}</h2>
                            <picture>
                                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                            </picture>
                        </div>
                    </a>
                `;
            });

        } catch (error) {
            console.error("Erro ao carregar modalidades:", error);
            divLista.innerHTML = '<p class="text-danger mt-4 text-center">Erro de conexão ao carregar as modalidades.</p>';
        }
    }

    // 3. Função para Criar uma Nova Modalidade
    document.getElementById('formNovaModalidade').addEventListener('submit', async (e) => {
        e.preventDefault(); 

        const inputNome = document.getElementById('inputNomeModalidade');
        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');
        
        // Verifica se temos o ID do interclasse antes de tentar salvar
        if (!idInterclasse) {
            caixaMensagem.innerHTML = `<p class="text-danger text-center mb-0 fw-bold">Erro: ID do Interclasse ausente.</p>`;
            return;
        }

        // Monta o objeto para enviar à API
        const dados = {
            nome_modalidade: inputNome.value.trim(),
            id_interclasse: idInterclasse
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = "Salvando...";
            caixaMensagem.innerHTML = "";

            const res = await axios.post('../../../api/modalidades.php', dados);

            if (res.data && res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center mb-0 fw-bold">Modalidade criada com sucesso!</p>`;
                
                inputNome.value = ""; // Limpa o input
                carregarModalidades(); // Atualiza a lista por trás do modal

                // Aguarda 1 segundo e fecha o modal
                setTimeout(() => {
                    const modalEl = document.getElementById('exampleModal');
                    const modalObj = bootstrap.Modal.getInstance(modalEl);
                    modalObj.hide();
                    caixaMensagem.innerHTML = ""; 
                }, 1000);

            } else {
                throw new Error(res.data ? res.data.message : "Erro desconhecido ao salvar.");
            }
        } catch (error) {
            console.error("Erro ao criar modalidade:", error);
            const msgErro = error.response?.data?.message || error.message || "Erro de conexão com o servidor.";
            caixaMensagem.innerHTML = `<p class="text-danger text-center mb-0 fw-bold">Erro: ${msgErro}</p>`;
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    // Inicia o carregamento assim que a página abrir
    window.onload = carregarModalidades;
</script>

<?php 
require_once '../componentes/footer.php';
?>