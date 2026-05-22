<?php
$titulo = "Adicionar Alunos";
$textTop = "Inscrições";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <a href="#" class="btn btn-outline-danger w-100 mb-3" id="btnVoltarEquipesMobile">Voltar</a>
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-danger flex-fill" data-bs-toggle="modal" data-bs-target="#modalImportarPdf">Importar PDF</button>
        </div>
        <input type="text" id="buscaAlunoMobile" class="form-control mb-3" placeholder="Buscar aluno">
        <div id="listaAlunosMobile"><p class="text-muted text-center">(Carregando alunos...)</p></div>
        <button id="btnSalvarAlunosMobile" class="btn btn-danger w-100 mt-3">Salvar alterações</button>
        <div id="msgAlunosMobile" class="text-center mt-2"></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Adicionar alunos à equipe</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalImportarPdf">Importar PDF</button>
                <a href="./equipes.php" id="btnVoltarEquipesDesktop" class="btn btn-outline-danger">Voltar</a>
            </div>
        </div>
        <input type="text" id="buscaAlunoDesktop" class="form-control mb-3" placeholder="Buscar aluno">
        <div id="listaAlunosDesktop"><p class="text-muted text-center">(Carregando alunos...)</p></div>
        <div class="d-flex justify-content-end mt-3">
            <button id="btnSalvarAlunosDesktop" class="btn btn-danger">Salvar alterações</button>
        </div>
        <div id="msgAlunosDesktop" class="text-center mt-2"></div>
    </div>
</main>

