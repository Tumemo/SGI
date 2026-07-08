<?php
$tituloPagina = 'SGI - Adicionar Alunos';
$titulo = 'Inscrições';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
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
    let generoDaModalidade = 'MISTO'; // Valor padrão de fallback

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
        container._timer = setTimeout(() => {
            container.style.display = 'none';
        }, 4000);
    }

    function cardAluno(aluno, mobile = false) {
        const estaNaEquipe = alunosNaEquipe.some(a => a.id_usuario === aluno.id_usuario);
        return `
            <label class="bg-white rounded-3 shadow-sm p-3 mb-2 d-flex justify-content-between align-items-center aluno-item">
                <div>
                    <strong>${aluno.nome_usuario}</strong>
                    <div class="text-muted small">${aluno.matricula_usuario} (${aluno.genero_usuario || 'Não informado'})</div>
                </div>
                <input class="form-check-input aluno-check" type="checkbox" value="${aluno.id_usuario}" ${estaNaEquipe ? 'checked' : ''}>
            </label>
        `;
    }

    function renderizar(lista) {
        const mobile = document.getElementById('listaAlunosMobile');
        const desktop = document.getElementById('listaAlunosDesktop');

        console.log("--- Iniciando Filtragem no Front-end ---");
        console.log("Gênero da Modalidade Ativo:", generoDaModalidade);
        console.log("Total de alunos recebidos para filtrar:", lista.length);

        // --- FILTRAGEM ULTRA-FLEXÍVEL POR GÊNERO ---
        const listaFiltradaPorGenero = lista.filter(aluno => {
            // Se por acaso a modalidade for explicitamente MISTO, permite todo mundo direto
            const genMod = String(generoDaModalidade || '').toUpperCase().trim();
            if (genMod === 'MISTO' || genMod === 'MISTA' || genMod === '') return true;

            const genAluno = String(aluno.genero_usuario || '').toUpperCase().trim();

            // Identifica se a modalidade é Feminina (F, FEM, FEMININO)
            const ehModFeminina = genMod.startsWith('FEM') || genMod === 'F';
            // Identifica se a modalidade é Masculina (M, MASC, MASCULINO)
            const ehModMasculina = genMod.startsWith('MAS') || genMod === 'M';

            // Identifica se o aluno é Feminino
            const ehAlunoFeminino = genAluno.startsWith('FEM') || genAluno === 'F';
            // Identifica se o aluno é Masculino
            const ehAlunoMasculino = genAluno.startsWith('MAS') || genAluno === 'M';

            // Regra 1: Se a modalidade é feminina e o aluno é masculino, bloqueia
            if (ehModFeminina && ehAlunoMasculino) return false;

            // Regra 2: Se a modalidade é masculina e o aluno é feminino, bloqueia
            if (ehModMasculina && ehAlunoFeminino) return false;

            // Se passar pelas regras ou não se encaixar em nenhuma negação, exibe o aluno
            return true;
        });

        console.log("Total de alunos após o filtro de gênero:", listaFiltradaPorGenero.length);

        if (!listaFiltradaPorGenero.length) {
            const semDadosHtml = '<p class="text-muted text-center">Nenhum aluno compatível com o gênero da modalidade encontrado nesta turma.</p>';
            mobile.innerHTML = semDadosHtml;
            desktop.innerHTML = semDadosHtml;
            return;
        }

        mobile.innerHTML = listaFiltradaPorGenero.map((aluno) => cardAluno(aluno, true)).join('');
        desktop.innerHTML = listaFiltradaPorGenero.map((aluno) => cardAluno(aluno)).join('');
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
        const idModalidade = params.get('id_modalidade');
        console.log(idModalidade);

        const voltar = `./elenco_equipe.php?id=${idInterclasse}&id_turma=${idTurma}${idCategoria ? `&id_categoria=${idCategoria}` : ''}`;
        document.getElementById('btnVoltarEquipesDesktop').href = voltar;
        const vm = document.getElementById('btnVoltarEquipesMobile');
        if (vm) vm.href = voltar;

        try {
            const ts = Date.now();

            // 1. CARREGA O GÊNERO DA MODALIDADE ATUAL
            // 1. CARREGA O GÊNERO DA MODALIDADE ATUAL
            if (idModalidade) {
                try {
                    // Mudamos o parâmetro para id_modalidade para pegar a modalidade exata
                    const resMod = await fetch(`../../../api/modalidades.php?id_modalidade=${idModalidade}&_t=${ts}`);
                    const dadosMod = await resMod.json();

                    if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                        generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                        console.log(`[SUCESSO] Modalidade: ${dadosMod[0].nome_modalidade} | Gênero Aplicado: ${generoDaModalidade}`);
                    } else if (dadosMod && dadosMod.genero_modalidade) {
                        generoDaModalidade = dadosMod.genero_modalidade;
                    } else {
                        generoDaModalidade = 'MISTO';
                    }
                } catch (e) {
                    console.error("Erro ao obter o gênero da modalidade. Usando padrão 'MISTO'.", e);
                    generoDaModalidade = 'MISTO';
                }
            } else if (idCategoria) {
                // Fallback: Caso não venha id_modalidade na URL, ainda tenta buscar por categoria como estava antes
                try {
                    const resMod = await fetch(`../../../api/modalidades.php?id_categoria=${idCategoria}&_t=${ts}`);
                    const dadosMod = await resMod.json();
                    if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                        generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                    }
                } catch (e) {
                    generoDaModalidade = 'MISTO';
                }
            }

            // 2. CARREGA ALUNOS QUE JÁ ESTÃO NA EQUIPE
            const resEquipe = await fetch(`../../../api/equipes.php?id_equipe=${idEquipe}&_t=${ts}`);
            const rawEq = await resEquipe.json();
            alunosNaEquipe = Array.isArray(rawEq) ? rawEq : [];

            // 3. CARREGA TODOS OS ALUNOS DA TURMA
            const res = await fetch(`../../../api/usuarios.php?acao=listar_competidores&id_turma=${idTurma}&_t=${ts}`);
            const data = await res.json();

            console.log("Retorno bruto da API de usuários/competidores:", data);

            alunos = (data && data.competidores) ? data.competidores : (Array.isArray(data) ? data : []);

            // Renderiza aplicando o novo filtro inteligente
            renderizar(alunos);
        } catch (error) {
            console.error("Erro crítico ao carregar dados na tela:", error);
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

        // Remove listeners antigos para evitar duplicações caso a função carregar rode multiplas vezes
        document.getElementById('btnSalvarAlunosDesktop').replaceWith(document.getElementById('btnSalvarAlunosDesktop').cloneNode(true));
        document.getElementById('btnSalvarAlunosMobile').replaceWith(document.getElementById('btnSalvarAlunosMobile').cloneNode(true));

        document.getElementById('btnSalvarAlunosDesktop').addEventListener('click', salvar);
        document.getElementById('btnSalvarAlunosMobile').addEventListener('click', salvar);
    }

    document.getElementById('buscaAlunoDesktop').addEventListener('input', (e) => filtrar(e.target.value));
    document.getElementById('buscaAlunoMobile').addEventListener('input', (e) => filtrar(e.target.value));

    window.addEventListener('pageshow', carregar);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>