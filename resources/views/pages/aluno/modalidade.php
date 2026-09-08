<?php
$tituloPagina = 'SGI - Inscrições';
$titulo = 'Inscrições';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = './home.php';
include SGI_ROOT . '/resources/views/components/aluno-head.php';
?>



<main class="modalidade-layout">

    <header class="page-header">
        <div class="page-header-inner">
            <span class="trophy-icon"><i class="bi bi-trophy-fill"></i></span>
            <div>
                <h1>Escolha suas modalidades</h1>
                <p class="subtitle">Selecione até 3 modalidades para participar do Interclasse.</p>
            </div>
        </div>
    </header>

    <section class="secao d-none" id="secaoInscricoes">
        <div class="secao-titulo">
            <span class="secao-titulo-icone"><i class="bi bi-person-check-fill"></i></span>
            <div class="secao-titulo-texto">
                <h2>Suas inscrições</h2>
                <p>Modalidades em que você já está confirmado.</p>
            </div>
            <span class="secao-badge" id="badgeInscricoes">0/3</span>
        </div>
        <div id="inscricoesAtuais"></div>
    </section>

    <section class="secao" id="secaoDisponiveis">
        <div class="secao-titulo">
            <span class="secao-titulo-icone"><i class="bi bi-grid-1x2-fill"></i></span>
            <div class="secao-titulo-texto">
                <h2>Disponíveis para escolha</h2>
                <p>Selecione até 3 modalidades para participar.</p>
            </div>
        </div>
        <div class="modalidades-grid" id="modalidadesGrid">
            <div class="col-12 text-center py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Carregando modalidades...
            </div>
        </div>
    </section>

    <div id="acoesInscricao" class="d-none">
        <div class="resumo-selecao">
            <div class="counter-box">
                <span class="counter-label">Modalidades escolhidas</span>
                <div class="counter-value"><span id="counterNum">0</span>&nbsp;<span class="counter-total">/ 3</span></div>
                <span id="statusDefault" class="status-default">Em andamento</span>
                <span id="limiteBadge" class="limite-badge d-none"><i class="bi bi-check-circle-fill"></i> Limite atingido</span>
            </div>

            <div class="resumo-progress">
                <div class="progress-header">
                    <span>Modalidades selecionadas</span>
                    <span id="progressCount" class="progress-count">0 de 3</span>
                </div>
                <div class="progress-track" id="progressTrack">
                    <div class="progress-seg"></div>
                    <div class="progress-seg"></div>
                    <div class="progress-seg"></div>
                </div>
            </div>

            <div class="resumo-actions">
                <button type="button" class="btn-save" id="btnSalvar" onclick="salvarEscolhas()" disabled>
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
            </div>
        </div>

        <p class="bottom-label small text-secondary" id="msgFeedback"></p>

        <p id="contador" class="visually-hidden"></p>
    </div>

</main>

<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalDetalhesTitle">Detalhes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalDetalhesCorpo"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEquipes" tabindex="-1" aria-labelledby="modalEquipesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalEquipesTitle">Escolha a equipe</h5>
                    <small class="text-muted" id="modalEquipesSubtitulo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalEquipesCorpo">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Carregando equipes...
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$paginaAtiva = 'inscricao';
include SGI_ROOT . '/resources/views/components/aluno-nav.php';
?>

<script type="application/json" data-sgi-config="aluno/modalidade"><?= json_encode(['value2' => ((string) ($genero_usuario)), 'value3' => ($categoria_usuario), 'value4' => ((int)($turma_usuario ?? 0)), 'value5' => ($modalidades_inscritas), 'value6' => ($id_usuario)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/aluno/modalidade.js') ?>"></script>
<script src="<?= \App\Shared\Http\Assets::url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
</body>
</html>
