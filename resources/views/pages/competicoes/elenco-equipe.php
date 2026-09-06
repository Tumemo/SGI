<?php
$tituloPagina = 'SGI - Elenco';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3 sgi-inline-d6522d52" >
    <a href="./edicao_equipes.php" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" id="btnVoltarElencoMob" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseElencoMob">Interclasse</span>
    </a>
    <div id="alertaLimiteMob" class="alert alert-danger d-none d-flex flex-wrap align-items-center gap-2 small"></div>
    <div id="listaElencoMob" class="d-flex flex-column gap-2"></div>
    <?php if ($isAdmin): ?>
    <a class="btn btn-aluno w-100 mt-4" id="linkGerenciarMob" href="#">
        <i class="bi bi-person-plus"></i>
    </a>
    <?php endif; ?>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <a href="./edicao_equipes.php" id="btnVoltarElencoDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseElencoDesk">Interclasse</span>
            </a>
            <h1>Elenco da equipe</h1>
            <?php if ($isAdmin): ?>
            <div class="ms-auto">
                <a class="btn btn-aluno" id="linkGerenciarDesk" href="#">
                    <i class="bi bi-person-plus"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="aluno-card">
            <div id="alertaLimiteDesk" class="alert alert-danger d-none d-flex flex-wrap align-items-center gap-2 small mx-3 mt-3 mb-0"></div>
            <div class="table-responsive">
                <table class="aluno-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>RM / Matrícula</th>
                            <?php if ($isAdmin): ?>
                            <th class="text-end">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbodyElencoDesk"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script type="application/json" data-sgi-config="competicoes/elenco-equipe"><?= json_encode(['value1' => ($isAdmin)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/elenco-equipe.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
