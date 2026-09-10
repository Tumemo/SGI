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
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>

<!-- Estilos para a foto redonda no menu de navegação -->


<!-- Navbar Mobile (Fixo na parte inferior) -->
<nav class="d-md-none fixed-bottom bg-danger shadow-lg mobile-nav sgi-u-z-1040-h-64px" >
    <ul class="nav justify-content-around flex-nowrap fs-5 list-unstyled mb-0 gap-0 px-1 align-items-center h-100">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1 d-flex align-items-center justify-content-center <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" aria-label="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="<?= htmlspecialchars(\App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($fotoUsuario))) ?>" class="nav-avatar-img-mobile" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback-mobile"><?= $inicialNome ?></span>
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <a href="<?= \App\Shared\Http\Url::to('api/v1/logout') ?>" class="text-white-50 nav-link p-1" aria-label="Sair" data-sgi-logout <?= $onclickSair ?>>
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
<nav class="d-none d-md-flex flex-column position-fixed start-0 shadow-lg sidebar-nav sgi-u-w-80px-top-0-bottom-0-2" >
    <ul class="nav flex-column align-items-center h-100 py-4 gap-4 fs-3 sidebar-nav-list">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="<?= htmlspecialchars(\App\Shared\Http\Url::to('uploads/fotosUsuarios/' . rawurlencode($fotoUsuario))) ?>" class="nav-avatar-img" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback"><?= $inicialNome ?></span>
                <?php else: ?>
                    <i class="bi <?= $iconeNav($item['icon'], $key) ?>"></i>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <a href="<?= \App\Shared\Http\Url::to('api/v1/logout') ?>" class="text-white" data-sgi-logout <?= $onclickSair ?> title="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>
