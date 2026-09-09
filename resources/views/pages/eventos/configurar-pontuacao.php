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
?>

<main class="main-desktop-layout main-ptc-layout">
    <div class="px-0 ptc-container">

        <div class="ptc-header">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarPontuacao" class="ptc-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>
            <div class="ptc-title-wrap">
                <h1 class="ptc-title"><i class="bi bi-award"></i> Edição de Pontuações</h1>
                <p class="ptc-subtitle">Ajuste os pontos de cada colocação e os multiplicadores de evento</p>
            </div>
            <div class="ptc-actions">
                <span class="ptc-unsaved d-none" id="ptcUnsaved">
                    <i class="bi bi-exclamation-circle-fill"></i> Alterações não salvas
                </span>
                <button type="button" class="btn ptc-btn-default" id="btnRestaurarPadrao" onclick="restaurarPadrao()" disabled>
                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrão
                </button>
                <button type="button" class="btn btn-danger ptc-btn-salvar" id="btnSalvarPontuacao" onclick="salvarPontuacao()" disabled>
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn ptc-btn-continuar d-none">
                    Continuar <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($ptcCards as $c): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="ptc-card ptc-card--<?= $c['classe'] ?>">
                    <span class="ptc-rank-badge"><?= $c['badge'] ?></span>

                    <div class="ptc-card-head">
                        <div class="ptc-card-icon"><i class="bi <?= $c['icone'] ?>"></i></div>
                        <div>
                            <div class="ptc-card-title"><?= $c['titulo'] ?></div>
                            <div class="ptc-card-sub"><?= $c['sub'] ?></div>
                        </div>
                    </div>

                    <div class="ptc-card-value">
                        <span class="ptc-card-label"><?= $c['label'] ?></span>
                        <div class="ptc-stepper">
                            <button type="button" class="ptc-step-btn ptc-step-btn--minus" aria-label="Diminuir <?= $c['titulo'] ?>" disabled>
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="ptc-step-input" id="pontos-<?= $c['key'] ?>"
                                   value="<?= $c['valor'] ?>" min="0" step="1" inputmode="numeric" disabled>
                            <button type="button" class="ptc-step-btn ptc-step-btn--plus" aria-label="Aumentar <?= $c['titulo'] ?>" disabled>
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ptc-card-foot"><?= $c['desc'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ptc-note">
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
