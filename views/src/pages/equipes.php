<?php
$titulo = "Equipes";
$textTop = "Equipes";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom:120px;">
    <div class="container mt-3">
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCriarAlunos">Adicionar alunos</button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">Adicionar equipe</button>
        </div>
        <div id="listaEquipesMobile">
            <p class="text-center text-muted">(Carregando equipes...)</p>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Equipes da turma</h4>
            <div>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCriarAlunos">Adicionar alunos</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCriarEquipe">Adicionar equipe</button>
            </div>
        </div>
        <div class="row g-3" id="listaEquipesDesktop">
            <p class="text-center text-muted">(Carregando equipes...)</p>
        </div>
    </div>
</main>

<!-- modal adicionar equipe -->
<div class="modal fade" id="modalCriarEquipe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Criar nova equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCriarEquipe">
                    <label for="selectModalidadeEquipe" class="form-label">Modalidade</label>
                    <select id="selectModalidadeEquipe" class="form-select mb-3" required>
                        <option value="" selected disabled>Carregando modalidades...</option>
                    </select>
                    <div id="msgEquipe" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEquipe">Criar e adicionar alunos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- modal adicionar alunos -->
<div class="modal fade" id="modalCriarAlunos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Adicionar alunos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAdicionarAlunos" enctype="multipart/form-data">
                    <label for="inputNomeTurmaPdf" class="form-label">Nome da turma</label>
                    <input id="inputNomeTurmaPdf" name="nome_turma" type="text" class="form-control mb-3" placeholder="Nome da turma (ex: 8ºA)" required>
                    <label for="inputPdfTurma" class="form-label">Arquivo PDF</label>
                    <input id="inputPdfTurma" name="pdf" type="file" class="form-control mb-3" accept="application/pdf" required>
                    <div id="msgAdicionarAlunos" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Continuar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let modalidadesDisponiveis = [];
    let equipesDisponiveis = [];

    function atualizarSelectEquipe() {
        // Função auxiliar para atualizar seleção de equipes se necessário
        // Pode ser expandida no futuro
    }

    async function carregarModalidadesParaEquipe(idInterclasse, idCategoria) {
        const select = document.getElementById('selectModalidadeEquipe');
        const filtroCategoria = idCategoria ? `&id_categoria=${idCategoria}` : '';
        try {
            const response = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}${filtroCategoria}`);
            const modalidades = await response.json();
            modalidadesDisponiveis = Array.isArray(modalidades) ? modalidades : [];
            if (!modalidadesDisponiveis.length) {
                select.innerHTML = '<option value="" selected disabled>Nenhuma modalidade disponível</option>';
                return;
            }
            select.innerHTML = '<option value="" selected disabled>Selecione a modalidade</option>';
            modalidadesDisponiveis.forEach((item) => {
                select.innerHTML += `<option value="${item.id_modalidade}">${item.nome_modalidade}</option>`;
            });
        } catch (error) {
            console.error(error);
            select.innerHTML = '<option value="" selected disabled>Erro ao carregar modalidades</option>';
        }
    }

    async function carregarEquipesPagina() {
        const urlParams = new URLSearchParams(window.location.search);
        const idInterclasse = urlParams.get('id');
        const idTurma = urlParams.get('id_turma');
        const idCategoria = urlParams.get('id_categoria');
        const listaMobile = document.getElementById('listaEquipesMobile');
        const listaDesktop = document.getElementById('listaEquipesDesktop');

        if (!idInterclasse || !idTurma) {
            listaMobile.innerHTML = '<p class="text-center text-muted">Turma não selecionada.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-muted">Turma não selecionada.</p>';
            return;
        }

        try {
            const [resTurmas, resEquipes] = await Promise.all([
                fetch(`../../../api/turmas.php?id_turma=${idTurma}&id_interclasse=${idInterclasse}`),
                fetch(`../../../api/equipes.php?id_turma=${idTurma}`)
            ]);
            const turma = (await resTurmas.json())?.[0];
            const equipes = await resEquipes.json();

            if (window.SGIInterclasse && typeof window.SGIInterclasse.updatePageTitle === 'function') {
                window.SGIInterclasse.updatePageTitle(turma?.nome_turma || 'Equipes');
            }
            await carregarModalidadesParaEquipe(idInterclasse, idCategoria);

            if (!Array.isArray(equipes) || equipes.length === 0) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma equipe nesta turma.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma equipe nesta turma.</p>';
                equipesDisponiveis = [];
                atualizarSelectEquipe();
                return;
            }

            equipesDisponiveis = equipes;
            atualizarSelectEquipe();

            listaMobile.innerHTML = equipes.map((equipe) => `
                <div class="bg-white rounded-3 shadow-sm p-3 mb-3 d-flex justify-content-between">
                    <div>
                        <span class="fw-semibold d-block">${equipe.nome_modalidade || `Equipe #${equipe.id_equipe}`}</span>
                        <span class="text-muted small">${equipe.nome_turma || ''}</span>
                    </div>
                    <a class="btn btn-sm btn-outline-danger" href="./equipe_alunos.php?id=${idInterclasse}&id_turma=${idTurma}&id_equipe=${equipe.id_equipe}">Alunos</a>
                </div>
            `).join('');

            listaDesktop.innerHTML = equipes.map((equipe) => `
                <div class="col-md-6 col-lg-4">
                    <div class="bg-white rounded-3 shadow-sm p-4 h-100">
                        <h6 class="fw-bold mb-2">${equipe.nome_modalidade || `Equipe #${equipe.id_equipe}`}</h6>
                        <p class="text-muted mb-3">${equipe.nome_turma || ''}</p>
                        <a class="btn btn-outline-danger btn-sm" href="./equipe_alunos.php?id=${idInterclasse}&id_turma=${idTurma}&id_equipe=${equipe.id_equipe}">Adicionar alunos</a>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error(error);
            listaMobile.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar equipes.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar equipes.</p>';
        }
    }

    document.getElementById('formAdicionarAlunos').addEventListener('submit', async (event) => {
        event.preventDefault();
        const nomeTurma = document.getElementById('inputNomeTurmaPdf').value.trim();
        const arquivoPdf = document.getElementById('inputPdfTurma').files?.[0];
        const btn = event.target.querySelector('button[type="submit"]');
        const msg = document.getElementById('msgAdicionarAlunos');
        msg.innerHTML = '';

        if (!nomeTurma || !arquivoPdf) {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">Preencha o nome da turma e selecione um PDF.</p>';
            return;
        }
        const nomeArquivo = arquivoPdf.name || '';
        const ext = nomeArquivo.split('.').pop()?.toLowerCase();
        if (ext !== 'pdf') {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">O arquivo deve ser um PDF.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerText = 'Carregando...';
            const formData = new FormData();
            formData.append('nome_turma', nomeTurma);
            formData.append('pdf', arquivoPdf);

            const response = await fetch('../upload_turma_pdf.php', {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();

            console.log('Resposta upload:', result, 'Status:', response.status);

            if (!response.ok && response.status !== 200) {
                throw new Error('Falha na requisição ao enviar o PDF (Status: ' + response.status + ')');
            }
            if (!result.success) {
                const detail = result.log ? ` - ${result.log}` : '';
                throw new Error((result.message || 'Falha ao enviar PDF') + detail);
            }
            msg.innerHTML = `<p class="text-success fw-bold mb-0">${result.message}</p>`;
            setTimeout(() => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCriarAlunos'));
                modal.hide();
                document.getElementById('inputNomeTurmaPdf').value = '';
                document.getElementById('inputPdfTurma').value = '';
                carregarEquipesPagina(); // Recarrega a lista de equipes
            }, 1500);
        } catch (error) {
            console.error('Erro ao enviar PDF:', error);
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Continuar';
        }
    });

    document.getElementById('formCriarEquipe').addEventListener('submit', async (event) => {
        event.preventDefault();
        const urlParams = new URLSearchParams(window.location.search);
        const idInterclasse = urlParams.get('id');
        const idTurma = Number(urlParams.get('id_turma'));
        const idModalidade = Number(document.getElementById('selectModalidadeEquipe').value);
        const btn = document.getElementById('btnSalvarEquipe');
        const msg = document.getElementById('msgEquipe');

        try {
            btn.disabled = true;
            btn.innerText = 'Criando...';
            msg.innerHTML = '';
            const response = await fetch('../../../api/equipes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'criar_equipe',
                    modalidades_id_modalidade: idModalidade,
                    turmas_id_turma: idTurma,
                    status_equipe: "1"
                })
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao criar equipe.');
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Equipe criada!</p>';
            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCriarEquipe')).hide();
                window.location.href = `./equipe_alunos.php?id=${idInterclasse}&id_turma=${idTurma}&id_equipe=${result.id_equipe}`;
            }, 600);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Criar e adicionar alunos';
        }
    });

    window.addEventListener('load', carregarEquipesPagina);
</script>

<?php
require_once '../componentes/footer.php';
?>
