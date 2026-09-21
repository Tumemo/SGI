<?php
$titulo = 'Resumo da Edição';
$tagTituloCompacto = 'h1';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
$idVoltarMobile = 'btnVoltarMobile';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';

$etapasEdicao = [
    ['chave' => 'categorias', 'nome' => 'Categorias', 'rota' => 'edicoes/categorias'],
    ['chave' => 'turmas', 'nome' => 'Turmas', 'rota' => 'turmas'],
    ['chave' => 'modalidades', 'nome' => 'Modalidades', 'rota' => 'edicoes/modalidades'],
    ['chave' => 'pontuacao', 'nome' => 'Pontuação', 'rota' => 'edicoes/pontuacao'],
];

$renderEtapasResumoEdicao = static function (array $etapas): void {
    ?>
    <nav class="w-100 mb-4" aria-label="Etapas da edição">
        <ol class="list-unstyled d-flex flex-wrap gap-2 mb-0">
            <?php foreach ($etapas as $etapa): ?>
            <li>
                <a class="btn btn-sm btn-outline-secondary" data-sgi-etapa="<?= htmlspecialchars($etapa['chave'], ENT_QUOTES, 'UTF-8') ?>" href="<?= \App\Shared\Http\Url::to($etapa['rota']) ?>">
                    <?= htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
            <?php endforeach; ?>
            <li><span class="btn btn-sm btn-primary" aria-current="step">Resumo</span></li>
        </ol>
    </nav>
    <?php
};
?>

<main class="mt-4 d-flex justify-content-center flex-column position-relative d-md-none mb-5">
    <header class="mb-3">
        <h2 class="h4 fw-bold text-body mb-1">Resumo da edição</h2>
        <p class="text-body-secondary mb-0">
            <strong id="nomeInterclasseResumoMob">Interclasse</strong>
            <span aria-hidden="true"> · </span><span id="anoInterclasseResumoMob">Ano carregando</span>
            <span aria-hidden="true"> · </span><span id="statusInterclasseResumoMob" class="badge text-bg-secondary">Carregando status</span>
        </p>
    </header>

    <?php $renderEtapasResumoEdicao($etapasEdicao); ?>

    <a href="<?= \App\Shared\Http\Url::to('edicoes/modalidades') ?>" id="linkEditarModalidadesMobile" class="sgi-resumo-card text-decoration-none text-dark w-100 d-flex justify-content-center">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 w-100">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Modalidades</h2>
                <p class="text-secondary m-0 mt-1 text-truncate small" id="resumoModalidadesMobile">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkEditarRegulamentosMobile" class="sgi-resumo-card text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 w-100">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Pontuação</h2>
                <p class="text-secondary m-0 mt-1 text-truncate small" id="resumoRegulamentosMobile">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasMobile" class="sgi-resumo-card text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 w-100">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Categorias</h2>
                <p class="text-secondary m-0 mt-1 text-truncate small" id="resumoCategoriasMobile">(Carregando...)</p>
            </div>
        </div>
    </a>

    <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkEditarTurmasMobile" class="sgi-resumo-card text-decoration-none text-dark w-100 d-flex justify-content-center mt-3">
        <div class="shadow-sm d-flex justify-content-between align-content-center px-3 py-3 rounded-3 border border-1 w-100">
            <div class="w-100 overflow-hidden">
                <h2 class="m-0 fs-5">Turmas</h2>
                <p class="text-secondary m-0 mt-1 text-truncate small" id="resumoTurmasMobile">(Carregando...)</p>
            </div>
        </div>
    </a>

    <section id="acoesResumoMobile" class="d-grid gap-2 mt-4 mb-5 sgi-categoria-actions">
        <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasAcaoMobile" class="btn btn-outline-primary">Editar categorias</a>
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" class="btn btn-primary fw-semibold rounded-3 px-4 py-2" id="btnAcaoFinalizacaoResumoMobile">Concluir configuração</a>
    </section>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 mw-100">
        <div class="d-flex align-items-start flex-wrap gap-3 mb-3">
            <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="btnVoltarResumoTopo" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 text-decoration-none sgi-event-back-link">
                <i class="bi bi-arrow-left-circle fs-5" aria-hidden="true"></i> Voltar
            </a>
            <div>
                <h1 class="h3 fw-bold text-body mb-1">Resumo da edição</h1>
                <p class="text-body-secondary mb-0">
                    <strong id="nomeInterclasseResumo">Interclasse</strong>
                    <span aria-hidden="true"> · </span><span id="anoInterclasseResumo">Ano carregando</span>
                    <span aria-hidden="true"> · </span><span id="statusInterclasseResumo" class="badge text-bg-secondary">Carregando status</span>
                </p>
            </div>
        </div>

        <?php $renderEtapasResumoEdicao($etapasEdicao); ?>

        <div class="d-flex flex-column gap-3 mb-5">
            <a href="<?= \App\Shared\Http\Url::to('edicoes/modalidades') ?>" id="linkEditarModalidadesDesktop" class="sgi-resumo-card text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center h-100 sgi-u-cursor-pointer">
                    <h2 class="h6 text-dark fw-medium mb-1">Modalidades</h2>
                    <span class="text-secondary text-truncate small" id="resumoModalidadesDesktop">(Carregando...)</span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('edicoes/pontuacao') ?>" id="linkEditarRegulamentosDesktop" class="sgi-resumo-card text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center h-100 sgi-u-cursor-pointer">
                    <h2 class="h6 text-dark fw-medium mb-1">Pontuação</h2>
                    <span class="text-secondary text-truncate small" id="resumoRegulamentosDesktop">(Carregando...)</span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasDesktop" class="sgi-resumo-card text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center h-100 sgi-u-cursor-pointer">
                    <h2 class="h6 text-dark fw-medium mb-1">Categorias</h2>
                    <span class="text-secondary text-truncate small" id="resumoCategoriasDesktop">(Carregando...)</span>
                </div>
            </a>

            <a href="<?= \App\Shared\Http\Url::to('turmas') ?>" id="linkEditarTurmasDesktop" class="sgi-resumo-card text-decoration-none">
                <div class="bg-white border-0 shadow-sm rounded-2 p-4 d-flex flex-column justify-content-center h-100 sgi-u-cursor-pointer">
                    <h2 class="h6 text-dark fw-medium mb-1">Turmas</h2>
                    <span class="text-secondary text-truncate small" id="resumoTurmasDesktop">(Carregando...)</span>
                </div>
            </a>
        </div>
    </div>

    <div id="acoesResumoDesktop" class="d-flex justify-content-end gap-3 mt-4 mb-5 sgi-categoria-actions">
        <a href="<?= \App\Shared\Http\Url::to('edicoes/categorias') ?>" id="linkEditarCategoriasAcaoDesktop" class="btn btn-outline-primary fw-semibold rounded-3 px-4 py-2">Editar categorias</a>
        <a href="<?= \App\Shared\Http\Url::to('painel') ?>" class="btn btn-primary fw-semibold rounded-3 px-4 py-2 shadow-sm" id="btnAcaoFinalizacaoResumoDesktop">Concluir configuração</a>
    </div>
</main>

<script type="application/json" data-sgi-config="eventos/configurar-resumo"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-resumo.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