<!-- Modal Importar PDF -->
<div class="modal fade" id="modalImportarPdf" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Importar alunos via PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formImportarPdf" enctype="multipart/form-data">
                    <label for="inputNomeTurmaPdf" class="form-label">Nome da turma</label>
                    <input id="inputNomeTurmaPdf" name="nome_turma" type="text" class="form-control mb-3" placeholder="Nome da turma (ex: 8ºA)" required>
                    <label for="inputPdfTurma" class="form-label">Arquivo PDF</label>
                    <input id="inputPdfTurma" name="pdf" type="file" class="form-control mb-3" accept="application/pdf" required>
                    <div id="msgImportarPdf" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnEnviarPdf">Enviar PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let alunos = [];
    let alunosNaEquipe = [];

    function cardAluno(aluno, mobile = false) {
        const estaNaEquipe = alunosNaEquipe.some(a => a.id_usuario === aluno.id_usuario);
        return `
            <label class="bg-white rounded-3 shadow-sm p-3 mb-2 d-flex justify-content-between align-items-center aluno-item">
                <div>
                    <strong>${aluno.nome_usuario}</strong>
                    <div class="text-muted small">${aluno.matricula_usuario}</div>
                </div>
                <input class="form-check-input aluno-check" type="checkbox" value="${aluno.id_usuario}" ${estaNaEquipe ? 'checked' : ''}>
            </label>
        `;
    }

    function renderizar(lista) {
        const mobile = document.getElementById('listaAlunosMobile');
        const desktop = document.getElementById('listaAlunosDesktop');
        if (!lista.length) {
            mobile.innerHTML = '<p class="text-muted text-center">Nenhum aluno encontrado.</p>';
            desktop.innerHTML = '<p class="text-muted text-center">Nenhum aluno encontrado.</p>';
            return;
        }
        mobile.innerHTML = lista.map((aluno) => cardAluno(aluno, true)).join('');
        desktop.innerHTML = lista.map((aluno) => cardAluno(aluno)).join('');
    }

    function filtrar(termo) {
        const t = termo.trim().toLowerCase();
        const filtrados = alunos.filter((aluno) => {
            return String(aluno.nome_usuario || '').toLowerCase().includes(t) || String(aluno.matricula_usuario || '').toLowerCase().includes(t);
        });
        renderizar(filtrados);
    }

    async function carregar() {
        const params = new URLSearchParams(window.location.search);
        const idInterclasse = params.get('id');
        const idTurma = params.get('id_turma');
        const idEquipe = params.get('id_equipe');
        const idCategoria = params.get('id_categoria');
        const voltar = `./equipes.php?id=${idInterclasse}&id_turma=${idTurma}${idCategoria ? `&id_categoria=${idCategoria}` : ''}`;
        document.getElementById('btnVoltarEquipesDesktop').href = voltar;
        const vm = document.getElementById('btnVoltarEquipesMobile');
        if (vm) vm.href = voltar;

        try {
            const resEquipe = await fetch(`../../../api/equipes.php?id_equipe=${idEquipe}`);
            const rawEq = await resEquipe.json();
            alunosNaEquipe = Array.isArray(rawEq) ? rawEq : [];

            const res = await fetch(`../../../api/usuarios.php?acao=listar_por_turma&id_turma=${idTurma}`);
            const data = await res.json();
            alunos = (data && data.usuarios) ? data.usuarios : (Array.isArray(data) ? data : []);
            renderizar(alunos);
        } catch (error) {
            console.error(error);
            document.getElementById('listaAlunosMobile').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
            document.getElementById('listaAlunosDesktop').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        }

        const salvar = async (botao, msg) => {
            const checks = Array.from(document.querySelectorAll('.aluno-check:checked'));
            const ids = checks.map((item) => Number(item.value)).filter(Boolean);
            if (!ids.length) {
                msg.innerHTML = '<p class="text-danger fw-bold mb-0">Selecione pelo menos um aluno.</p>';
                return;
            }

            try {
                botao.disabled = true;
                botao.innerText = 'Salvando...';
                const response = await fetch('../../../api/equipes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'adicionar_usuarios',
                        id_equipe: Number(idEquipe),
                        usuarios: ids
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao salvar.');
                msg.innerHTML = '<p class="text-success fw-bold mb-0">Alterações salvas com sucesso.</p>';
            } catch (error) {
                msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
            } finally {
                botao.disabled = false;
                botao.innerText = 'Salvar alterações';
            }
        };

        document.getElementById('btnSalvarAlunosDesktop').addEventListener('click', () => salvar(document.getElementById('btnSalvarAlunosDesktop'), document.getElementById('msgAlunosDesktop')));
        document.getElementById('btnSalvarAlunosMobile').addEventListener('click', () => salvar(document.getElementById('btnSalvarAlunosMobile'), document.getElementById('msgAlunosMobile')));
    }

    document.getElementById('buscaAlunoDesktop').addEventListener('input', (e) => filtrar(e.target.value));
    document.getElementById('buscaAlunoMobile').addEventListener('input', (e) => filtrar(e.target.value));

    // Importar alunos via PDF
    document.getElementById('formImportarPdf').addEventListener('submit', async (event) => {
        event.preventDefault();
        const params = new URLSearchParams(window.location.search);
        const idInterclasse = params.get('id');
        const idCategoria = params.get('id_categoria');
        const idTurma = params.get('id_turma');

        const nomeTurma = document.getElementById('inputNomeTurmaPdf').value.trim();
        const arquivoPdf = document.getElementById('inputPdfTurma').files?.[0];
        const btn = document.getElementById('btnEnviarPdf');
        const msg = document.getElementById('msgImportarPdf');
        msg.innerHTML = '';

        if (!nomeTurma || !arquivoPdf) {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">Preencha o nome da turma e selecione um PDF.</p>';
            return;
        }
        const ext = arquivoPdf.name.split('.').pop()?.toLowerCase();
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
            formData.append('id_interclasse', idInterclasse || '');
            formData.append('id_categoria', idCategoria || '');
            formData.append('id_turma', idTurma || '');

            const response = await fetch('../../../api/upload_turma_pdf.php', {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Falha ao enviar PDF');
            }
            msg.innerHTML = `<p class="text-success fw-bold mb-0">${result.message}</p>`;
            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalImportarPdf')).hide();
                document.getElementById('inputNomeTurmaPdf').value = '';
                document.getElementById('inputPdfTurma').value = '';
                msg.innerHTML = '';
                carregar(); // Recarrega a lista de alunos
            }, 1500);
        } catch (error) {
            console.error('Erro ao enviar PDF:', error);
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Enviar PDF';
        }
    });

    // Preencher nome da turma automaticamente ao abrir o modal
    document.getElementById('modalImportarPdf').addEventListener('show.bs.modal', async () => {
        const params = new URLSearchParams(window.location.search);
        const idTurma = params.get('id_turma');
        const inp = document.getElementById('inputNomeTurmaPdf');
        if (idTurma && !inp.value) {
            try {
                const res = await fetch(`../../../api/turmas.php?id_turma=${idTurma}`);
                const turmas = await res.json();
                if (turmas && turmas[0] && turmas[0].nome_turma) {
                    inp.value = turmas[0].nome_turma;
                }
            } catch (e) { /* ignora */ }
        }
    });

    window.addEventListener('load', carregar);
</script>

<?php
require_once '../componentes/footer.php';
?>
