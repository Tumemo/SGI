<?php
(session_status() === PHP_SESSION_NONE) && \App\Shared\Http\SessionManager::start();
$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$fotoUsuario = $_SESSION['foto_usuario'] ?? null;
if ($fotoUsuario) {
    $fotoPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios' . DIRECTORY_SEPARATOR . $fotoUsuario;
    if (!file_exists($fotoPath)) $fotoUsuario = null;
}
$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$inicialNome = strtoupper(substr($nomeUsuario, 0, 1));

$todosItens = [
    'perfil'            => ['label' => 'Perfil',          'icon' => 'bi-person',             'url' => \App\Shared\Http\Url::to('perfil'),              'niveis' => [0, 1, 2]],
    'dashboard'         => ['label' => 'Dashboard',       'icon' => 'bi-house-door',         'url' => \App\Shared\Http\Url::to('painel'),           'niveis' => [0, 1, 2]],
    'ocorrencias'        => ['label' => 'Ocorrências',    'icon' => 'bi-exclamation-triangle',          'url' => \App\Shared\Http\Url::to('ocorrencias'),          'niveis' => [0, 1, 2]],
    'chaveamento'       => ['label' => 'Chaveamento',     'icon' => 'bi-diagram-3',          'url' => \App\Shared\Http\Url::to('chaveamento'),  'niveis' => [0, 1]],
    'ranking'           => ['label' => 'Ranking',         'icon' => 'bi-trophy',             'url' => \App\Shared\Http\Url::to('ranking'),             'niveis' => [0, 1]],
    'agenda'            => ['label' => 'Agenda',          'icon' => 'bi-calendar2-check',          'url' => \App\Shared\Http\Url::to('edicoes/agenda'),       'niveis' => [0, 1]],
    'arrecadacoes'      => ['label' => 'Arrecadações',    'icon' => 'bi-basket',             'url' => \App\Shared\Http\Url::to('edicoes/arrecadacao'),  'niveis' => [0, 1]],
    'equipes'     => ['label' => 'Equipes',   'icon' => 'bi-people',             'url' => \App\Shared\Http\Url::to('edicoes/equipes'),       'niveis' => [0]],
    'chaveamentos_mesario' => ['label' => 'Chaveamentos', 'icon' => 'bi-diagram-3',          'url' => \App\Shared\Http\Url::to('chaveamento'),           'niveis' => [2]],
    'agenda_mesario'    => ['label' => 'Agenda',          'icon' => 'bi-calendar3',          'url' => \App\Shared\Http\Url::to('edicoes/agenda'),       'niveis' => [2]],
];

$navItens = [];
foreach ($todosItens as $key => $item) {
    if (in_array($nivelUsuario, $item['niveis'])) {
        $navItens[$key] = $item;
    }
}

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$iconeNav = fn($icon, $key) => $key === $paginaAtiva ? $icon . '-fill' : $icon;
?>

<!-- Menu compacto: o Bootstrap controla foco, backdrop e fechamento do offcanvas. -->
<button type="button" class="d-md-none sgi-mobile-menu-trigger btn btn-primary shadow d-inline-flex align-items-center justify-content-center" data-bs-toggle="offcanvas" data-bs-target="#sgiMobileMenu" aria-controls="sgiMobileMenu" aria-label="Abrir menu">
    <i class="bi bi-list fs-4" aria-hidden="true"></i>
</button>
<div class="offcanvas offcanvas-start sgi-mobile-menu" tabindex="-1" id="sgiMobileMenu" aria-labelledby="sgiMobileMenuLabel">
    <div class="offcanvas-header px-3 py-3 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-grid-1x2-fill text-primary" aria-hidden="true"></i>
            <h2 class="offcanvas-title h5 mb-0" id="sgiMobileMenuLabel">Menu</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar menu"></button>
    </div>
    <div class="offcanvas-body p-2">
        <nav aria-label="Navegação principal">
            <ul class="nav flex-column gap-1">
                <?php foreach ($navItens as $key => $item): ?>
                <li class="nav-item">
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="sgi-mobile-menu-link d-flex align-items-center gap-3 rounded-3 px-3 py-2 <?= $key === $paginaAtiva ? 'bg-primary text-white' : 'text-body' ?>" <?= $key === $paginaAtiva ? 'aria-current="page"' : '' ?>>
                        <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                            <img src="<?= htmlspecialchars(\App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($fotoUsuario))) ?>" class="nav-avatar-img-mobile object-fit-cover rounded-circle border border-2 border-white" alt="Perfil">
                        <?php elseif ($key === 'perfil'): ?>
                            <span class="nav-avatar-fallback-mobile d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-danger fw-semibold border border-2 border-white small"><?= htmlspecialchars($inicialNome, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <i class="bi <?= $iconeNav($item['icon'], $key) ?> fs-5" aria-hidden="true"></i>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item mt-2 pt-2 border-top">
                    <a href="<?= \App\Shared\Http\Url::to('api/v1/logout') ?>" class="sgi-mobile-menu-link d-flex align-items-center gap-3 rounded-3 px-3 py-2 text-danger" data-sgi-logout>
                        <i class="bi bi-box-arrow-right fs-5" aria-hidden="true"></i>
                        <span>Sair</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- navbar desktop -->
<nav class="d-none d-md-flex flex-column position-fixed start-0 shadow-lg bg-primary sidebar-nav">
    <ul class="nav flex-column align-items-center h-100 py-4 gap-4 fs-3 sidebar-nav-list">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="<?= htmlspecialchars(\App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($fotoUsuario))) ?>" class="nav-avatar-img object-fit-cover rounded-circle border border-2 border-white" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-danger fw-semibold border border-2 border-white small"><?= htmlspecialchars($inicialNome, ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li class="">
            <a href="<?= \App\Shared\Http\Url::to('api/v1/logout') ?>" class="text-white" data-sgi-logout>
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>
