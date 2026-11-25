<?php
$titulo = 'Relatório Final do Interclasse';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
$titulo = '';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'relatorios';
?>

<main class="sgi-relatorio-page main-desktop-layout">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <?php
        $headerIdVoltar = 'btnVoltarRelatorio';
        $headerClasseTitulo = 'd-block';
        $headerCorpoHtml = '<h1 class="h2 fw-bold mb-1">Relatório Final</h1><p class="small text-body-secondary mb-0" id="sgiRelatorioEdicao">Carregando o interclasse selecionado…</p>';
        $headerAcoesHtml = '<a id="btnGerarRelatorio" class="btn btn-primary d-inline-flex align-items-center gap-2" href="#" aria-disabled="true" tabindex="-1"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span>Gerar Relatório</span></a>';
        include SGI_ROOT . '/resources/views/components/page-header.php';
        unset($headerCorpoHtml, $headerAcoesHtml, $headerIdVoltar, $headerClasseTitulo, $headerMostrarVoltar, $headerClasse, $headerUrlVoltar, $headerClassBotao, $headerHiddenBotao);
        ?>

        <div id="sgiRelatorioStatus" class="alert alert-info" role="status" aria-live="polite">Carregando dados consolidados…</div>
        <div id="sgiRelatorioConteudo" class="d-none">
            <section class="sgi-relatorio-indicadores" id="sgiRelatorioMetricas" aria-label="Indicadores gerais"></section>

            <section class="sgi-relatorio-secao card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-1">Pontuação Geral das Turmas</h2>
                    <p class="text-body-secondary small mb-3">Classificação por pontos líquidos registrados, após os descontos de penalidades ativas.</p>
                    <div id="sgiRelatorioGraficoPontos" class="sgi-relatorio-grafico"></div>
                </div>
            </section>

            <section class="sgi-relatorio-secao card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-1">Doações Solidárias</h2>
                    <p class="text-body-secondary small mb-3" id="sgiRelatorioTotalDoacoes"></p>
                    <div id="sgiRelatorioGraficoDoacoes" class="sgi-relatorio-grafico"></div>
                </div>
            </section>

            <div id="sgiRelatorioCategorias" class="d-flex flex-column gap-3"></div>

            <section class="sgi-relatorio-secao card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-2">Observações da organização</h2>
                    <p id="sgiRelatorioObservacao" class="mb-2"></p>
                    <p class="text-body-secondary small mb-0" id="sgiRelatorioRegraDoacao"></p>
                </div>
            </section>

            <section class="sgi-relatorio-secao card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Central de Modalidades e Pódios</h2>
                    <div id="sgiRelatorioModalidades" class="sgi-relatorio-modalidades"></div>
                </div>
            </section>

            <section id="sgiRelatorioSecaoArtilharia" class="sgi-relatorio-secao card shadow-sm d-none">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Ranking de Artilheiros</h2>
                    <div id="sgiRelatorioArtilheiros"></div>
                </div>
            </section>

            <section class="sgi-relatorio-secao card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-1">Relatório da Gincana Solidária de Doações</h2>
                    <p class="sgi-relatorio-total-doacoes fs-5 mb-3" id="sgiRelatorioTotalDoacoesTabela"></p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle sgi-relatorio-tabela">
                            <thead><tr><th scope="col">Pos.</th><th scope="col">Turma</th><th scope="col">Categoria</th><th scope="col">Quantidade</th><th scope="col">Pontos gerados</th></tr></thead>
                            <tbody id="sgiRelatorioTabelaDoacoes"></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="resultados/relatorio-final"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/resultados/relatorio-final.js') ?>"></script>

<?php include SGI_ROOT . '/resources/views/components/admin-nav.php'; require_once SGI_ROOT . '/resources/views/components/footer.php'; ?>
