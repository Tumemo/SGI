<?php
$tituloPagina = 'SGI - Dashboard';
$titulo = 'Dashboard';
$mostrarVoltar = true;
$urlVoltar = './home.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
$isAdmin = $nivelUsuario === 0;
$isColaborador = $nivelUsuario === 1;
$isMesario = $nivelUsuario === 2;
?>

<!-- main mobile -->
<main class="d-md-none" style="margin-bottom: 120px;">
    <a href="./home.php" class="btn btn-outline-danger btn-sm ms-3 mt-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-house"></i> Voltar ao início
    </a>
    <p class="text-center mt-3 text-secondary" id="subtituloMobile">Selecione uma opção</p>
    <section>
        <?php if ($isMesario): ?>
        <a href="./categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-bookmarks fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
            </div>
        </a>
        <a href="./agenda.php" id="linkAgenda" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-calendar fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Agenda</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
            </div>
        </a>
        <a href="./pontuacao.php" id="linkChaveamentos" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-diagram-3 fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Chaveamentos</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
            </div>
        </a>
        <a href="./ranking.php" id="linkRanking" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy-fill fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Ranking</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($isColaborador || $isAdmin): ?>
        <a href="./modalidades.php" id="linkModalidades" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Modalidades</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./pontuacoes.php" id="linkPontuacoes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-award fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Pontuações</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./locais.php" id="linkLocais" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-geo-alt fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Locais</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./colaboradores.php" id="linkColaboradores" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-people fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Colaboradores</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./arrecadacoes.php" id="linkArrecadacoes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-basket fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Arrecadações</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./categorias.php" id="linkCategorias" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-bookmarks fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Categorias</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./turmas.php" id="linkTurmas" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-backpack fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Turmas</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./equipes.php" id="linkEquipes" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-diagram-3 fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Equipes</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <a href="./ranking.php" id="linkRanking" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                <i class="bi bi-trophy-fill fs-2"></i>
                <h2 class="m-0 fs-3 w-100 px-3">Ranking</h2>
                <picture><img src="../../public/icons/arrow-right.svg" alt="Seta"></picture>
            </div>
        </a>
        <?php endif; ?>
    </section>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <a href="./home.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-house"></i> Voltar ao início
        </a>

        <?php if ($isAdmin): ?>
        <div id="avisoFinalizacaoInterclasse" class="d-none alert alert-warning d-flex justify-content-between align-items-center mb-4">
            <span>Esta edição ainda não foi finalizada. Conclua as etapas para ativá-la.</span>
            <a id="linkConcluirInterclasse" class="btn btn-sm btn-danger" href="#">Concluir criação</a>
        </div>
        <?php endif; ?>

        <h1 class="fs-2 mb-1" id="tituloDashboard">Dashboard</h1>
        <p class="text-muted mb-4" id="subtituloDesktop">Carregando...</p>

        <div class="row g-4 mt-2">
            <?php if ($isMesario): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="categorias.php" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Visualize as categorias da competição, suas turmas, equipes e partidas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="agenda.php" id="linkAgenda" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-calendar3"></i></div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Visualize o cronograma dos jogos, acesse o placar e acompanhe os resultados das partidas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="pontuacao.php" id="linkChaveamentos" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">CHAVEAMENTOS</h5>
                    </div>
                    <p class="dash-card-text">Visualize os chaveamentos e acesse os confrontos das modalidades.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="ranking.php" id="linkRanking" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h5 class="dash-card-title">RANKING</h5>
                    </div>
                    <p class="dash-card-text">Visualize o ranking geral de pontuações por categoria.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($isColaborador): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="modalidades.php" id="linkModalidades" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Visualize e crie novas modalidades esportivas para a competição.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="pontuacoes.php" id="linkPontuacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-award"></i></div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Acompanhe a tabela de pontos e o desempenho das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="locais.php" id="linkLocais" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="dash-card-title">LOCAIS</h5>
                    </div>
                    <p class="dash-card-text">Cadastre e visualize os locais onde os jogos acontecem.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="colaboradores.php" id="linkColaboradores" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-people"></i></div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie a equipe de apoio e voluntários do evento.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="arrecadacoes.php" id="linkArrecadacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-basket"></i></div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Adicione e acompanhe os itens arrecadados na gincana.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="categorias.php" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Configure as divisões da competição por faixa ou nível.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="turmas.php" id="linkTurmas" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-backpack"></i></div>
                        <h5 class="dash-card-title">TURMAS</h5>
                    </div>
                    <p class="dash-card-text">Visualize as turmas participantes e acesse os alunos.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="equipes.php" id="linkEquipes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">EQUIPES</h5>
                    </div>
                    <p class="dash-card-text">Visualize equipes por modalidade e crie novas equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="ranking.php" id="linkRanking" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h5 class="dash-card-title">RANKING</h5>
                    </div>
                    <p class="dash-card-text">Visualize o ranking geral de pontuações por categoria.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_modalidades.php" id="linkModalidades" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Cadastre e gerencie as modalidades esportivas, regulamentos e especificações de cada competição.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_pontuacao.php" id="linkPontuacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-award"></i></div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Acompanhe a tabela de pontos, critérios de classificação e o histórico de pontuação das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_locais.php" id="linkLocais" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="dash-card-title">LOCAIS</h5>
                    </div>
                    <p class="dash-card-text">Cadastre quadras, ginásios e demais espaços usados nos jogos antes de montar a agenda.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_agenda.php" id="linkAgenda" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-calendar3"></i></div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Organize o cronograma dos jogos, definição de confrontos, datas e horários das partidas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_arrecadacao.php" id="linkArrecadacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-basket"></i></div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie os itens arrecadados na gincana, metas, pontos de entrega e o impacto das doações.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_categorias.php" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Configure as divisões da competição por faixa etária, gênero ou nível técnico dos participantes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="colaboradores.php" id="linkColaboradores" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-people"></i></div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie a equipe de organização, voluntários, comissão técnica e juízes do evento.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="turmas.php" id="linkTurmas" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-backpack"></i></div>
                        <h5 class="dash-card-title">TURMAS</h5>
                    </div>
                    <p class="dash-card-text">Categorias e turmas desta edição: cadastro, PDF de alunos e acesso às equipes por turma.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="edicao_equipes.php" id="linkEquipes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">EQUIPES</h5>
                    </div>
                    <p class="dash-card-text">Visualize equipes por categoria e modalidade e abra o elenco de cada turma.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="chaveamento_arvore.php" id="linkChaveamento" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3-fill"></i></div>
                        <h5 class="dash-card-title">CHAVEAMENTO</h5>
                    </div>
                    <p class="dash-card-text">Visualize a árvore completa do chaveamento mata-mata: confrontos, resultados e avanço das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="ranking.php" id="linkRanking" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h5 class="dash-card-title">RANKING</h5>
                    </div>
                    <p class="dash-card-text">Visualize o ranking geral de pontuações por categoria.</p>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
