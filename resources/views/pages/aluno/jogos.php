<?php
$titulo = 'Tabela de Jogos';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = \App\Shared\Http\Url::to('aluno/inicio');

include SGI_ROOT . '/resources/views/components/aluno-head.php';
?>



<main class="jogos-layout">

    <header class="page-header">
        <span class="trophy-icon"><i class="bi bi-calendar-event"></i></span>
        <div>
            <h1>Cronograma de Jogos</h1>
            <p class="subtitle">Acompanhe as datas, horários e resultados das partidas.</p>
        </div>
    </header>

    <!-- Filtros -->
    <div class="filtro-bar">
        <div class="filtro-jogos">
            <button class="filtro-btn active" data-filter="all">Todos</button>
            <button class="filtro-btn" data-filter="agendado">Próximos Jogos</button>
            <button class="filtro-btn" data-filter="finalizado">Resultados</button>
        </div>

        <div class="filtro-modalidade">
            <span class="mod-label">Modalidade</span>
            <select id="filtroModalidade" aria-label="Filtrar por modalidade">
                <option value="all">Todas</option>
            </select>
        </div>

        <div class="filtro-modalidade">
            <span class="mod-label">Categoria</span>
            <select id="filtroCategoria" aria-label="Filtrar por categoria">
                <option value="all">Todas</option>
            </select>
        </div>
    </div>

    <!-- Container dos Jogos -->
    <div id="listaJogos" class="jogos-grid">
        <div class="empty-state">
            <div class="spinner-border spinner-border-sm text-danger mb-2" role="status"></div>
            Carregando tabela de jogos...
        </div>
    </div>

</main>

<!-- Modal de Detalhes da Modalidade -->
<div class="modal fade modalidade-modal" id="modalModalidade" tabindex="-1" aria-labelledby="modalModalidadeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalModalidadeTitle"><i class="bi bi-trophy-fill me-2"></i>Detalhes da Modalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalModalidadeCorpo">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
                    Carregando detalhes...
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$paginaAtiva = 'jogos';
include SGI_ROOT . '/resources/views/components/aluno-nav.php';
?>

<script type="application/json" data-sgi-config="aluno/jogos"><?= json_encode(['value2' => ((string) ($categoria_usuario > 0 ? (string) $categoria_usuario : 'all'))], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/aluno/jogos.js') ?>"></script>
<script src="<?= \App\Shared\Http\Assets::url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
</body>
</html>
