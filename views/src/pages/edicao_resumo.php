<?php
$titulo = "Resumo";
$textTop = "Resumo da Edição";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="mt-4 d-flex justify-content-center align-items-center flex-column position-relative d-md-none" style="margin-bottom: 120px;">
    <a href="./edicao_modalidades.php" id="linkEditarModalidadesMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Modalidades</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoModalidadesMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="./edicao_pontuacao.php" id="linkEditarRegulamentosMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Pontuação</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoRegulamentosMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="./edicao_categorias.php" id="linkEditarCategoriasMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Categorias</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoCategoriasMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="./turmas.php" id="linkEditarTurmasMobile" class="text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1" style="width: 90%; min-height: 90px;">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Turmas</h2>
                <p class="text-secondary m-0 mt-1 text-truncate" id="resumoTurmasMobile" style="font-size: 14px;">(Carregando...)</p>
            </div>
        </div>
    </a>

    <section class="d-flex gap-4 mt-3 position-fixed translate-middle" style="width: max-content; top: 85%; left: 50%; z-index: 10; cursor: pointer;">
        <a href="./edicao_pontuacao.php" id="btnVoltarMobile" class="btn btn-dark">Voltar</a>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#adicionarCategoria">Adicionar categoria</button>
    </section>
</main>


<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <a href="./edicao_pontuacao.php" id="btnVoltarResumoTopo" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 shadow-sm text-decoration-none" style="border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseResumo">Interclasse</span>
            </a>
        </div>

        <div class="d-flex flex-column gap-3 mb-5">
            <a href="./edicao_modalidades.php" id="linkEditarModalidadesDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Modalidades</h6>
                    <span class="text-secondary text-truncate" id="resumoModalidadesDesktop" style="font-size: 0.95rem;">
                        (Carregando...)
                    </span>
                </div>
            </a>

            <a href="./edicao_pontuacao.php" id="linkEditarRegulamentosDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Regulamento</h6>
                    <span class="text-secondary text-truncate" id="resumoRegulamentosDesktop" style="font-size: 0.95rem;">
                        (Carregando...)
                    </span>
                </div>
            </a>

            <a href="./edicao_categorias.php" id="linkEditarCategoriasDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Categorias</h6>
                    <span class="text-secondary text-truncate" id="resumoCategoriasDesktop" style="font-size: 0.95rem;">(Carregando...)</span>
                </div>
            </a>

            <a href="./turmas.php" id="linkEditarTurmasDesktop" class="text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center" style="cursor: pointer; transition: 0.2s ease; min-height: 100px;" onmouseover="this.classList.add('shadow')" onmouseout="this.classList.remove('shadow')">
                    <h6 class="text-dark fw-medium mb-1">Turmas</h6>
                    <span class="text-secondary text-truncate" id="resumoTurmasDesktop" style="font-size: 0.95rem;">(Carregando...)</span>
                </div>
            </a>
        </div>
    </div>

    <div class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, rgba(248,249,250,1) 70%, rgba(248,249,250,0) 100%); padding: 30px 0;">
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3" style="max-width: 80%;">
            <a href="./edicao_pontuacao.php" id="btnVoltarDesktop" class="text-decoration-none">
                <button class="btn btn-outline-danger bg-white fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Voltar
                </button>
            </a>
            <a href="./dashboard.php" class="text-decoration-none" id="btnCriarInterclasseFinal">
                <button class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Criar interclasse
                </button>
            </a>
        </div>
    </div>
</main>



