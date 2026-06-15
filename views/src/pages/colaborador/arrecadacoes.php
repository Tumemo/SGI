<?php
$tituloPagina = 'SGI - Colaborador - Arrecadações';
$titulo = 'Arrecadações';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center text-secondary mt-3" style="font-size: 14px;">Itens arrecadados no interclasse</p>
    <div id="listaArrecadacoesMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando arrecadações...)</p>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <button data-bs-toggle="modal" data-bs-target="#modalAdicionarItem" class="btn btn-outline-danger">Adicionar item</button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-4">
            <i class="bi bi-basket fs-5"></i> Arrecadações
        </h4>
        <div id="listaArrecadacoesDesktop">
            <p class="text-muted">(Carregando arrecadações...)</p>
        </div>
        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg mt-4" style="color: #ed1c24; border: 2px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalAdicionarItem">
            <i class="bi bi-plus-circle"></i> Adicionar item
        </button>
    </div>
</main>

<div class="modal fade" id="modalAdicionarItem" tabindex="-1" aria-labelledby="modalAdicionarItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalAdicionarItemLabel">Adicionar Item</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAdicionarItem">
                    <div class="mb-3">
                        <label for="inputNomeItem" class="form-label fw-semibold">Nome do item</label>
                        <input type="text" class="form-control" id="inputNomeItem" placeholder="Ex: Garrafa PET" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputQuantidade" class="form-label fw-semibold">Quantidade</label>
                        <input type="number" class="form-control" id="inputQuantidade" min="1" step="1" placeholder="Ex: 10" required>
                    </div>
                    <div class="mb-3">
                        <label for="selectCategoria" class="form-label fw-semibold">Categoria</label>
                        <select class="form-select" id="selectCategoria" required>
                            <option value="">Selecione uma categoria</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarItem">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let idInterclasse = null;
    let categorias = [];

    async function obterIdInterclasse() {
        const urlParams = new URLSearchParams(window.location.search);
        idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
            if (idInterclasse) {
                window.history.replaceState(null, '', `?id=${idInterclasse}`);
            }
        }
        return idInterclasse;
    }

    async function carregarCategorias() {
        if (!idInterclasse) return;
        try {
            const res = await fetch(`../../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const data = await res.json();
            categorias = Array.isArray(data) ? data : [];
            const select = document.getElementById('selectCategoria');
            select.innerHTML = '<option value="">Selecione uma categoria</option>';
            categorias.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id_categoria;
                opt.textContent = cat.nome_categoria;
                select.appendChild(opt);
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    function agruparPorCategoria(itens) {
        const grupos = {};
        itens.forEach(item => {
            const chave = item.nome_categoria || 'Sem categoria';
            if (!grupos[chave]) grupos[chave] = [];
            grupos[chave].push(item);
        });
        return grupos;
    }

    function renderizarArrecadacoes(itens) {
        const divMobile = document.getElementById('listaArrecadacoesMobile');
        const divDesktop = document.getElementById('listaArrecadacoesDesktop');

        if (!itens.length) {
            const msg = '<p class="text-muted mt-4 text-center w-100">Nenhum item arrecadado ainda.</p>';
            divMobile.innerHTML = msg;
            divDesktop.innerHTML = msg;
            return;
        }

        const grupos = agruparPorCategoria(itens);

        divMobile.innerHTML = '';
        divDesktop.innerHTML = '';

        Object.keys(grupos).forEach(categoria => {
            const items = grupos[categoria];

            divMobile.innerHTML += `
                <div class="bg-white d-flex flex-column m-auto justify-content-between shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                    <h2 class="fs-6 fw-bold text-danger mb-3">${categoria}</h2>
                    ${items.map(item => `
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <span class="fw-medium">${item.nome_item}</span>
                            <span class="badge bg-danger rounded-pill">${item.quantidade}</span>
                        </div>
                    `).join('')}
                </div>
            `;

            divDesktop.innerHTML += `
                <div class="mb-4">
                    <h5 class="fw-bold text-danger mb-2">${categoria}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover bg-white shadow-sm rounded-3 overflow-hidden mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold">Item</th>
                                    <th class="fw-semibold text-center" style="width: 120px;">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => `
                                    <tr>
                                        <td>${item.nome_item}</td>
                                        <td class="text-center">${item.quantidade}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        });
    }

    async function carregarArrecadacoes() {
        if (!idInterclasse) return;
        try {
            const res = await fetch(`../../../../api/arrecadacao.php?id_interclasse=${idInterclasse}`);
            const data = await res.json();
            const itens = Array.isArray(data) ? data : [];
            renderizarArrecadacoes(itens);
        } catch (error) {
            console.error('Erro ao carregar arrecadações:', error);
            document.getElementById('listaArrecadacoesMobile').innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar arrecadações.</p>';
            document.getElementById('listaArrecadacoesDesktop').innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar arrecadações.</p>';
        }
    }

    document.getElementById('formAdicionarItem').addEventListener('submit', async (e) => {
        e.preventDefault();

        const nomeItem = document.getElementById('inputNomeItem').value.trim();
        const quantidade = parseInt(document.getElementById('inputQuantidade').value, 10);
        const idCategoria = document.getElementById('selectCategoria').value;

        if (!nomeItem || !quantidade || !idCategoria || !idInterclasse) {
            alert('Preencha todos os campos.');
            return;
        }

        const btnSalvar = document.getElementById('btnSalvarItem');
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = "Salvando...";

        try {
            const response = await fetch('../../../../api/arrecadacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_interclasse: parseInt(idInterclasse, 10),
                    nome_item: nomeItem,
                    quantidade: quantidade,
                    id_categoria: parseInt(idCategoria, 10)
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                document.getElementById('inputNomeItem').value = '';
                document.getElementById('inputQuantidade').value = '';
                document.getElementById('selectCategoria').value = '';
                const modalEl = document.getElementById('modalAdicionarItem');
                const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalObj.hide();
                carregarArrecadacoes();
            } else {
                alert('Erro: ' + (result.message || 'Não foi possível adicionar o item.'));
            }
        } catch (error) {
            console.error('Erro ao adicionar item:', error);
            alert('Erro de conexão com o servidor ao adicionar item.');
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Adicionar";
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            await obterIdInterclasse();
            if (!idInterclasse) {
                document.getElementById('listaArrecadacoesMobile').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
                document.getElementById('listaArrecadacoesDesktop').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
                return;
            }
            await Promise.all([
                carregarCategorias(),
                carregarArrecadacoes()
            ]);
        } catch (error) {
            console.error(error);
        }
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
