<header class="position-relative header-banner-container bg-dark">
    <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100 header-banner-img opacity-75">
    
    <?php if ($mostrarVoltar ?? true): ?>
    <a href="<?= htmlspecialchars($urlVoltar ?? './home.php') ?>" 
       class="bi bi-arrow-left position-absolute text-white fs-3 text-decoration-none bg-dark bg-opacity-50 rounded-circle px-2 py-1" 
       style="top: 15px; left: 20px; z-index: 10;"
       title="Voltar"></a>
    <?php endif; ?>

    <?php if (!empty($titulo)): ?>
    <div class="position-absolute top-50 start-50 translate-middle text-center w-100 px-3">
        <h2 class="text-white m-0 fw-bold fs-2 fs-md-1 text-shadow"><?= htmlspecialchars($titulo) ?></h2>
    </div>
    <?php endif; ?>
</header>