<?php
$titulo = 'Pontuações';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';

$ptcCards = [
    [
        'key'     => '1',
        'classe'  => 'gold',
        'icone'   => 'bi-trophy-fill',
        'titulo'  => '1º Lugar',
        'sub'     => 'Medalha de Ouro',
        'badge'   => 'Ouro',
        'label'   => 'Pontos',
        'valor'   => 10,
        'desc'    => 'Pontos atribuídos à 1ª colocação de cada modalidade.'
    ],
    [
        'key'     => '2',
        'classe'  => 'silver',
        'icone'   => 'bi-award-fill',
        'titulo'  => '2º Lugar',
        'sub'     => 'Medalha de Prata',
        'badge'   => 'Prata',
        'label'   => 'Pontos',
        'valor'   => 7,
        'desc'    => 'Pontos atribuídos à 2ª colocação de cada modalidade.'
    ],
    [
        'key'     => '3',
        'classe'  => 'bronze',
        'icone'   => 'bi-award-fill',
        'titulo'  => '3º Lugar',
        'sub'     => 'Medalha de Bronze',
        'badge'   => 'Bronze',
        'label'   => 'Pontos',
        'valor'   => 5,
        'desc'    => 'Pontos atribuídos à 3ª colocação de cada modalidade.'
    ],
    [
        'key'     => 'arr',
        'classe'  => 'multi',
        'icone'   => 'bi-lightning-charge-fill',
        'titulo'  => 'Multiplicador',
        'sub'     => 'Arrecadação da turma',
        'badge'   => 'Especial',
        'label'   => 'Multiplicador',
        'valor'   => 2,
        'desc'    => 'Fator aplicado sobre os kg arrecadados pela turma.'
    ],
];

$ptcTheme = [
    'gold' => ['card' => 'border-warning bg-warning-subtle', 'icon' => 'bg-warning text-dark', 'badge' => 'text-bg-warning'],
    'silver' => ['card' => 'border-secondary bg-secondary-subtle', 'icon' => 'bg-secondary text-white', 'badge' => 'text-bg-secondary'],
    'bronze' => ['card' => 'border-warning bg-warning-subtle', 'icon' => 'bg-warning text-dark', 'badge' => 'text-bg-warning'],
    'multi' => ['card' => 'border-danger bg-danger-subtle', 'icon' => 'bg-danger text-white', 'badge' => 'text-bg-danger'],
];
?>

<main class="main-desktop-layout">
    <div class="container-fluid px-0">

        <div class="d-flex align-items-center gap-3 flex-wrap mb-5">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarPontuacao" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold text-body mb-1"><i class="bi bi-award text-primary me-1"></i> Edição de Pontuações</h1>
                <p class="small text-body-secondary mb-0">Ajuste os pontos de cada colocação e os multiplicadores de evento</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge text-bg-warning d-none" id="ptcUnsaved">
                    <i class="bi bi-exclamation-circle-fill"></i> Alterações não salvas
                </span>
                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="btnRestaurarPadrao" onclick="restaurarPadrao()" disabled>
                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrão
                </button>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btnSalvarPontuacao" onclick="salvarPontuacao()" disabled>
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn btn-dark d-inline-flex align-items-center gap-2 d-none">
                    Continuar <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($ptcCards as $c): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <?php $theme = $ptcTheme[$c['classe']]; ?>
                <div class="card <?= $theme['card'] ?> border-start border-4 h-100 shadow-sm p-3 position-relative">
                    <span class="badge <?= $theme['badge'] ?> position-absolute top-0 end-0 m-3"><?= $c['badge'] ?></span>

                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 <?= $theme['icon'] ?> p-2 fs-5 d-inline-flex"><i class="bi <?= $c['icone'] ?>"></i></div>
                        <div>
                            <div class="fw-bold text-body"><?= $c['titulo'] ?></div>
                            <div class="small text-body-secondary"><?= $c['sub'] ?></div>
                        </div>
                    </div>

                    <div class="text-center my-4">
                        <span class="small text-uppercase fw-bold text-body-secondary"><?= $c['label'] ?></span>
                        <div class="ptc-stepper d-flex align-items-center justify-content-center gap-2 mt-2">
                            <button type="button" class="ptc-step-btn ptc-step-btn--minus btn btn-outline-secondary btn-lg rounded-circle p-0" aria-label="Diminuir <?= $c['titulo'] ?>" disabled>
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="ptc-step-input form-control form-control-lg text-center fw-bold" id="pontos-<?= $c['key'] ?>"
                                   value="<?= $c['valor'] ?>" min="0" step="1" inputmode="numeric" disabled>
                            <button type="button" class="ptc-step-btn ptc-step-btn--plus btn btn-outline-secondary btn-lg rounded-circle p-0" aria-label="Aumentar <?= $c['titulo'] ?>" disabled>
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>

                    <p class="small text-body-secondary text-center border-top pt-2 mt-0 mb-0"><?= $c['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info d-flex align-items-center gap-2 mt-4 mb-0">
            <i class="bi bi-info-circle"></i>
            <span>Os valores são aplicados ao Interclasse ativo. Altere com os botões <strong>+</strong> e <strong>&minus;</strong> ou digite diretamente no campo central.</span>
        </div>

    </div>
</main>

<script type="application/json" data-sgi-config="eventos/configurar-pontuacao"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-pontuacao.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
