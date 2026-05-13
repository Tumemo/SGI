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
            <a href="./equipes.php" id="btnVoltarEquipesDesktop" class="btn btn-outline-danger">Voltar</a>
        </div>
        <input type="text" id="buscaAlunoDesktop" class="form-control mb-3" placeholder="Buscar aluno">
        <div id="listaAlunosDesktop"><p class="text-muted text-center">(Carregando alunos...)</p></div>
        <div class="d-flex justify-content-end mt-3">
            <button id="btnSalvarAlunosDesktop" class="btn btn-danger">Salvar alterações</button>
        </div>
        <div id="msgAlunosDesktop" class="text-center mt-2"></div>
    </div>
</main>

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
    window.addEventListener('load', carregar);
</script>

<?php
require_once '../componentes/footer.php';
?>
