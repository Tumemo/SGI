<?php
$mostrarVoltar = $mostrarVoltar ?? true;
$titulo = $titulo ?? '';
$tagTituloCompacto = ($tagTituloCompacto ?? 'h2') === 'h1' ? 'h1' : 'h2';
$urlVoltar = $urlVoltar ?? \App\Shared\Http\Url::to('aluno/inicio');
$idVoltarMobile = (string) ($idVoltarMobile ?? 'sgiBtnVoltar');
$compacteCabecalho = (bool) ($compacteCabecalho ?? false);
?>
<section class="d-md-none position-relative sgi-u-h-120px<?= $compacteCabecalho ? ' sgi-compact-header' : '' ?>" >
    <?php
    $mostrarVoltarHeader = $mostrarVoltarHeader ?? true;
    if ($mostrarVoltarHeader) {
        $sgiUrlVoltar = $urlVoltar;
        $sgiIdVoltar = $idVoltarMobile;
        $sgiClassVoltar = 'sgi-u-top-20px-left-20px-z-10';
        $urlVoltarRestauro = $urlVoltar ?? null;
        include SGI_ROOT . '/resources/views/components/back-button.php';
        $urlVoltar = $urlVoltarRestauro;
        unset($sgiUrlVoltar, $sgiIdVoltar, $sgiClassVoltar, $urlVoltarRestauro);
    }
    ?>
    <?php if (!empty($titulo)): ?>
    <<?= $tagTituloCompacto ?> class="sgi-mobile-header-title text-black fw-bold m-0 px-1"><?= htmlspecialchars($titulo) ?></<?= $tagTituloCompacto ?>>
    <?php endif; ?>
</section>