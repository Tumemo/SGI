<?php
(session_status() === PHP_SESSION_NONE) && session_start();

$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);

// Busca a foto da sessão ou do array $usuarioPerfil (caso esteja definido na página perfil.php)
$fotoUsuario = $_SESSION['foto_usuario'] ?? $usuarioPerfil['foto_usuario'] ?? null;

$navItens = [
    'home'    => ['label' => 'Início',   'icon' => 'bi-house-door',   'url' => './home.php'],
    'ranking' => ['label' => 'Ranking',  'icon' => 'bi-trophy',       'url' => './ranking.php'],
    'perfil'  => ['label' => 'Perfil',   'icon' => 'bi-person-gear',  'url' => './perfil.php'],
    'termos'  => ['label' => 'Termos',   'icon' => 'bi-file-text',    'url' => './termos.php'],
];

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$iconeNav = fn($icon, $key) => $key === $paginaAtiva ? $icon . '-fill' : $icon;
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>

<!-- Estilos para a foto redonda no menu de navegação -->
<style>
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
    .active-nav-icon .nav-avatar-img {
        border-color: #ffe6e6;
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        transform: scale(1.1);
    }
</style>

<!-- Navbar Mobile (Fixo na parte inferior) -->
<nav class="d-md-none fixed-bottom py-1 bg-danger shadow-lg" style="z-index: 1040;">
    <ul class="nav justify-content-around flex-wrap fs-5 list-unstyled mb-0 gap-0 px-1 align-items-center">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1 d-flex align-items-center justify-content-center <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" aria-label="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img-mobile" alt="Perfil">
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

<!-- Navbar Desktop (Barra lateral esquerda) -->
<nav class="d-none d-md-flex flex-column position-fixed vh-100 start-0 shadow-lg bg-danger" style="width: 80px; top: 0; z-index: 1040;">
    <ul class="nav flex-column align-items-center justify-content-around h-100 py-4 gap-4 fs-3">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img" alt="Perfil">
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