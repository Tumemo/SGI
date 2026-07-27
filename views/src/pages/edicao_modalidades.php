<?php
$tituloPagina = 'SGI - Modalidades';
$titulo = 'Modalidades';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Escolha uma modalidade para continuar</p>

    <section id="listaModalidadesMobile" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando modalidades...)</p>
    </section>

    <div class="position-fixed d-flex gap-2" style="bottom: 92px; right: 16px; z-index: 20;">
        <button type="button" class="btn btn-outline-primary d-none" id="btnEditarModalidadeMobile" onclick="abrirModalEditarModalidade()">Editar</button>
        <button type="button" class="btn btn-danger d-none" id="btnExcluirModalidadeMobile" onclick="excluirModalidade()">Excluir</button>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">Adicionar</button>
        <a href="#" class="btn btn-danger disabled" id="btnContinuarMobile" aria-disabled="true">Continuar</a>
    </div>
</main>


<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">

    <div style="border-radius: 12px;">

        <div class="mb-5">
            <a class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;" id="btnVoltarModalidades" href="./dashboard.php">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseModalidades">Interclasse</span>
            </a>
        </div>

        <div class="row g-4" id="listaModalidadesDesktop">
            <p class="text-muted">(Carregando modalidades...)</p>
        </div>

    </div>

    <div class="position-fixed d-flex flex-row align-items-center gap-4 py-3 px-5" style="bottom: 0; right: 0; z-index: 1050; background: transparent;">

        <button type="button" id="btnEditarModalidadeDesktop" class="btn btn-outline-primary fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm d-none" style="border-radius: 8px;" onclick="abrirModalEditarModalidade()">
            <i class="bi bi-pencil-square"></i> Editar
        </button>

        <button type="button" id="btnExcluirModalidadeDesktop" class="btn btn-danger fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm d-none" style="border-radius: 8px;" onclick="excluirModalidade()">
            <i class="bi bi-trash"></i> Excluir
        </button>

        <button type="button" class="btn bg-white fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="color: #ed1c24; border: 2px solid #ed1c24; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i> Adicionar
        </button>

        <a href="#" id="btnContinuarDesktop" class="btn fw-bold px-5 py-2 text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center disabled" style="background-color: #ed1c24; border-radius: 8px;" aria-disabled="true">
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

