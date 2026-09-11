<?php
(session_status() === PHP_SESSION_NONE) && \App\Shared\Http\SessionManager::start();

$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$termoAceito = $nivelUsuario !== 3 || !empty($_SESSION['termo_aceito']);

// Busca a foto da sessão ou do array $usuarioPerfil (caso esteja definido na página de perfil)
$fotoUsuario = $_SESSION['foto_usuario'] ?? $usuarioPerfil['foto_usuario'] ?? null;
if ($fotoUsuario) {
    $fotoPath = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotosUsuarios' . DIRECTORY_SEPARATOR . $fotoUsuario;
    if (!file_exists($fotoPath)) $fotoUsuario = null;
}
$nomeUsuario = $_SESSION['nome'] ?? $usuarioPerfil['nome_usuario'] ?? 'Usuário';
$inicialNome = mb_strtoupper(mb_substr($nomeUsuario, 0, 1));

// LISTA DE ITENS DO MENU
$navItens = [
    'perfil'  => ['label' => 'Perfil',   'icon' => 'bi-person-gear',    'url' => \App\Shared\Http\Url::to('aluno/perfil')],
    'home'    => ['label' => 'Início',   'icon' => 'bi-house-door',     'url' => \App\Shared\Http\Url::to('aluno/inicio')],
    'jogos'   => ['label' => 'Jogos',    'icon' => 'bi-calendar-event', 'url' => \App\Shared\Http\Url::to('aluno/jogos')],
    'termos'  => ['label' => 'Termos',   'icon' => 'bi-file-text',      'url' => \App\Shared\Http\Url::to('aluno/termos')],
];
if (!$termoAceito) {
    $navItens = ['termos' => $navItens['termos']];
}

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$iconeNav = fn($icon, $key) => $key === $paginaAtiva ? $icon . '-fill' : $icon;
?>

<!-- Estilos para a foto redonda no menu de navegação -->


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

<!-- Navbar Desktop (Barra lateral esquerda) -->
<nav class="d-none d-md-flex flex-column position-fixed start-0 shadow-lg sidebar-nav">
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
        <li>
            <a href="<?= \App\Shared\Http\Url::to('api/v1/logout') ?>" class="text-white" data-sgi-logout title="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>
