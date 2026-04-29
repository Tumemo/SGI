<?php
$titulo = "Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Escolha uma modalidade para editar</p>

    <section id="listaModalidadesMobile" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando modalidades...)</p>
    </section>

    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed" style="height: 60px; width: 60px; bottom: 40px; right: 5%; z-index: 10; cursor: pointer; top: 80%;" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </button>
</main>


<!-- main desktop -->
<main class="container d-none d-md-block">

    <div style="border-radius: 12px; padding: 40px;">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm" style="background-color: #ed1c24; border-radius: 6px;" onclick="window.history.back()">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0 fs-5">
                <i class="bi bi-trophy fs-4 text-dark"></i> Modalidades
            </h4>
        </div>

        <div class="row g-4" id="listaModalidadesDesktop">
            <p class="text-muted">(Carregando modalidades...)</p>
        </div>

    </div>

    <div class="position-fixed d-flex flex-row align-items-center gap-4 py-3 px-5" style="bottom: 0; right: 0; z-index: 1050; background: transparent;">

        <span class="text-muted small fw-medium">Não tem a modalidade que você quer?</span>

        <button type="button" class="btn bg-white fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="color: #ed1c24; border: 2px solid #ed1c24; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i> Adicionar
        </button>

        <a href="./edicao_pontuacao.php" id="btnContinuarDesktop" class="btn fw-bold px-5 py-2 text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center" style="background-color: #ed1c24; border-radius: 8px;">
            Continuar
        </a>

    </div>
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
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
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
                            <option value="" disabled selected>Carregando tipos...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="inputCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando categorias...</option>
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
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // TRAVA DE SEGURANÇA e Configuração do Botão "Continuar"
    if (!idInterclasse) {
        alert("Erro: Nenhum interclasse selecionado! Você será redirecionado.");
        window.location.href = "home.php";
    } else {
        // Redirecionamento para o próximo passo no Desktop
        const btnCont = document.getElementById('btnContinuarDesktop');
        if(btnCont) btnCont.href = `./categorias.php?id=${idInterclasse}`;
    }

    // 1. FUNÇÃO: Listar as modalidades já existentes (Cards)
    async function carregarModalidades() {
        const divMobile = document.getElementById('listaModalidadesMobile');
        const divDesktop = document.getElementById('listaModalidadesDesktop');

        try {
            const response = await axios.get(`../../../api/modalidades.php?interclasses_id_interclasse=${idInterclasse}`);
            let modalidades = response.data.data || response.data;

            if (divMobile) divMobile.innerHTML = '';
            if (divDesktop) divDesktop.innerHTML = '';

            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
                if (divMobile) divMobile.innerHTML = msgVazia;
                if (divDesktop) divDesktop.innerHTML = msgVazia;
                return;
            }

            modalidades.forEach(modalidade => {
                const linkEdicao = `./modalidades.php?id=${idInterclasse}&id_modalidade=${modalidade.id_modalidade}`;

                // Render Mobile
                if (divMobile) {
                    divMobile.innerHTML += `
                        <a href="${linkEdicao}" class="text-decoration-none text-black w-100 d-flex justify-content-center">
                            <div class="bg-white d-flex justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                                <i class="bi bi-trophy fs-2"></i>
                                <h2 class="m-0 fs-4 text-truncate px-3 w-100 text-start">${modalidade.nome_modalidade}</h2>
                                <img src="../../public/icons/arrow-right.svg" alt="Seta">
                            </div>
                        </a>`;
                }

                // Render Desktop
                if (divDesktop) {
                    divDesktop.innerHTML += `
                        <div class="col-12 col-md-6 col-lg-4">
                            <a href="${linkEdicao}" class="text-decoration-none text-dark">
                                <div class="card border border-light-subtle shadow-sm h-100 py-4 px-4 d-flex flex-row align-items-center justify-content-between transition-hover" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-trophy fs-4 text-dark"></i>
                                        <h5 class="m-0 fw-bold fs-6">${modalidade.nome_modalidade}</h5>
                                    </div>
                                    <i class="bi bi-chevron-right text-secondary"></i>
                                </div>
                            </a>
                        </div>`;
                }
            });
        } catch (error) {
            console.error("Erro ao carregar lista:", error);
        }
    }

    // 2. FUNÇÃO: Preencher o Select de TIPOS (Vem da api/tipo_modalidade.php)
    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('../../../api/tipoModalidade.php');
            const tipos = response.data;

            selectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += `<option value="${tipo.id_tipo_modalidade}">${tipo.nome_tipo_modalidade}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar tipos:", error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 3. FUNÇÃO: Preencher o Select de CATEGORIAS (Vem da api/categorias.php)
    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get('../../../api/categorias.php');
            const categorias = response.data;

            selectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            categorias.forEach(cat => {
                selectCat.innerHTML += `<option value="${cat.id_categoria}">${cat.nome_categoria}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 4. EVENTO: Enviar Formulário de Criação
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
            const res = await axios.post('../../../api/modalidades.php', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center fw-bold">Criada com sucesso!</p>`;
                document.getElementById('formNovaModalidade').reset();
                carregarModalidades();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('exampleModal')).hide();
                    caixaMensagem.innerHTML = "";
                }, 1000);
            }
        } catch (error) {
            caixaMensagem.innerHTML = `<p class="text-danger text-center fw-bold">Erro ao salvar.</p>`;
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    // 5. INICIALIZAÇÃO: Onde a mágica acontece
    window.onload = async () => {
        // Executa todas as buscas ao mesmo tempo
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
    };
</script>

<style>
    /* Efeito sutil para os cards no desktop quando passa o mouse */
    .transition-hover {
        transition: all 0.2s ease-in-out;
    }

    .transition-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }
</style>

<?php
require_once '../componentes/footer.php';
?>