<div class="modal fade" id="modalEditarModalidade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger">Editar Modalidade</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarModalidade">
                    <div class="mb-3">
                        <label for="editNomeModalidade" class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="editNomeModalidade" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Gênero:</label>
                        <select class="form-select" id="editGeneroModalidade" required>
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" id="editMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="editTipoModalidade" required>
                            <option value="" disabled selected>Carregando tipos...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="editCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando categorias...</option>
                        </select>
                    </div>
                    <div id="caixaMensagemEditarModalidade" class="mt-3"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoModalidade">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const idCategoria = urlParams.get('id_categoria');
    const modo = urlParams.get('modo') || 'view';
    let modalidadeSelecionada = null;
    let modalidadesData = [];

    // TRAVA DE SEGURANÇA e Configuração do Botão "Continuar"
    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclasseModalidades').innerText = dados?.nome_interclasse || 'Interclasse';
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);
        atualizarBotaoContinuar();
        return idInterclasse;
    }

    function atualizarBotaoContinuar() {
        const botaoDesktop = document.getElementById('btnContinuarDesktop');
        const botaoMobile = document.getElementById('btnContinuarMobile');
        const destino = modo === 'view'
            ? `./dashboard.php?id=${idInterclasse}`
            : `./edicao_pontuacao.php?id=${idInterclasse}&modo=create${modalidadeSelecionada ? `&id_modalidade=${modalidadeSelecionada}` : ''}`;
        [botaoDesktop, botaoMobile].forEach((botao) => {
            if (!botao) return;
            botao.href = destino;
            const disabled = !modalidadeSelecionada && modo !== 'view';
            botao.classList.toggle('disabled', disabled);
            botao.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        });

        // Editar / Excluir buttons
        ['btnEditarModalidadeDesktop', 'btnEditarModalidadeMobile',
         'btnExcluirModalidadeDesktop', 'btnExcluirModalidadeMobile'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('d-none', !modalidadeSelecionada);
        });

        const backBtn = document.getElementById('btnVoltarModalidades');
        if (backBtn) {
            backBtn.href = modo === 'view'
                ? `./dashboard.php?id=${idInterclasse}`
                : `./edicao_categorias.php?id=${idInterclasse}&modo=create`;
        }
    }

    // 1. FUNÇÃO: Listar as modalidades já existentes (Cards)
    async function carregarModalidades() {
        const divMobile = document.getElementById('listaModalidadesMobile');
        const divDesktop = document.getElementById('listaModalidadesDesktop');

        try {
            const filtroCategoria = idCategoria ? `&id_categoria=${idCategoria}` : '';
            const response = await axios.get(`../../../api/modalidades.php?x=1${filtroCategoria}`);
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidades = modalidades.filter((item) => String(item.interclasses_id_interclasse) === String(idInterclasse));
            modalidadesData = modalidades;

            if (divMobile) divMobile.innerHTML = '';
            if (divDesktop) divDesktop.innerHTML = '';

            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
                if (divMobile) divMobile.innerHTML = msgVazia;
                if (divDesktop) divDesktop.innerHTML = msgVazia;
                return;
            }

            // Agrupar modalidades por categoria
            const modalidadesPorCategoria = {};
            modalidades.forEach((modalidade) => {
                const categoria = modalidade.nome_categoria || 'Sem Categoria';
                if (!modalidadesPorCategoria[categoria]) {
                    modalidadesPorCategoria[categoria] = [];
                }
                modalidadesPorCategoria[categoria].push(modalidade);
            });

            Object.keys(modalidadesPorCategoria).forEach((categoria, catIndex) => {
                const mods = modalidadesPorCategoria[categoria];

                // Mobile: Seção por categoria
                if (divMobile) {
                    divMobile.innerHTML += `<h5 class="mt-4 mb-3 text-muted">${categoria}</h5>`;
                    mods.forEach((modalidade) => {
                        const destinoDetalhes = `./modalidade_detalhes.php?id=${idInterclasse}&id_modalidade=${modalidade.id_modalidade}`;

                        divMobile.innerHTML += `
                            <button type="button" class="modalidade-opcao bg-white d-flex justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3 w-100" style="max-width: 90%;" data-id="${modalidade.id_modalidade}" data-detalhes="${destinoDetalhes}">
                                <i class="bi bi-trophy fs-4"></i>
                                <div class="text-start px-3 w-100">
                                    <h2 class="m-0 fs-5 text-truncate">${modalidade.nome_modalidade}</h2>
                                </div>
                                <i class="bi bi-check-circle-fill text-danger d-none"></i>
                            </button>`;
                    });
                }

                // Desktop: Seção por categoria
                if (divDesktop) {
                    divDesktop.innerHTML += `<h4 class="mt-4 mb-3 text-muted">${categoria}</h4><div class="row g-4">`;
                    mods.forEach((modalidade) => {
                        const destinoDetalhes = `./modalidade_detalhes.php?id=${idInterclasse}&id_modalidade=${modalidade.id_modalidade}`;

                        divDesktop.innerHTML += `
                            <div class="col-12 col-md-6 col-lg-4">
                                <button type="button" class="modalidade-opcao card border border-light-subtle shadow-sm h-100 py-4 px-4 d-flex flex-row align-items-center justify-content-between transition-hover w-100 text-start bg-white" style="border-radius: 10px;" data-id="${modalidade.id_modalidade}" data-detalhes="${destinoDetalhes}">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-trophy fs-4 text-dark"></i>
                                        <div>
                                            <h5 class="m-0 fw-bold fs-6">${modalidade.nome_modalidade}</h5>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-danger d-none"></i>
                                </button>
                            </div>`;
                    });
                    divDesktop.innerHTML += '</div>';
                }

                if (catIndex === 0 && mods.length > 0) modalidadeSelecionada = Number(mods[0].id_modalidade);
            });

            document.querySelectorAll('.modalidade-opcao').forEach((botao) => {
                botao.addEventListener('click', () => {
                    if (modo === 'view') {
                        window.location.href = botao.dataset.detalhes;
                        return;
                    }
                    modalidadeSelecionada = Number(botao.dataset.id);
                    document.querySelectorAll('.modalidade-opcao').forEach((btn) => {
                        btn.classList.remove('border-danger');
                        const icone = btn.querySelector('.bi-check-circle-fill');
                        if (icone) icone.classList.add('d-none');
                    });
                    botao.classList.add('border-danger');
                    const iconeAtivo = botao.querySelector('.bi-check-circle-fill');
                    if (iconeAtivo) iconeAtivo.classList.remove('d-none');
                    atualizarBotaoContinuar();
                });
            });

            modalidadeSelecionada = null;
            atualizarBotaoContinuar();
        } catch (error) {
            console.error("Erro ao carregar lista:", error);
        }
    }

    // 2. FUNÇÃO: Preencher o Select de TIPOS (Vem da api/tipo_modalidade.php)
    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        const editSelectTipo = document.getElementById('editTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('../../../api/tipoModalidade.php');
            const tipos = response.data;

            selectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            if (editSelectTipo) editSelectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += `<option value="${tipo.id_tipo_modalidade}">${tipo.nome_tipo_modalidade}</option>`;
                if (editSelectTipo) editSelectTipo.innerHTML += `<option value="${tipo.id_tipo_modalidade}">${tipo.nome_tipo_modalidade}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar tipos:", error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 3. FUNÇÃO: Preencher o Select de CATEGORIAS (Vem da api/categorias.php)
    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        const editSelectCat = document.getElementById('editCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const categorias = response.data;

            selectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            if (editSelectCat) editSelectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            categorias.forEach((cat) => {
                const selected = idCategoria && String(idCategoria) === String(cat.id_categoria) ? 'selected' : '';
                selectCat.innerHTML += `<option value="${cat.id_categoria}" ${selected}>${cat.nome_categoria}</option>`;
                if (editSelectCat) editSelectCat.innerHTML += `<option value="${cat.id_categoria}">${cat.nome_categoria}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // Editar Modalidade
    window.abrirModalEditarModalidade = function() {
        if (!modalidadeSelecionada) return;
        const mod = modalidadesData.find(m => m.id_modalidade == modalidadeSelecionada);
        if (!mod) return;

        document.getElementById('editNomeModalidade').value = mod.nome_modalidade || '';
        document.getElementById('editGeneroModalidade').value = mod.genero_modalidade || 'MISTO';
        document.getElementById('editMaxInscritos').value = mod.max_inscrito_modalidade || '';
        document.getElementById('caixaMensagemEditarModalidade').innerHTML = '';

        // Preencher selects de tipo e categoria
        const selectTipo = document.getElementById('editTipoModalidade');
        const selectCat = document.getElementById('editCategoriaModalidade');

        if (mod.tipos_modalidades_id_tipo_modalidade) {
            Array.from(selectTipo.options).forEach(opt => {
                opt.selected = String(opt.value) === String(mod.tipos_modalidades_id_tipo_modalidade);
            });
        }
        if (mod.categorias_id_categoria) {
            Array.from(selectCat.options).forEach(opt => {
                opt.selected = String(opt.value) === String(mod.categorias_id_categoria);
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEditarModalidade'));
        modal.show();
    };

    document.getElementById('formEditarModalidade').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvarEdicaoModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemEditarModalidade');

        const dados = {
            id_modalidade: parseInt(modalidadeSelecionada),
            nome_modalidade: document.getElementById('editNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('editGeneroModalidade').value,
            max_inscrito_modalidade: parseInt(document.getElementById('editMaxInscritos').value) || 0,
            tipos_modalidades_id_tipo_modalidade: document.getElementById('editTipoModalidade').value,
            categorias_id_categoria: document.getElementById('editCategoriaModalidade').value
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = "Salvando...";
            const res = await axios.put('../../../api/modalidades.php', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center fw-bold">Salvo com sucesso!</p>`;
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarModalidade')).hide();
                    caixaMensagem.innerHTML = "";
                    carregarModalidades();
                }, 800);
            }
        } catch (error) {
            const msg = error.response?.data?.message || 'Erro ao salvar.';
            caixaMensagem.innerHTML = `<p class="text-danger text-center fw-bold">${msg}</p>`;
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Salvar";
        }
    });

    // Excluir Modalidade
    window.excluirModalidade = async function() {
        if (!modalidadeSelecionada) return;
        const mod = modalidadesData.find(m => m.id_modalidade == modalidadeSelecionada);
        const nomeMod = mod ? mod.nome_modalidade : 'esta modalidade';

        if (!confirm(`Tem certeza que deseja excluir "${nomeMod}"?`)) return;

        const btnDesktop = document.getElementById('btnExcluirModalidadeDesktop');
        const btnMobile = document.getElementById('btnExcluirModalidadeMobile');

        try {
            if (btnDesktop) btnDesktop.disabled = true;
            if (btnMobile) btnMobile.disabled = true;

            const res = await axios.delete('../../../api/modalidades.php', {
                data: { id_modalidade: parseInt(modalidadeSelecionada) }
            });

            if (res.data.success) {
                modalidadeSelecionada = null;
                carregarModalidades();
            }
        } catch (error) {
            const msg = error.response?.data?.message || 'Erro ao excluir.';
            alert(msg);
        } finally {
            if (btnDesktop) btnDesktop.disabled = false;
            if (btnMobile) btnMobile.disabled = false;
        }
    };

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
                modalidadeSelecionada = Number(res.data.id);
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
    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
        atualizarBotaoContinuar();
        if (modo === 'view') {
            ['btnContinuarDesktop', 'btnContinuarMobile'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.classList.add('d-none');
            });
        }
    });
</script>



<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>