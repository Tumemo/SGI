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
    'perfil'            => ['label' => 'Perfil',          'icon' => 'bi-person',             'url' => './perfil.php',              'niveis' => [0, 1, 2]],
    'dashboard'         => ['label' => 'Dashboard',       'icon' => 'bi-house-door',         'url' => './dashboard.php',           'niveis' => [0, 1, 2]],
    'ocorrencias'        => ['label' => 'Ocorrências',    'icon' => 'bi-exclamation-triangle',          'url' => './ocorrencias.php',          'niveis' => [0, 1, 2]],
    'chaveamento'       => ['label' => 'Chaveamento',     'icon' => 'bi-diagram-3',          'url' => './chaveamento_arvore.php',  'niveis' => [0, 1]],
    'ranking'           => ['label' => 'Ranking',         'icon' => 'bi-trophy',             'url' => './ranking.php',             'niveis' => [0, 1]],
    'agenda'            => ['label' => 'Agenda',          'icon' => 'bi-calendar2-check',          'url' => './edicao_agenda.php',       'niveis' => [0, 1]],
    'arrecadacoes'      => ['label' => 'Arrecadações',    'icon' => 'bi-basket',             'url' => './edicao_arrecadacao.php',  'niveis' => [0, 1]],
    'equipes'     => ['label' => 'Equipes',   'icon' => 'bi-people',             'url' => './edicao_equipes.php',       'niveis' => [0]],
    'chaveamentos_mesario' => ['label' => 'Chaveamentos', 'icon' => 'bi-diagram-3',          'url' => './chaveamento_arvore.php',           'niveis' => [2]],
    'agenda_mesario'    => ['label' => 'Agenda',          'icon' => 'bi-calendar3',          'url' => './edicao_agenda.php',       'niveis' => [2]],
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

<!-- navbar mobile -->
<nav class="d-md-none fixed-bottom bg-danger shadow-lg mobile-nav sgi-inline-cf687978" >
    <ul class="nav justify-content-around flex-nowrap fs-5 list-unstyled mb-0 gap-0 px-1 align-items-center h-100">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1 <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" aria-label="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img-mobile" alt="Perfil">
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

<!-- navbar desktop -->
<nav class="d-none d-md-flex flex-column position-fixed start-0 shadow-lg bg-danger sidebar-nav sgi-inline-2b7964ab" >
    <ul class="nav flex-column align-items-center h-100 py-4 gap-4 fs-3 sidebar-nav-list">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>">
                <?php if ($key === 'perfil' && !empty($fotoUsuario)): ?>
                    <img src="../../../uploads/fotosUsuarios/<?= htmlspecialchars($fotoUsuario) ?>" class="nav-avatar-img" alt="Perfil">
                <?php elseif ($key === 'perfil'): ?>
                    <span class="nav-avatar-fallback"><?= $inicialNome ?></span>
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
