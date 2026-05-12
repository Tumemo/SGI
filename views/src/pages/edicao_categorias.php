<?php
$titulo = "Categorias";
$textTop = "Categorias";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .categoria-item--selected {
        border: 2px solid #ed1c24 !important;
        box-shadow: 0 0.25rem 0.75rem rgba(237, 28, 36, 0.12) !important;
    }
</style>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-secondary text-center my-3" style="font-size: 14px;">Selecione uma categoria para adicionar turmas</p>
    
    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>

    <section class="d-flex gap-3 mt-3 position-fixed translate-middle flex-wrap justify-content-center" style="width: max-content; max-width: 96vw; top: 85%; left: 50%; z-index: 10;">
        <div id="wrapBtnTurmaMobile" class="d-none">
            <button type="button" id="btnAdicionarTurmaMobile" data-bs-toggle="modal" data-bs-target="#criarTurma" class="btn btn-outline-danger" disabled>Adicionar Turma</button>
        </div>
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar Categoria</button>
        <a href="#" id="btnContinuarMobile" class="btn btn-danger">Continuar</a>
    </section>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <div class="mb-5">
            <a href="./home.php" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;" data-back-link="true">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0">
                <i class="bi bi-bookmark fs-5"></i> Categorias
            </h4>
        </div>

        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>

        <div class="position-fixed d-flex flex-row gap-3" style="bottom: 40px; right: 5%; z-index: 1050;">
            <div id="wrapBtnTurmaDesktop" class="d-none">
                <button type="button" id="btnAdicionarTurmaDesktop" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg" style="color: #ed1c24; border: 2px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#criarTurma" disabled>
                    <i class="bi bi-mortarboard"></i> Adicionar Turma
                </button>
            </div>
            
            <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg" style="color: #ed1c24; border: 2px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
                <i class="bi bi-plus-circle"></i> Adicionar
            </button>
            
            <a href="#" id="btnContinuarDesktop" class="btn fw-semibold rounded-3 px-5 py-2 text-white text-decoration-none shadow-lg d-flex align-items-center justify-content-center" style="background-color: #ed1c24; border: 2px solid #ed1c24;">
                Continuar
            </a>
            
        </div>
    </div>
</main>

