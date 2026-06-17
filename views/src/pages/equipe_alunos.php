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
        <div id="listaAlunosMobile">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
        <button id="btnSalvarAlunosMobile" class="btn btn-danger w-100 mt-3">Salvar alterações</button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Adicionar alunos à equipe</h4>
            <div class="d-flex gap-2">
                <button id="btnSalvarAlunosDesktop" class="btn btn-danger">Salvar alterações</button>
                <a href="./equipes.php" id="btnVoltarEquipesDesktop" class="btn btn-outline-danger">Voltar</a>
            </div>
        </div>
        <input type="text" id="buscaAlunoDesktop" class="form-control mb-3" placeholder="Buscar aluno">
        <div id="listaAlunosDesktop">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
    </div>
</main>

<div id="toastMensagem" class="position-fixed top-0 start-50 translate-middle-x z-3 p-3" style="display:none; margin-top: 10px;">
    <div class="d-flex align-items-center gap-2 px-4 py-3 rounded-3 shadow-lg" id="toastConteudo" style="min-width: 280px; background: white; border-left: 5px solid #198754;">
        <i class="bi fs-4" id="toastIcone"></i>
        <span class="fw-semibold" id="toastTexto"></span>
    </div>
</div>

<script>
    let alunos = [];
    let alunosNaEquipe = [];

    function mostrarToast(tipo, texto) {
        const container = document.getElementById('toastMensagem');
        const conteudo = document.getElementById('toastConteudo');
        const icone = document.getElementById('toastIcone');
        const txt = document.getElementById('toastTexto');
        const cor = tipo === 'sucesso' ? '#198754' : '#dc3545';
        const iconeNome = tipo === 'sucesso' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
        conteudo.style.borderLeftColor = cor;
        icone.className = `bi ${iconeNome} fs-4`;
        txt.textContent = texto;
        container.style.display = 'block';
        clearTimeout(container._timer);
        container._timer = setTimeout(() => { container.style.display = 'none'; }, 4000);
    }

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
        const voltar = `./elenco_equipe.php?id=${idInterclasse}&id_turma=${idTurma}${idCategoria ? `&id_categoria=${idCategoria}` : ''}`;
        document.getElementById('btnVoltarEquipesDesktop').href = voltar;
        const vm = document.getElementById('btnVoltarEquipesMobile');
        if (vm) vm.href = voltar;

        try {
            const ts = Date.now();
            const resEquipe = await fetch(`../../../api/equipes.php?id_equipe=${idEquipe}&_t=${ts}`);
            const rawEq = await resEquipe.json();
            alunosNaEquipe = Array.isArray(rawEq) ? rawEq : [];

            const res = await fetch(`../../../api/usuarios.php?acao=listar_competidores&id_turma=${idTurma}&_t=${ts}`);
            const data = await res.json();
            alunos = (data && data.competidores) ? data.competidores : (Array.isArray(data) ? data : []);
            renderizar(alunos);
        } catch (error) {
            console.error(error);
            document.getElementById('listaAlunosMobile').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
            document.getElementById('listaAlunosDesktop').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        }

        const salvar = async () => {
            const checks = Array.from(document.querySelectorAll('.aluno-check:checked'));
            const ids = checks.map((item) => Number(item.value)).filter(Boolean);
            if (!ids.length) {
                mostrarToast('erro', 'Selecione pelo menos um aluno.');
                return;
            }

            const botao = document.getElementById('btnSalvarAlunosDesktop');
            const botaoMob = document.getElementById('btnSalvarAlunosMobile');
            try {
                botao.disabled = true;
                botaoMob.disabled = true;
                botao.innerText = 'Salvando...';
                botaoMob.innerText = 'Salvando...';
                const response = await fetch('../../../api/equipes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        acao: 'adicionar_usuarios',
                        id_equipe: Number(idEquipe),
                        usuarios: ids
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao salvar.');
                mostrarToast('sucesso', 'Alterações salvas com sucesso.');
                await carregar();
            } catch (error) {
                mostrarToast('erro', error.message);
            } finally {
                botao.disabled = false;
                botaoMob.disabled = false;
                botao.innerText = 'Salvar alterações';
                botaoMob.innerText = 'Salvar alterações';
            }
        };

        document.getElementById('btnSalvarAlunosDesktop').addEventListener('click', salvar);
        document.getElementById('btnSalvarAlunosMobile').addEventListener('click', salvar);
    }

    document.getElementById('buscaAlunoDesktop').addEventListener('input', (e) => filtrar(e.target.value));
    document.getElementById('buscaAlunoMobile').addEventListener('input', (e) => filtrar(e.target.value));

    window.addEventListener('pageshow', carregar);
</script>

<?php
require_once '../componentes/footer.php';
?>