<?php
$tituloPagina = 'SGI - Colaborador - Modalidades';
$titulo = 'Modalidades';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$paginaAtiva = 'modalidades';
$cssExtra = '.transition-hover { transition: all 0.2s ease-in-out; } .transition-hover:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important; }';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <section id="listaModalidadesMobile" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando modalidades...)</p>
    </section>

    <div class="position-fixed" style="bottom: 92px; right: 16px; z-index: 20;">
        <button class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 56px; height: 56px; background-color: #ed1c24; border: none;" data-bs-toggle="modal" data-bs-target="#modalCriarModalidade">
            <i class="bi bi-plus-lg text-white fs-4"></i>
        </button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div style="border-radius: 12px;">
        <div class="mb-5">
            <a class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;" href="./dashboard.php">
                <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
            </a>

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

        <button type="button" class="btn bg-white fw-bold px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="color: #ed1c24; border: 2px solid #ed1c24; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalCriarModalidade">
            <i class="bi bi-plus-circle"></i> Adicionar
        </button>
    </div>
</main>

<div class="modal fade" id="modalCriarModalidade" tabindex="-1" aria-labelledby="modalCriarModalidadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalCriarModalidadeLabel">Criar nova Modalidade</h1>
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

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = null;

    async function carregarModalidades() {
        const divMobile = document.getElementById('listaModalidadesMobile');
        const divDesktop = document.getElementById('listaModalidadesDesktop');

        try {
            const response = await axios.get('../../../../api/modalidades.php?x=1');
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidades = modalidades.filter((item) => String(item.interclasses_id_interclasse) === String(idInterclasse));

            if (divMobile) divMobile.innerHTML = '';
            if (divDesktop) divDesktop.innerHTML = '';

            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
                if (divMobile) divMobile.innerHTML = msgVazia;
                if (divDesktop) divDesktop.innerHTML = msgVazia;
                return;
            }

            const modalidadesPorCategoria = {};
            modalidades.forEach((modalidade) => {
                const categoria = modalidade.nome_categoria || 'Sem Categoria';
                if (!modalidadesPorCategoria[categoria]) {
                    modalidadesPorCategoria[categoria] = [];
                }
                modalidadesPorCategoria[categoria].push(modalidade);
            });

            Object.keys(modalidadesPorCategoria).forEach((categoria) => {
                const mods = modalidadesPorCategoria[categoria];

                if (divMobile) {
                    divMobile.innerHTML += '<h5 class="mt-4 mb-3 text-muted px-3">' + esc(categoria) + '</h5>';
                    mods.forEach((modalidade) => {
                        divMobile.innerHTML +=
                            '<div class="bg-white d-flex align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3 w-100" style="max-width: 90%;">' +
                                '<i class="bi bi-trophy fs-4"></i>' +
                                '<div class="text-start px-3 w-100">' +
                                    '<h2 class="m-0 fs-5 text-truncate">' + esc(modalidade.nome_modalidade) + '</h2>' +
                                '</div>' +
                            '</div>';
                    });
                }

                if (divDesktop) {
                    divDesktop.innerHTML += '<h4 class="mt-4 mb-3 text-muted">' + esc(categoria) + '</h4><div class="row g-4">';
                    mods.forEach((modalidade) => {
                        divDesktop.innerHTML +=
                            '<div class="col-12 col-md-6 col-lg-4">' +
                                '<div class="card border border-light-subtle shadow-sm h-100 py-4 px-4 d-flex flex-row align-items-center" style="border-radius: 10px;">' +
                                    '<div class="d-flex align-items-center gap-3">' +
                                        '<i class="bi bi-trophy fs-4 text-dark"></i>' +
                                        '<div>' +
                                            '<h5 class="m-0 fw-bold fs-6">' + esc(modalidade.nome_modalidade) + '</h5>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>';
                    });
                    divDesktop.innerHTML += '</div>';
                }
            });
        } catch (error) {
            console.error('Erro ao carregar lista:', error);
        }
    }

    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('../../../../api/tipoModalidade.php');
            const tipos = response.data;

            selectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += '<option value="' + esc(tipo.id_tipo_modalidade) + '">' + esc(tipo.nome_tipo_modalidade) + '</option>';
            });
        } catch (error) {
            console.error('Erro ao carregar tipos:', error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get('../../../../api/categorias.php?id_interclasse=' + idInterclasse);
            const categorias = response.data;

            selectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            categorias.forEach((cat) => {
                selectCat.innerHTML += '<option value="' + esc(cat.id_categoria) + '">' + esc(cat.nome_categoria) + '</option>';
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    document.getElementById('formNovaModalidade').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');

        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_modalidade: document.getElementById('inputNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('inputGeneroModalidade').value,
            tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
            categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = 'Salvando...';
            const res = await axios.post('../../../../api/modalidades.php', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = '<p class="text-success text-center fw-bold">Criada com sucesso!</p>';
                document.getElementById('formNovaModalidade').reset();
                carregarModalidades();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCriarModalidade')).hide();
                    caixaMensagem.innerHTML = '';
                }, 1000);
            }
        } catch (error) {
            caixaMensagem.innerHTML = '<p class="text-danger text-center fw-bold">Erro ao salvar.</p>';
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = 'Criar';
        }
    });

    window.addEventListener('load', async () => {
        idInterclasse = await window.SGIInterclasse.resolveId();
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo encontrado.');
            window.location.href = 'home.php';
            return;
        }
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