<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    async function resolverInterclasseAtual() {
        if (idInterclasse) {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
            return dados;
        }
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        if (ativo) {
            idInterclasse = ativo.id_interclasse;
        }
        return ativo;
    }

    // Transferindo IDs para os links
    async function configurarLinks() {
        const interclasse = await resolverInterclasseAtual();
        if (!idInterclasse) return;
        const nome = interclasse?.nome_interclasse || 'Interclasse';

        document.getElementById('btnVoltarMobile').href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        document.getElementById('btnVoltarDesktop').href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        document.getElementById('btnVoltarResumoTopo').href = `./edicao_pontuacao.php?id=${idInterclasse}`;

        document.getElementById('linkEditarModalidadesMobile').href = `./edicao_modalidades.php?id=${idInterclasse}`;
        document.getElementById('linkEditarModalidadesDesktop').href = `./edicao_modalidades.php?id=${idInterclasse}`;

        document.getElementById('linkEditarRegulamentosMobile').href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        document.getElementById('linkEditarRegulamentosDesktop').href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        document.getElementById('linkEditarCategoriasMobile').href = `./edicao_categorias.php?id=${idInterclasse}`;
        document.getElementById('linkEditarCategoriasDesktop').href = `./edicao_categorias.php?id=${idInterclasse}`;
        window.SGIInterclasse.getActiveInterclasse().then((ativo) => {
            const idTurmas = ativo?.id_interclasse || idInterclasse;
            document.getElementById('linkEditarTurmasMobile').href = `./turmas.php?id=${idTurmas}`;
            document.getElementById('linkEditarTurmasDesktop').href = `./turmas.php?id=${idTurmas}`;
        }).catch(() => {
            document.getElementById('linkEditarTurmasMobile').href = `./turmas.php?id=${idInterclasse}`;
            document.getElementById('linkEditarTurmasDesktop').href = `./turmas.php?id=${idInterclasse}`;
        });
        document.getElementById('btnCriarInterclasseFinal').href = `./dashboard.php?id=${idInterclasse}`;
        document.getElementById('nomeInterclasseResumo').innerText = nome;
        window.SGIInterclasse.updatePageTitle(nome);
    }

    // Carregar Resumos
    async function carregarResumos() {
        if (!idInterclasse) {
            const alerta = '(Nenhum interclasse ativo encontrado)';
            document.getElementById('resumoModalidadesMobile').innerText = alerta;
            document.getElementById('resumoModalidadesDesktop').innerText = alerta;
            return;
        }

        // --- Tentativa de carregar Modalidades ---
        try {
            const resMod = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const dataMod = await resMod.json();
            
            let textoModalidades = "(Nenhuma modalidade cadastrada)";
            
            if (Array.isArray(dataMod) && dataMod.length > 0) {
                const nomes = dataMod.map(m => m.nome_modalidade).join(', ');
                textoModalidades = `(${nomes})`;
            }

            document.getElementById('resumoModalidadesMobile').innerText = textoModalidades;
            document.getElementById('resumoModalidadesDesktop').innerText = textoModalidades;
        } catch (error) {
            // Em caso de erro, mostramos diretamente que não há nada
            const textoPadrao = "(Nenhuma modalidade cadastrada)";
            document.getElementById('resumoModalidadesMobile').innerText = textoPadrao;
            document.getElementById('resumoModalidadesDesktop').innerText = textoPadrao;
        }

        // --- Tentativa de carregar Regulamentos ---
        try {
            // A API de regulamento ainda não existe neste projeto.
            // Mantemos um resumo estático para não quebrar o fluxo visual.
            let textoRegulamento = "(Configuração de pontuação disponível na tela anterior)";

            document.getElementById('resumoRegulamentosMobile').innerText = textoRegulamento;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoRegulamento;
            
        } catch (error) {
            const textoPadrao = "(Configuração de pontuação disponível na tela anterior)";
            document.getElementById('resumoRegulamentosMobile').innerText = textoPadrao;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoPadrao;
        }

        try {
            const [resCategorias, resTurmas] = await Promise.all([
                fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`),
                fetch(`../../../api/turmas.php?id_interclasse=${idInterclasse}`)
            ]);
            const categorias = await resCategorias.json();
            const turmas = await resTurmas.json();
            const textoCategorias = Array.isArray(categorias) && categorias.length
                ? `${categorias.length} categoria(s) cadastrada(s)`
                : '(Nenhuma categoria cadastrada)';
            const textoTurmas = Array.isArray(turmas) && turmas.length
                ? `${turmas.length} turma(s) cadastrada(s)`
                : '(Nenhuma turma cadastrada)';
            document.getElementById('resumoCategoriasMobile').innerText = textoCategorias;
            document.getElementById('resumoCategoriasDesktop').innerText = textoCategorias;
            document.getElementById('resumoTurmasMobile').innerText = textoTurmas;
            document.getElementById('resumoTurmasDesktop').innerText = textoTurmas;
        } catch (error) {
            document.getElementById('resumoCategoriasMobile').innerText = '(Erro ao carregar categorias)';
            document.getElementById('resumoCategoriasDesktop').innerText = '(Erro ao carregar categorias)';
            document.getElementById('resumoTurmasMobile').innerText = '(Erro ao carregar turmas)';
            document.getElementById('resumoTurmasDesktop').innerText = '(Erro ao carregar turmas)';
        }
    }

    window.onload = async () => {
        await configurarLinks();
        await carregarResumos();
    };

    document.getElementById('btnCriarInterclasseFinal')?.addEventListener('click', async (event) => {
        if (!idInterclasse) return;
        event.preventDefault();
        try {
            const lista = await window.SGIInterclasse.getInterclasses();
            for (const item of lista) {
                if (String(item.id_interclasse) !== String(idInterclasse) && String(item.status_interclasse) === '1') {
                    const body = new FormData();
                    body.append('status_interclasse', '0');
                    await fetch(`../../../api/interclasse.php?id=${item.id_interclasse}`, { method: 'POST', body });
                }
            }
            const bodyAtual = new FormData();
            bodyAtual.append('status_interclasse', '1');
            await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, { method: 'POST', body: bodyAtual });
        } catch (error) {
            console.error(error);
        } finally {
            await window.SGIInterclasse.refreshNavigation();
            window.location.href = `./dashboard.php?id=${idInterclasse}`;
        }
    });
</script>

<?php
require_once '../componentes/footer.php';
?>