(async function() {
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    if (!idInterclasse) {
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        idInterclasse = ativo?.id_interclasse || null;
    }

    if (!idInterclasse) {
        document.getElementById('subtituloMobile').textContent = 'Nenhum interclasse selecionado.';
        document.getElementById('subtituloDesktop').textContent = 'Nenhum interclasse selecionado.';
        return;
    }

    const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
    if (dados) {
        document.getElementById('tituloDashboard').textContent = <?= $isMesario ? 'dados.nome_interclasse' : '"Dashboard"' ?>;
        document.getElementById('subtituloMobile').textContent = 'Selecione uma opção';
        document.getElementById('subtituloDesktop').textContent = 'Selecione uma opção';
        window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);

        <?php if ($isAdmin): ?>
        if (String(dados.status_interclasse) !== '1') {
            const aviso = document.getElementById('avisoFinalizacaoInterclasse');
            if (aviso) {
                aviso.classList.remove('d-none');
                document.getElementById('linkConcluirInterclasse').href = `./edicao_resumo.php?id=${idInterclasse}`;
            }
        }
        <?php endif; ?>
    }

    const modoParam = <?= $isMesario ? "'modo=view'" : "'modo=view'" ?>;

    <?php if ($isMesario): ?>
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./categorias.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkAgenda').forEach(link => { link.href = `./agenda.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkChaveamentos').forEach(link => { link.href = `./pontuacao.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    <?php endif; ?>

    <?php if ($isColaborador): ?>
    document.querySelectorAll('#linkModalidades').forEach(link => { link.href = `./modalidades.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkPontuacoes').forEach(link => { link.href = `./pontuacoes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkLocais').forEach(link => { link.href = `./locais.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkColaboradores').forEach(link => { link.href = `./colaboradores.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkArrecadacoes').forEach(link => { link.href = `./arrecadacoes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./categorias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkTurmas').forEach(link => { link.href = `./turmas.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkEquipes').forEach(link => { link.href = `./edicao_equipes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    document.querySelectorAll('#linkModalidades').forEach(link => { link.href = `./edicao_modalidades.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkPontuacoes').forEach(link => { link.href = `./edicao_pontuacao.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkArrecadacoes').forEach(link => { link.href = `./edicao_arrecadacao.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./edicao_categorias.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkLocais').forEach(link => { link.href = `./edicao_locais.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkAgenda').forEach(link => { link.href = `./edicao_agenda.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkColaboradores').forEach(link => { link.href = `./colaboradores.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkTurmas').forEach(link => { link.href = `./turmas.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkEquipes').forEach(link => { link.href = `./edicao_equipes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkChaveamento').forEach(link => { link.href = `./chaveamento_arvore.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    <?php endif; ?>
})();
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
