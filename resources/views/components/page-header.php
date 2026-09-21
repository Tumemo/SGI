<?php

$headerMostrarVoltar = $headerMostrarVoltar ?? $mostrarVoltar ?? true;
$headerCorpoHtml = (string) ($headerCorpoHtml ?? '');

if ($headerCorpoHtml === '') {
    $headerTitulo = trim((string) ($titulo ?? ''));
    if ($headerTitulo !== '') {
        $headerCorpoHtml = '<h1>' . htmlspecialchars($headerTitulo, ENT_QUOTES, 'UTF-8') . '</h1>';
        $headerSubtitulo = trim((string) ($subtitulo ?? ''));
        if ($headerSubtitulo !== '') {
            $headerCorpoHtml .= PHP_EOL . '<p>' . htmlspecialchars($headerSubtitulo, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    unset($headerTitulo, $headerSubtitulo);
}

$headerAcoesHtml = (string) ($headerAcoesHtml ?? '');
$headerClasse = trim((string) ($headerClasse ?? ''));
$headerClasseTitulo = trim((string) ($headerClasseTitulo ?? 'd-none d-md-block'));
$headerUrlVoltar = (string) (($headerUrlVoltar ?? $urlVoltar) ?? \App\Shared\Http\Url::to('painel'));
$headerIdVoltar = (string) ($headerIdVoltar ?? 'sgiBtnVoltar');
$headerClassBotao = trim('sgi-page-header__back ' . (string) ($headerClassBotao ?? ''));
$headerHiddenBotao = (bool) ($headerHiddenBotao ?? false);

$classesHeader = trim('sgi-page-header ' . $headerClasse);
$classesTitulo = trim('sgi-page-header__title ' . $headerClasseTitulo);
?>
<header class="<?= $classesHeader ?>">
    <?php if ($headerMostrarVoltar): ?>
        <?php
        $mostrarVoltarBackup = $mostrarVoltar ?? null;
        $mostrarVoltar = true;
        $sgiUrlVoltar = $headerUrlVoltar;
        $sgiIdVoltar = $headerIdVoltar;
        $sgiClassVoltar = $headerClassBotao;
        $sgiHiddenVoltar = $headerHiddenBotao;
        include SGI_ROOT . '/resources/views/components/back-button.php';
        $mostrarVoltar = $mostrarVoltarBackup;
        unset($mostrarVoltarBackup, $sgiUrlVoltar, $sgiIdVoltar, $sgiClassVoltar, $sgiHiddenVoltar);
        ?>
    <?php endif; ?>

    <?php if ($headerCorpoHtml !== ''): ?>
        <div class="<?= $classesTitulo ?>">
            <?= $headerCorpoHtml ?>
        </div>
    <?php endif; ?>

    <?php if ($headerAcoesHtml !== ''): ?>
        <div class="sgi-page-header__actions">
            <?= $headerAcoesHtml ?>
        </div>
    <?php endif; ?>
</header>