<!-- modal de adicionar nova turma -->
<div class="modal fade" id="criarTurma" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurmaCategoria">
                    <div class="mb-3">
                        <label for="inputNomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="inputNomeTurma" placeholder="Ex: 1º Médio A" required>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p class="text-center" style="font-size: 13px;">Adicione aqui o pdf dos alunos da turma criada</p>
                        <label for="arquivoUpload" class="btn btn-light border rounded-circle p-3" style="cursor:pointer;">
                            <i class="bi bi-upload fs-4"></i>
                        </label>
                        <span id="nomeArquivo" class="text-muted mt-2"></span>
                    </div>
                    <div id="msgNovaTurmaCategoria" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnCriarTurmaCategoria">Criar e enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- modal de criar nova categoria -->
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
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';
    let categoriaSelecionada = null;

    function atualizarAcoesCategoria() {
        const btnTurmaMobile = document.getElementById('btnAdicionarTurmaMobile');
        const btnTurmaDesktop = document.getElementById('btnAdicionarTurmaDesktop');
        const wrapMob = document.getElementById('wrapBtnTurmaMobile');
        const wrapDesk = document.getElementById('wrapBtnTurmaDesktop');
        const temSel = Boolean(categoriaSelecionada);
        if (wrapMob) wrapMob.classList.toggle('d-none', !temSel);
        if (wrapDesk) wrapDesk.classList.toggle('d-none', !temSel);
        if (btnTurmaMobile) btnTurmaMobile.disabled = !categoriaSelecionada;
        if (btnTurmaDesktop) btnTurmaDesktop.disabled = !categoriaSelecionada;

        const rota = modo === 'view' ? './dashboard.php' : './edicao_modalidades.php';
        const sufixoCategoria = categoriaSelecionada ? `&id_categoria=${categoriaSelecionada}` : '';
        document.getElementById('btnContinuarMobile').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
        document.getElementById('btnContinuarDesktop').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
    }

    function selecionarCategoria(idCategoria, el) {
        categoriaSelecionada = Number(idCategoria);
        document.querySelectorAll('.categoria-item').forEach((item) => {
            item.classList.remove('categoria-item--selected');
        });
        el.classList.add('categoria-item--selected');
        atualizarAcoesCategoria();
    }

    if (!idInterclasse) {
        document.getElementById('listaCategoriasMobile').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse selecionado.</p>';
        document.getElementById('listaCategoriasDesktop').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse selecionado.</p>';
        document.getElementById('btnContinuarMobile').href = './dashboard.php';
        document.getElementById('btnContinuarDesktop').href = './dashboard.php';
    } else {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                document.getElementById('nomeInterclasseCategoria').innerText = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);
        atualizarAcoesCategoria();
    }

    async function carregarCategorias() {
        const divMobile = document.getElementById('listaCategoriasMobile');
        const divDesktop = document.getElementById('listaCategoriasDesktop');

        try {
            const response = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            if (!response.ok) throw new Error(`Erro na API: ${response.status}`);
            const categorias = await response.json();

            divMobile.innerHTML = '';
            divDesktop.innerHTML = '';

            if (!categorias.length) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma categoria cadastrada ainda.</p>';
                divMobile.innerHTML = msgVazia;
                divDesktop.innerHTML = msgVazia;
                return;
            }

            categorias.forEach((categoria) => {
                divMobile.innerHTML += `
                    <button type="button" class="categoria-item bg-white d-flex m-auto justify-content-between align-items-center shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;" data-id="${categoria.id_categoria}">
                        <i class="bi bi-trophy fs-3"></i>
                        <h2 class="m-0 fs-5 text-truncate px-3 w-100 text-start">${categoria.nome_categoria}</h2>
                        <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
                    </button>
                `;

                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="categoria-item card border-0 shadow-sm h-100 p-4" style="border-radius: 12px; cursor: pointer;" data-id="${categoria.id_categoria}">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                        <div class="fs-5 text-dark">0</div>
                                    </div>
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                        <div class="fs-5 text-dark">0</div>
                                    </div>
                                </div>
                                <a class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;" href="./edicao_turmas.php?id=${idInterclasse}&id_categoria=${categoria.id_categoria}">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;

            });

            document.querySelectorAll('.categoria-item').forEach((btn) => {
                btn.addEventListener('click', (ev) => {
                    if (ev.target.closest('a[href]')) return;
                    ev.preventDefault();
                    selecionarCategoria(btn.dataset.id, btn);
                });
            });
            categoriaSelecionada = null;
            atualizarAcoesCategoria();
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            divMobile.innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar categorias.</p>';
            divDesktop.innerHTML = '<p class="text-danger mt-4">Erro ao carregar categorias.</p>';
        }
    }

    // Lógica para enviar Nova Categoria para a API
    document.getElementById('formNovaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();

        const inputNome = document.getElementById('inputNomeCategoriaNova');
        const btnSalvar = document.getElementById('btnSalvarCategoria');
        
        // Injetando o id_interclasse no payload
        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_categoria: inputNome.value.trim(),
            status_categoria: 1 //Definido como ativo por padrao
        };

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = "Salvando...";

        try {
            const response = await fetch('../../../api/categorias.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                inputNome.value = "";
                
                // Fechando o modal corretamente no Bootstrap 5
                const modalEl = document.getElementById('modalCriarCategoria');
                const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalObj.hide();

                // Recarrega a tela para exibir a categoria recém-criada
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

    document.getElementById('formNovaTurmaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!categoriaSelecionada) {
            alert("Selecione uma categoria antes de criar a turma.");
            return;
        }

        const btn = document.getElementById('btnCriarTurmaCategoria');
        const msg = document.getElementById('msgNovaTurmaCategoria');
        const nomeTurma = document.getElementById('inputNomeTurma').value.trim();
        const pdf = document.getElementById('arquivoUpload').files?.[0];

        try {
            btn.disabled = true;
            btn.innerText = "Criando...";
            msg.innerHTML = '';

            const payloadTurma = {
                interclasses_id_interclasse: Number(idInterclasse),
                categorias_id_categoria: Number(categoriaSelecionada),
                nome_turma: nomeTurma,
                status_turma: "1"
            };

            const resTurma = await fetch('../../../api/turmas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payloadTurma)
            });
            const turmaCriada = await resTurma.json();
            if (!resTurma.ok || !turmaCriada.success) {
                throw new Error(turmaCriada.message || 'Falha ao criar turma.');
            }

            if (pdf) {
                const formData = new FormData();
                formData.append('pdf', pdf);
                formData.append('nome_turma', nomeTurma);
                await fetch('./upload_turma_pdf.php', { method: 'POST', body: formData });
            }

            msg.innerHTML = '<p class="text-success fw-bold mb-0">Turma criada com sucesso!</p>';
            document.getElementById('inputNomeTurma').value = '';
            document.getElementById('arquivoUpload').value = '';
            document.getElementById('nomeArquivo').innerText = '';

            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('criarTurma')).hide();
                msg.innerHTML = '';
            }, 900);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message || 'Erro ao criar turma.'}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = "Criar e enviar";
        }
    });

    function mostrarNomeArquivo() {
        const inputUpload = document.getElementById('arquivoUpload');
        const displayNome = document.getElementById('nomeArquivo');
        if (inputUpload.files && inputUpload.files.length > 0) {
            displayNome.innerText = inputUpload.files[0].name;
        } else {
            displayNome.innerText = "";
        }
    }

    if (idInterclasse) {
        window.onload = carregarCategorias;
    }
</script>

<?php
require_once '../componentes/footer.php';
?>