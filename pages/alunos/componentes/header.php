<header class="position-relative">
    <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100">
    <?php if ($mostrarVoltar ?? true): ?>
    <a href="<?= htmlspecialchars($urlVoltar ?? './home.php') ?>" class="bi bi-arrow-left position-absolute text-white fs-3 text-decoration-none" style="top: 20px; left: 20px; z-index: 10;"></a>
    <?php endif; ?>
    <?php if (!empty($titulo)): ?>
    <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0 fw-bold"><?= htmlspecialchars($titulo) ?></h2>
    <?php endif; ?>
    <?php if ($mostrarSino ?? false): ?>
    <a href="./notificacoes.php" class="position-absolute text-white fs-3 d-flex align-items-center justify-content-center rounded-circle" style="top: 15%; right: 6%; width: 44px; height: 44px; background: rgba(0,0,0,0.3);" aria-label="Notificações e avisos">
        <i class="bi bi-bell"></i>
    </a>
    <?php endif; ?>
</header>
