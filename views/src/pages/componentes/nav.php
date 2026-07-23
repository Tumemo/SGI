<?php
(session_status() === PHP_SESSION_NONE) && session_start();
$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$fotoUsuario = $_SESSION['foto_usuario'] ?? null;

$todosItens = [
    'perfil'            => ['label' => 'Perfil',          'icon' => 'bi-person',             'url' => './perfil.php',              'niveis' => [0, 1, 2]],
    'dashboard'         => ['label' => 'Dashboard',       'icon' => 'bi-house-door',         'url' => './dashboard.php',           'niveis' => [0, 1]],
    'categorias'        => ['label' => 'Categorias',      'icon' => 'bi-bookmarks',          'url' => './categorias.php',          'niveis' => [0, 1, 2]],
    'chaveamento'       => ['label' => 'Chaveamento',     'icon' => 'bi-diagram-3',          'url' => './chaveamento_arvore.php',  'niveis' => [0, 1]],
    'ranking'           => ['label' => 'Ranking',         'icon' => 'bi-trophy',             'url' => './ranking.php',             'niveis' => [0, 1]],
    'agenda'            => ['label' => 'Agenda',          'icon' => 'bi-calendar2-check',          'url' => './edicao_agenda.php',       'niveis' => [0, 1]],
    'arrecadacoes'      => ['label' => 'Arrecadações',    'icon' => 'bi-basket',             'url' => './edicao_arrecadacao.php',  'niveis' => [0, 1]],
    'colaboradores'     => ['label' => 'Colaboradores',   'icon' => 'bi-people',             'url' => './colaboradores.php',       'niveis' => [0]],
    'chaveamentos_mesario' => ['label' => 'Chaveamentos', 'icon' => 'bi-diagram-3',          'url' => './pontuacao.php',           'niveis' => [2]],
    'agenda_mesario'    => ['label' => 'Agenda',          'icon' => 'bi-calendar3',          'url' => './agenda.php',              'niveis' => [2]],
];

$navItens = [];
foreach ($todosItens as $key => $item) {
    if (in_array($nivelUsuario, $item['niveis'])) {
        $navItens[$key] = $item;
    }
}

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$iconeNav = fn($icon, $key) => $key === $paginaAtiva ? $icon . '-fill' : $icon;
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>
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
<!-- navbar mobile -->
<nav class="d-md-none fixed-bottom py-1 bg-danger shadow-lg">
    <ul class="nav justify-content-around flex-wrap fs-5 list-unstyled mb-0 gap-0 px-1">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1 <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" aria-label="<?= $item['label'] ?>">
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

<!-- navbar desktop -->
<nav class="d-none d-md-flex flex-column position-fixed vh-100 start-0 shadow-lg bg-danger" style="width: 80px; top: 0; z-index: 1040;">
    <ul class="nav flex-column align-items-center justify-content-around h-100 py-4 gap-4 fs-3">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img" alt="Perfil">
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li class="">
            <a href="../../../api/logout.php" class="text-white" <?= $onclickSair ?>>
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>
