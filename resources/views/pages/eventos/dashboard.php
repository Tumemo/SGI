<?php
$titulo = 'Dashboard';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('aluno/inicio');
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
$isAdmin = $nivelUsuario === 0;
$isColaborador = $nivelUsuario === 1;
$isMesario = $nivelUsuario === 2;
?>

<!-- Casca fixa do SPA do mesário: o conteúdo desta div é trocado
     dinamicamente pelas telas baixadas pelo mesario-offline.js.
     Header/Nav/Footer (componentes) permanecem fixos na página. -->
<div id="conteudo-principal" data-sgi-shell="1">
    <main class="main-desktop-layout main-dashboard-layout">
        <div class="container-fluid px-0">
            <a href="<?= \App\Shared\Http\Url::to('aluno/inicio') ?>" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
                <i class="bi bi-house"></i> Voltar ao início
            </a>

        <?php if ($isAdmin): ?>
        <div id="avisoFinalizacaoInterclasse" class="d-none alert alert-warning mb-4">
            <span>O interclasse está inativo no momento.</span>
        </div>
        <?php endif; ?>

        <div class="row g-4 mt-2">
            <?php if ($isMesario): ?>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/agenda') ?>" id="linkAgenda" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-calendar3"></i></div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Visualize o cronograma dos jogos, acesse o placar e acompanhe os resultados das partidas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('chaveamento') ?>" id="linkChaveamentos" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">CHAVEAMENTOS</h5>
                    </div>
                    <p class="dash-card-text">Visualize os chaveamentos e acesse os confrontos das modalidades.</p>
                </a>
            </div>
           
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('ocorrencias') ?>" id="linkOcorrencias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <h5 class="dash-card-title">OCORRÊNCIAS</h5>
                    </div>
                    <p class="dash-card-text">Registre ocorrências e aplique descontos de pontos nas turmas.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($isColaborador): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('modalidades') ?>" id="linkModalidades" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Visualize e crie novas modalidades esportivas para a competição.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkPontuacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-award"></i></div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Acompanhe a tabela de pontos e o desempenho das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/locais') ?>" id="linkLocais" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="dash-card-title">LOCAIS E REGULAMENTO</h5>
                    </div>
                    <p class="dash-card-text">Cadastre e visualize os locais onde os jogos acontecem.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('colaboradores') ?>" id="linkColaboradores" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-people"></i></div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie a equipe de apoio e voluntários do evento.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/arrecadacao') ?>" id="linkArrecadacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-basket"></i></div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Adicione e acompanhe os kg arrecadados na gincana.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('ocorrencias') ?>" id="linkOcorrenciasColab" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <h5 class="dash-card-title">OCORRÊNCIAS</h5>
                    </div>
                    <p class="dash-card-text">Registre ocorrências e aplique descontos de pontos nas turmas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('categorias') ?>" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Configure as divisões da competição por faixa ou nível.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkTurmas" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-backpack"></i></div>
                        <h5 class="dash-card-title">TURMAS</h5>
                    </div>
                    <p class="dash-card-text">Visualize as turmas participantes e acesse os alunos.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/equipes') ?>" id="linkEquipes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">EQUIPES</h5>
                    </div>
                    <p class="dash-card-text">Visualize equipes por modalidade e crie novas equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('ranking') ?>" id="linkRanking" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">RANKING</h5>
                    </div>
                    <p class="dash-card-text">Visualize o ranking geral de pontuações por categoria.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/modalidades') ?>" id="linkModalidades" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">MODALIDADES</h5>
                    </div>
                    <p class="dash-card-text">Cadastre e gerencie as modalidades esportivas, regulamentos e especificações de cada competição.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkPontuacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-award"></i></div>
                        <h5 class="dash-card-title">PONTUAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Acompanhe a tabela de pontos, critérios de classificação e o histórico de pontuação das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/locais') ?>" id="linkLocais" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-building-gear"></i></div>
                        <h5 class="dash-card-title">LOCAIS E REGULAMENTO DO INTERCLASSE</h5>
                    </div>
                    <p class="dash-card-text">Cadastre quadras, ginásios e demais espaços usados nos jogos antes de montar a agenda e cadstre e atualize o regulamento do interclasse.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/agenda') ?>" id="linkAgenda" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-calendar3"></i></div>
                        <h5 class="dash-card-title">AGENDA</h5>
                    </div>
                    <p class="dash-card-text">Organize o cronograma dos jogos, definição de confrontos, datas e horários das partidas.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/arrecadacao') ?>" id="linkArrecadacoes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-basket"></i></div>
                        <h5 class="dash-card-title">ARRECADAÇÕES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie os kg arrecadados na gincana, metas, pontos de entrega e o impacto das doações.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('ocorrencias') ?>" id="linkOcorrenciasAdmin" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <h5 class="dash-card-title">OCORRÊNCIAS</h5>
                    </div>
                    <p class="dash-card-text">Registre ocorrências e aplique descontos de pontos nas turmas por modalidade.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkCategorias" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-bookmark"></i></div>
                        <h5 class="dash-card-title">CATEGORIAS</h5>
                    </div>
                    <p class="dash-card-text">Configure as divisões da competição por faixa etária, gênero ou nível técnico dos participantes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('colaboradores') ?>" id="linkColaboradores" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><img src="<?= \App\Shared\Http\Assets::url('images/icon_equipes.png') ?>" alt="Icone de equipe"></div>
                        <h5 class="dash-card-title">COLABORADORES</h5>
                    </div>
                    <p class="dash-card-text">Gerencie a equipe de organização, voluntários, comissão técnica e juízes do evento.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkTurmas" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-backpack"></i></div>
                        <h5 class="dash-card-title">TURMAS</h5>
                    </div>
                    <p class="dash-card-text">Categorias e turmas desta edição: cadastro, PDF de alunos e acesso às equipes por turma.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/equipes') ?>" id="linkEquipes" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-people"></i></div>
                        <h5 class="dash-card-title">EQUIPES</h5>
                    </div>
                    <p class="dash-card-text">Visualize equipes por categoria e modalidade e abra o elenco de cada turma.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('chaveamento') ?>" id="linkChaveamento" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-diagram-3"></i></div>
                        <h5 class="dash-card-title">CHAVEAMENTO</h5>
                    </div>
                    <p class="dash-card-text">Visualize a árvore completa do chaveamento mata-mata: confrontos, resultados e avanço das equipes.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= \App\Shared\Http\Url::to('ranking') ?>" id="linkRanking" class="dash-card">
                    <div class="dash-card-red-corner"></div>
                    <div class="dash-card-header">
                        <div class="dash-card-icon"><i class="bi bi-trophy"></i></div>
                        <h5 class="dash-card-title">RANKING</h5>
                    </div>
                    <p class="dash-card-text">Visualize o ranking geral de pontuações por categoria.</p>
                </a>
            </div>
             <?php endif; ?>
        </div>
    </div>
    </main>
</div><!-- /conteudo-principal -->

<script type="application/json" data-sgi-config="eventos/dashboard"><?= json_encode(['value0' => ($isMesario), 'value1' => ($isAdmin), 'value2' => ($isColaborador)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/dashboard.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
