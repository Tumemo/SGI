<?php
(session_status() === PHP_SESSION_NONE) && session_start();

$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);

// Busca a foto da sessão ou do array $usuarioPerfil (caso esteja definido na página perfil.php)
$fotoUsuario = $_SESSION['foto_usuario'] ?? $usuarioPerfil['foto_usuario'] ?? null;
if ($fotoUsuario) {
    $fotoPath = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios' . DIRECTORY_SEPARATOR . $fotoUsuario;
    if (!file_exists($fotoPath)) $fotoUsuario = null;
}
$nomeUsuario = $_SESSION['nome'] ?? $usuarioPerfil['nome_usuario'] ?? 'Usuário';
$inicialNome = mb_strtoupper(mb_substr($nomeUsuario, 0, 1));

// LISTA DE ITENS DO MENU (O menu Jogos foi adicionado aqui!)
$navItens = [
    'perfil'  => ['label' => 'Perfil',   'icon' => 'bi-person-gear',    'url' => './perfil.php'],
    'home'    => ['label' => 'Início',   'icon' => 'bi-house-door',     'url' => './home.php'],
    'jogos'   => ['label' => 'Jogos',    'icon' => 'bi-calendar-event', 'url' => './jogos.php'],
    'termos'  => ['label' => 'Termos',   'icon' => 'bi-file-text',      'url' => './termos.php'],
];

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$iconeNav = fn($icon, $key) => $key === $paginaAtiva ? $icon . '-fill' : $icon;
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>

<!-- Estilos para a foto redonda no menu de navegação -->
<style>
    .mobile-nav {
        height: 64px;
    }
    .mobile-nav .nav {
        flex-wrap: nowrap;
        height: 100%;
    }
    .mobile-nav ul li {
        flex: 1 1 0;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sidebar-nav {
        overflow-y: auto;
        scrollbar-width: none;
    }
    .sidebar-nav::-webkit-scrollbar {
        display: none;
    }
    .sidebar-nav .sidebar-nav-list {
        justify-content: flex-start;
    }
    .sidebar-nav .sidebar-nav-list li {
        margin: auto 0;
        flex-shrink: 1;
        min-height: 0;
    }
    @media (max-height: 700px) {
        .sidebar-nav .sidebar-nav-list {
            gap: 0.5rem !important;
            font-size: 1.25rem !important;
        }
    }
    .nav-avatar-img {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .nav-avatar-img-mobile {
        width: 26px;
        height: 26px;
        object-fit: cover;
        border-radius: 50%;
        border: 1.5px solid #fff;
    }
    .nav-avatar-fallback {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        border: 2px solid #fff;
    }
    .nav-avatar-fallback-mobile {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #fff;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.75rem;
        border: 1.5px solid #fff;
    }
    .active-nav-icon .nav-avatar-img {
        border-color: #ffe6e6;
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        transform: scale(1.1);
    }
    .active-nav-icon .nav-avatar-fallback {
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        transform: scale(1.1);
    }
</style>

<!-- Navbar Mobile (Fixo na parte inferior) -->
<nav class="d-md-none fixed-bottom bg-danger shadow-lg mobile-nav" style="z-index: 1040; height: 64px;">
    <ul class="nav justify-content-around flex-nowrap fs-5 list-unstyled mb-0 gap-0 px-1 align-items-center h-100">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1 d-flex align-items-center justify-content-center <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" aria-label="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img-mobile" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback-mobile"><?= $inicialNome ?></span>
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <a href="../../../api/logout.php" class="text-white-50 nav-link p-1" aria-label="Sair" <?= $onclickSair ?>>
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>
<script>
(function () {
    var nav = document.querySelector('.mobile-nav');
    if (!nav || !window.visualViewport) return;
    var baseline = window.innerHeight;
    function pin() {
        var shrink = baseline - window.innerHeight;
        nav.style.bottom = shrink > 0 ? shrink + 'px' : '0';
    }
    window.addEventListener('resize', pin);
    window.visualViewport.addEventListener('resize', pin);
    window.visualViewport.addEventListener('scroll', pin);
    window.addEventListener('orientationchange', function () {
        baseline = window.innerHeight;
        nav.style.bottom = '0';
    });
})();
</script>

<!-- Navbar Desktop (Barra lateral esquerda) -->
<nav class="d-none d-md-flex flex-column position-fixed start-0 shadow-lg sidebar-nav" style="width: 80px; top: 0; bottom: 0; z-index: 1040; background-color: #e30613;">
    <ul class="nav flex-column align-items-center h-100 py-4 gap-4 fs-3 sidebar-nav-list">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback"><?= $inicialNome ?></span>
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <a href="../../../../api/logout.php" class="text-white" <?= $onclickSair ?> title="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>