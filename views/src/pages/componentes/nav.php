<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);

$todosItens = [
    'perfil'        => ['label' => 'Perfil',       'icon' => 'bi-person-gear',           'url' => './perfil.php',        'niveis' => [0, 1, 2]],
    'dashboard'     => ['label' => 'Dashboard',    'icon' => 'bi-house-door-fill',   'url' => './dashboard.php',     'niveis' => [0, 1]],
    'categorias'    => ['label' => 'Categorias',   'icon' => 'bi-bookmarks',         'url' => './categorias.php',    'niveis' => [0, 1, 2]],
    'equipes'       => ['label' => 'Equipes',      'icon' => 'bi-diagram-3',         'url' => './edicao_equipes.php',       'niveis' => [0, 1]],
    'modalidades'   => ['label' => 'Modalidades',  'icon' => 'bi-trophy',            'url' => './modalidades.php',   'niveis' => [0, 1]],
    'pontuacoes'    => ['label' => 'Pontuações',   'icon' => 'bi-award',             'url' => './pontuacoes.php',    'niveis' => [0, 1]],
    'arrecadacoes'  => ['label' => 'Arrecadações', 'icon' => 'bi-basket',            'url' => './edicao_arrecadacao.php',  'niveis' => [0, 1]],
    'colaboradores' => ['label' => 'Colaboradores','icon' => 'bi-people',            'url' => './colaboradores.php', 'niveis' => [0]],
    'agenda'        => ['label' => 'Agenda',       'icon' => 'bi-calendar3',         'url' => './agenda.php',        'niveis' => [2]],
    'chaveamentos'  => ['label' => 'Chaveamentos', 'icon' => 'bi-diagram-3-fill',    'url' => './pontuacao.php',     'niveis' => [2]],
];

$navItens = [];
foreach ($todosItens as $key => $item) {
    if (in_array($nivelUsuario, $item['niveis'])) {
        $navItens[$key] = $item;
    }
}

$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>
<!-- navbar mobile -->
<nav class="d-md-none fixed-bottom py-1 bg-danger shadow-lg">
    <ul class="nav justify-content-around flex-wrap fs-5 list-unstyled mb-0 gap-0 px-1">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1" aria-label="<?= $item['label'] ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
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
                <i class="bi <?= $item['icon'] ?>"></i>
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

<style>
    .nav li a:hover {
        opacity: 1 !important;
        transition: 0.3s;
    }
    .active-nav-icon {
        opacity: 1 !important;
        filter: drop-shadow(0 0 4px rgba(255,255,255,0.5));
    }
</style>
