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
                        <label for="inputNomeModalidade" class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="inputNomeModalidade" placeholder="Ex: Futsal" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Gênero:</label>
                        <select class="form-select" id="inputGeneroModalidade" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="M">Masculino (M)</option>
                            <option value="F">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 12" id="inputMaxInscritos" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="inputTipoModalidade" required>
                            <option value="" disabled selected>Selecione um tipo...</option>
                            <option value="1">Esporte de Quadra</option>
                            <option value="2">E-sports</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="inputCategoriaModalidade" required>
                            <option value="" disabled selected>Selecione uma categoria...</option>
                            <option value="1">Ensino Médio</option>
                            <option value="2">Ensino Fundamental</option>
                        </select>
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

    // TRAVA DE SEGURANÇA
    if (!idInterclasse) {
        alert("Erro: Nenhum interclasse selecionado! Você será redirecionado.");
        window.location.href = "home.php";
    }

    // 2. Função para buscar e renderizar as modalidades
    async function carregarModalidades() {
        const divLista = document.getElementById('listaModalidades');

        try {
            // CORREÇÃO 1: Ajustado o nome do parâmetro para 'interclasses_id_interclasse' igual ao do seu banco/POST
            const response = await axios.get(`../../../api/modalidades.php?interclasses_id_interclasse=${idInterclasse}`);
            
            // CORREÇÃO 2: Garantir que pegamos o array, independente se a API retornou direto ou dentro de uma chave 'data'
            let modalidades = response.data.data || response.data;

            divLista.innerHTML = '';

            // Se não houver nada cadastrado ou não for um array
            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                divLista.innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada para este interclasse.</p>';
                return;
            }

            // Para cada modalidade recebida, cria o HTML e joga na tela
            modalidades.forEach(modalidade => {
                const linkEdicao = `./modalidades.php?id=${idInterclasse}&id_modalidade=${modalidade.id_modalidade}`;

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
            console.error("Detalhes do erro no Axios:", error);
            
            // CORREÇÃO 3: Mostra o erro real na tela para facilitar nosso debug
            const msgErro = error.response?.data?.message || error.message || "Erro desconhecido";
            divLista.innerHTML = `<p class="text-danger mt-4 text-center px-3"><strong>Erro ao carregar:</strong> ${msgErro}</p>`;
        }
    }

    // 3. Função para Criar uma Nova Modalidade
    document.getElementById('formNovaModalidade').addEventListener('submit', async (e) => {
        e.preventDefault(); 

        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');
        
        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_modalidade: document.getElementById('inputNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('inputGeneroModalidade').value,
            max_inscrito_modalidade: parseInt(document.getElementById('inputMaxInscritos').value) || 0,
            tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
            categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = "Salvando...";
            caixaMensagem.innerHTML = "";

            const res = await axios.post('../../../api/modalidades.php', dados);

            if (res.data && res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center mb-0 fw-bold">Modalidade criada com sucesso!</p>`;
                
                document.getElementById('formNovaModalidade').reset(); 
                carregarModalidades(); 

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

    if (idInterclasse) {
        window.onload = carregarModalidades;
    }
</script>

<?php 
require_once '../componentes/footer.php';
?>