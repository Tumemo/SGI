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
    <div id="caixaListar" class="pb-5 mb-2 mt-2">
        <p class="text-center text-muted mt-3">(Carregando...)</p>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-flex main-desktop-layout">
    <section class="mt-4 container">

        <h1 class="fs-2">Edições do interclasse</h1>

        <button class="btn btn-outline-danger d-flex gap-2 mt-3 mb-4 align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i>Criar Nova Edição
        </button>

        <div>
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-5 fw-medium px-2">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <div class="mt-2" id="listaDesktop">
                 <p class="text-center text-muted mt-5">(Carregando...)</p>
            </div>
        </div>

    </section>
</main>


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
                        <button type="submit" class="btn btn-danger" id="btnCriar">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const estadoInterclasses = {
        lista: []
    };

    function anoInterclasse(item) {
        return item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
    }

    function statusAtivo(item) {
        return String(item.status_interclasse) === '1';
    }

    async function atualizarStatusInterclasse(idInterclasse, ativo) {
        const body = new FormData();
        body.append('status_interclasse', ativo ? '1' : '0');
        const response = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, {
            method: 'POST',
            body
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível atualizar o status.');
        }
        return data;
    }

    async function ativarComExclusividade(idParaAtivar) {
        const ativos = estadoInterclasses.lista.filter(item => statusAtivo(item));

        for (const interclasse of ativos) {
            if (String(interclasse.id_interclasse) !== String(idParaAtivar)) {
                await atualizarStatusInterclasse(interclasse.id_interclasse, false);
            }
        }
        await atualizarStatusInterclasse(idParaAtivar, true);
    }

    function cardClassStatus(ativo) {
        return ativo ? '' : 'opacity-75';
    }

    // 1. Função para Listar (Mobile e Desktop)
    async function listarInterclasses() {
        const listarMobile = document.getElementById('caixaListar');
        const listarDesktop = document.getElementById('listaDesktop');
        
        try {
            const res = await axios.get("../../../api/interclasse.php?regulamento=true");
            
            if (!res.data || res.data.length === 0) {
                const msgVazia = `<p class="text-center text-muted mt-5">Nenhum interclasse encontrado</p>`;
                listarMobile.innerHTML = msgVazia;
                listarDesktop.innerHTML = msgVazia;
                return;
            }

            // Variáveis para acumular o HTML
            let htmlMobile = '';
            let htmlDesktop = '';

            estadoInterclasses.lista = res.data;
            res.data.forEach((item) => {
                const anoStr = anoInterclasse(item);
                const ativo = statusAtivo(item);
                const statusBadge = ativo
                    ? '<span class="bg-danger rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Ativo</span>'
                    : '<span class="bg-secondary rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Inativo</span>';

                // Montar HTML Mobile
                htmlMobile += `
                    <a href="./dashboard.php?id=${item.id_interclasse}" class="text-decoration-none text-dark">
                        <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1 ${cardClassStatus(ativo)}" style="width: 90%;">
                            <div>
                                <h2 class="m-0 fs-4">${item.nome_interclasse}</h2>
                                <p class="text-secondary m-0">${anoStr}</p>
                                <label class="form-check form-switch mt-2 mb-0">
                                  <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                                  <span class="small text-muted">Interclasse ativo</span>
                                </label>
                            </div>
                            <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                        </div>
                    </a>
                `;

                // Montar HTML Desktop (com onclick para redirecionar enviando o ID)
                htmlDesktop += `
                    <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-3 align-items-center px-2 border border-1 ${cardClassStatus(ativo)}" 
                         style="cursor: pointer; transition: background-color 0.2s ease;" 
                         onmouseover="this.style.backgroundColor='#f8f9fa'" 
                         onmouseout="this.style.backgroundColor='#ffffff'"
                         onclick="window.location.href='./dashboard.php?id=${item.id_interclasse}'">
                        
                        <div class="col-4 fw-semibold text-dark text-truncate">${item.nome_interclasse}</div>
                        <div class="col-4 text-center text-secondary">${anoStr}</div>
                        <div class="col-4 text-center">
                            ${statusBadge}
                            <div class="form-check form-switch d-flex justify-content-center mt-2">
                                <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                            </div>
                        </div>
                    </div>
                `;
            });

            // Injetar na tela
            listarMobile.innerHTML = htmlMobile;
            listarDesktop.innerHTML = htmlDesktop;
            registrarEventosStatus();

        } catch (error) {
            console.error(error);
            const msgErro = `<p class="mt-3 text-center text-danger">Erro ao carregar dados da API!</p>`;
            listarMobile.innerHTML = msgErro;
            listarDesktop.innerHTML = msgErro;
        }
    }

    function registrarEventosStatus() {
        document.querySelectorAll('.status-switch').forEach((input) => {
            input.addEventListener('click', (event) => event.stopPropagation());
            input.addEventListener('change', async (event) => {
                const id = event.target.getAttribute('data-id');
                const checked = event.target.checked;
                event.target.disabled = true;
                try {
                    if (checked) {
                        await ativarComExclusividade(id);
                    } else {
                        await atualizarStatusInterclasse(id, false);
                    }
                    await listarInterclasses();
                } catch (error) {
                    alert(error.message || 'Erro ao atualizar status do interclasse.');
                    await listarInterclasses();
                } finally {
                    event.target.disabled = false;
                }
            });
        });
    }

    // 2. Lógica para Criar Novo Interclasse
    const formulario = document.getElementById("formulario");
    const caixaMensagem = document.getElementById('caixaMensagem');

    formulario.addEventListener("submit", async (event) => {
        event.preventDefault(); 

        const nome = document.getElementById('nomeNovaEdicao').value;
        const ano = document.getElementById('anoNovaEdicao').value;

        // Formata o ano para o padrão YYYY-MM-DD
        const dataAtual = new Date();
        const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
        const dia = String(dataAtual.getDate()).padStart(2, '0');
        const dataFormatada = `${ano}-${mes}-${dia}`;

        const novoInterclasse = {
            nome_interclasse: nome.trim(),
            ano_interclasse: dataFormatada
        };

        try {
            document.getElementById('btnCriar').disabled = true;
            document.getElementById('btnCriar').innerText = "Criando...";

            const res = await axios.post("../../../api/interclasse.php", novoInterclasse);

            if (res.data && res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center mt-3 mb-0 fw-bold">Criado com sucesso!</p>`;
                const idCriado = res.data.id;
                await atualizarStatusInterclasse(idCriado, false);

                formulario.reset();
                listarInterclasses();

                setTimeout(() => {
                    window.location.href = `./edicao_categorias.php?id=${idCriado}`;
                }, 800); // Dei um tempo ligeiramente maior para o usuário ler o "Criado com sucesso!"
            } else {
                throw new Error(res.data ? res.data.message : "Erro interno no servidor ao salvar.");
            }
        } catch (error) {
            console.error("ERRO DETALHADO:", error);
            const msgErro = error.response?.data?.message || error.message || "Erro desconhecido";
            caixaMensagem.innerHTML = `<p class="text-danger text-center mt-3 mb-0 fw-bold">Erro: ${msgErro}</p>`;
        } finally {
            document.getElementById('btnCriar').disabled = false;
            document.getElementById('btnCriar').innerText = "Criar";
        }
    });

    // Inicia a listagem ao carregar a página
    window.onload = listarInterclasses;
</script>

<style>
    .status-switch.form-check-input:checked {
        background-color: #ed1c24;
        border-color: #ed1c24;
    }
    .status-switch.form-check-input:focus {
        border-color: #ed1c24;
        box-shadow: 0 0 0 0.2rem rgba(237, 28, 36, 0.25);
    }
</style>

<?php
require_once '../componentes/footer.php';
?>