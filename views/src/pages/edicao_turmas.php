<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none">
    <p class="text-secondary text-center my-3">Editar detalhes turmas</p>
    
    <a href="#" id="linkAlunosMobile" class="text-decoration-none text-black">
        <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3  border border-1" style="width: 90%;">
            <h2 class="m-0 fs-3">1 º ano EM</h2>
            <picture>
                <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
            </picture>
        </div>
    </a>
    
    <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3  border border-1" style="width: 90%;">
        <h2 class="m-0 fs-3">2 º ano EM</h2>
        <picture>
            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
        </picture>
    </div>
    <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3  border border-1" style="width: 90%;">
        <h2 class="m-0 fs-3">3 º ano EM</h2>
        <picture>
            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
        </picture>
    </div>

</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        
        <div class="row g-4 mx-0">
            <div class="col-md-4 px-0 px-md-2">
                <div class="bg-white rounded-4 shadow-sm overflow-hidden border-0">
                    <div class="p-3 d-flex align-items-center gap-2" style="background-color: #ed1c24; color: white;">
                        <i class="bi bi-plus-circle fs-5" style="cursor: pointer;" title="Adicionar Categoria"></i>
                        <h6 class="mb-0 fw-bold fs-5">Categorias</h6>
                    </div>
                    
                    <div id="listaCategorias" class="list-group list-group-flush" style="max-height: 60vh; overflow-y: auto;">
                        <p class="text-muted p-3 mb-0 text-center">Carregando categorias...</p>
                    </div>
                </div>
            </div>

            <div class="col-md-8 px-0 px-md-2 d-flex flex-column gap-3">
                
                <div class="bg-white rounded-3 shadow-sm p-2 d-flex align-items-center">
                    <i class="bi bi-search text-muted ms-3"></i>
                    <input type="text" id="inputBuscaTurma" class="form-control border-0 shadow-none bg-transparent" placeholder="Buscar turma">
                    <button class="btn fw-bold px-4 text-nowrap" style="color: #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalCriarTurma">
                        + Adicionar
                    </button>
                </div>

                <div id="listaTurmas" class="d-flex flex-column gap-3 pe-2" style="max-height: 60vh; overflow-y: auto;">
                    <div class="text-center mt-5">
                        <p class="text-muted fs-5">Selecione uma categoria ao lado para ver as turmas.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="position-fixed" style="bottom: 40px; right: 5%; z-index: 1050;">
        <a href="#" id="btnContinuar" class="btn fw-semibold rounded-3 px-5 py-2 text-white text-decoration-none shadow-lg d-flex align-items-center justify-content-center" style="background-color: #ed1c24; border: 2px solid #ed1c24;">
            Continuar
        </a>
    </div>

    <div class="modal fade" id="modalCriarTurma" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 p-2">
                <div class="modal-header border-0 pb-0 justify-content-center">
                    <h5 class="modal-title fw-bold text-center w-100" style="color: #ed1c24;">
                        ADICIONAR TURMA
                    </h5>
                </div>
                <form id="formTurma">
                    <div class="modal-body pt-3 pb-3">
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Nome da turma:</label>
                            <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary" placeholder="Ex: 9º Ano A" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputNomeTurma" required>
                        </div>
                        <div class="mb-3 d-flex flex-column align-items-center gap-2">
                            <input type="file" id="arquivoPdfTurma" class="d-none" accept=".pdf" onchange="mostrarNomePdfTurma()">
                            <p class="text-center text-muted mb-0" style="font-size: 13px;">Envie o PDF da lista de alunos (opcional)</p>
                            <label for="arquivoPdfTurma" class="btn btn-light border rounded-circle p-3 mb-0" style="cursor:pointer;">
                                <i class="bi bi-upload fs-4"></i>
                            </label>
                            <span id="nomePdfTurma" class="text-muted small"></span>
                        </div>
                        <div id="msgTurma" class="mt-2 text-center"></div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2">
                        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2" data-bs-dismiss="modal" style="color: #ed1c24; border: 1px solid #ed1c24;">
                            Cancelar
                        </button>
                        <button type="submit" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;" id="btnSalvarTurma">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    // 1. TRAVA DE SEGURANÇA: Captura e valida o ID do Interclasse
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const idCategoriaUrl = urlParams.get('id_categoria');

    if (!idInterclasse) {
        alert("Erro: Nenhum interclasse selecionado! Você será redirecionado.");
        window.location.href = "home.php"; // Redireciona para o início caso tentem acessar direto
    } else {
        // Repassa o ID para os botões e links
        document.getElementById('btnContinuar').href = `./edicao_modalidades.php?id=${idInterclasse}`;
        
        // Se a versão mobile for usada e o link existir, repassa o ID também
        const linkAlunos = document.getElementById('linkAlunosMobile');
        if (linkAlunos) linkAlunos.href = `./edicao_alunos.php?id=${idInterclasse}`;
    }

    let categoriaSelecionadaId = null;
    let todasTurmasAtuais = []; 

    // 2. Carregar Categorias
    async function carregarCategorias() {
        try {
            const response = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const categorias = await response.json();
            const container = document.getElementById('listaCategorias');
            container.innerHTML = '';

            if (categorias && categorias.length > 0) {
                categorias.forEach(cat => {
                    const btn = document.createElement('button');
                    btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-4 border-bottom border-0 fs-6 fw-medium text-secondary';
                    btn.style.cursor = 'pointer';
                    btn.innerHTML = `${cat.nome_categoria} <i class="bi bi-chevron-right text-muted"></i>`;
                    
                    btn.onclick = () => {
                        document.querySelectorAll('#listaCategorias button').forEach(b => {
                            b.classList.remove('bg-light', 'text-dark', 'fw-bold');
                            b.classList.add('text-secondary');
                        });
                        btn.classList.add('bg-light', 'text-dark', 'fw-bold');
                        btn.classList.remove('text-secondary');
                        
                        categoriaSelecionadaId = cat.id_categoria;
                        carregarTurmas(categoriaSelecionadaId);
                    };

                    container.appendChild(btn);
                    if (idCategoriaUrl && String(idCategoriaUrl) === String(cat.id_categoria)) {
                        btn.click();
                    }
                });
            } else {
                container.innerHTML = '<p class="text-muted p-3 text-center mb-0">Nenhuma categoria encontrada.</p>';
            }
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
        }
    }

    // 3. Carregar Turmas 
    async function carregarTurmas(idCategoria) {
        try {
            const response = await fetch(`../../../api/turmas.php?id_categoria=${idCategoria}&id_interclasse=${idInterclasse}`);
            const turmas = await response.json();
            todasTurmasAtuais = turmas; 
            renderizarTurmas(turmas);
        } catch (error) {
            console.error("Erro ao carregar turmas:", error);
        }
    }

    // Função para desenhar as turmas na tela
    function renderizarTurmas(turmas) {
        const container = document.getElementById('listaTurmas');
        container.innerHTML = '';

        if (turmas && turmas.length > 0) {
            turmas.forEach(turma => {
                container.innerHTML += `
                    <a href="./equipes.php?id=${idInterclasse}&id_categoria=${categoriaSelecionadaId || ''}&id_turma=${turma.id_turma}" class="text-decoration-none">
                        <div class="bg-white rounded-3 shadow-sm p-4 d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark fs-5">${turma.nome_turma}</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </a>
                `;
            });
        } else {
            container.innerHTML = `
                <div class="text-center mt-5">
                    <p class="text-muted fs-5">Nenhuma turma adicionada nesta categoria.</p>
                </div>
            `;
        }
    }

    // Filtro de Busca Front-end
    document.getElementById('inputBuscaTurma').addEventListener('input', (e) => {
        const termo = e.target.value.toLowerCase();
        const turmasFiltradas = todasTurmasAtuais.filter(t => 
            t.nome_turma.toLowerCase().includes(termo)
        );
        renderizarTurmas(turmasFiltradas);
    });

    // 4. Adicionar Nova Turma (Submit do Modal)
    document.getElementById('formTurma').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!categoriaSelecionadaId) {
            alert("Por favor, selecione uma categoria na lista ao lado primeiro!");
            const modalEl = document.getElementById('modalCriarTurma');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            return;
        }

        const btnSalvar = document.getElementById('btnSalvarTurma');
        const inputNome = document.getElementById('inputNomeTurma');
        const msg = document.getElementById('msgTurma');

        // Já estava correto: usando a variável convertida lá de cima
        const dadosTurma = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            categorias_id_categoria: categoriaSelecionadaId,
            nome_turma: inputNome.value.trim(),
            status_turma: "1"
        };

        try {
            btnSalvar.disabled = true;

            const response = await fetch('../../../api/turmas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosTurma)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                msg.innerHTML = `<p class="text-success fw-bold mt-2 mb-0">Turma Adicionada!</p>`;
                const nomeTurma = inputNome.value.trim();
                inputNome.value = '';
                const pdf = document.getElementById('arquivoPdfTurma').files?.[0];
                if (pdf) {
                    const formData = new FormData();
                    formData.append('pdf', pdf);
                    formData.append('nome_turma', nomeTurma);
                    try {
                        await fetch('./upload_turma_pdf.php', { method: 'POST', body: formData });
                    } catch (_) { /* upload opcional */ }
                }
                document.getElementById('arquivoPdfTurma').value = '';
                document.getElementById('nomePdfTurma').textContent = '';

                carregarTurmas(categoriaSelecionadaId);

                setTimeout(() => {
                    const modalEl = document.getElementById('modalCriarTurma');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    msg.innerHTML = '';
                }, 1000);
            } else {
                alert("Erro ao criar turma: " + (result.message || "Erro desconhecido."));
            }
        } catch (error) {
            console.error("Erro na requisição:", error);
            alert("Erro de conexão.");
        } finally {
            btnSalvar.disabled = false;
        }
    });

    // Inicia a tela somente se tiver o ID
    if (idInterclasse) {
        window.onload = carregarCategorias;
    }

    function mostrarNomePdfTurma() {
        const inp = document.getElementById('arquivoPdfTurma');
        const out = document.getElementById('nomePdfTurma');
        if (inp.files && inp.files.length > 0) {
            out.textContent = inp.files[0].name;
        } else {
            out.textContent = '';
        }
    }
</script>

<?php
require_once '../componentes/footer.php';
?>