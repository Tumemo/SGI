<?php
$tituloPagina = 'SGI - Colaborador - Categorias';
$titulo = 'Categorias';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center text-secondary mt-3" style="font-size: 14px;">Categorias do interclasse</p>
    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar categoria</button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-4">
            <i class="bi bi-bookmark fs-5"></i> Categorias
        </h4>
        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>
        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg mt-4" style="color: #ed1c24; border: 2px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
            <i class="bi bi-plus-circle"></i> Adicionar categoria
        </button>
    </div>
</main>

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
    async function carregarCategorias() {
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
            if (idInterclasse) {
                window.history.replaceState(null, '', `?id=${idInterclasse}`);
            }
        }
        if (!idInterclasse) {
            document.getElementById('listaCategoriasMobile').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('listaCategoriasDesktop').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            return;
        }

        const divMobile = document.getElementById('listaCategoriasMobile');
        const divDesktop = document.getElementById('listaCategoriasDesktop');

        try {
            const [categoriasRes, turmasRes] = await Promise.all([
                fetch(`../../../../api/categorias.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../../api/turmas.php?id_interclasse=${idInterclasse}`).then(r => r.json())
            ]);

            const categorias = Array.isArray(categoriasRes) ? categoriasRes : [];
            const turmas = Array.isArray(turmasRes) ? turmasRes : [];

            const qtdTurmas = {};
            turmas.forEach(t => {
                const catId = Number(t.categorias_id_categoria);
                if (catId) qtdTurmas[catId] = (qtdTurmas[catId] || 0) + 1;
            });

            divMobile.innerHTML = '';
            divDesktop.innerHTML = '';

            if (!categorias.length) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma categoria cadastrada ainda.</p>';
                divMobile.innerHTML = msgVazia;
                divDesktop.innerHTML = msgVazia;
                return;
            }

            categorias.forEach((categoria) => {
                const cId = Number(categoria.id_categoria);
                const count = qtdTurmas[cId] || 0;

                divMobile.innerHTML += `
                    <div class="bg-white d-flex flex-column m-auto justify-content-between shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-trophy fs-3 me-3"></i>
                            <h2 class="m-0 fs-5 text-truncate">${categoria.nome_categoria}</h2>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">${count} turma(s)</span>
                            <a href="./turmas.php?id=${idInterclasse}&id_categoria=${cId}" class="btn btn-danger btn-sm">Ver detalhes</a>
                        </div>
                    </div>
                `;

                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                <div class="rounded-3 p-2 px-3 mb-4 border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                    <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">TURMAS</div>
                                    <div class="fs-5 text-dark">${count}</div>
                                </div>
                                <a class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;" href="./turmas.php?id=${idInterclasse}&id_categoria=${cId}">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            divMobile.innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar categorias.</p>';
            divDesktop.innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar categorias.</p>';
        }
    }

    document.getElementById('formNovaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();

        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo disponível.");
            return;
        }

        const inputNome = document.getElementById('inputNomeCategoriaNova');
        const btnSalvar = document.getElementById('btnSalvarCategoria');
        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_categoria: inputNome.value.trim(),
            status_categoria: 1
        };

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = "Salvando...";

        try {
            const response = await fetch('../../../../api/categorias.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                inputNome.value = "";
                const modalEl = document.getElementById('modalCriarCategoria');
                const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalObj.hide();
                carregarCategorias();
            } else {
                alert("Erro: " + (result.message || "Não foi possível criar a categoria."));
            }
        } catch (error) {
            console.error("Erro ao criar categoria:", error);
            alert("Erro de conexão com o servidor ao criar categoria.");
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    document.addEventListener('DOMContentLoaded', () => carregarCategorias().catch(console.error));
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
