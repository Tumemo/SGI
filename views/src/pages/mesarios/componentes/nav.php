<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$navItens = [
    'home'       => ['label' => 'Início',       'icon' => 'bi-house-door-fill',   'url' => './home.php'],
    'jogos'      => ['label' => 'Jogos',        'icon' => 'bi-play-circle-fill',  'url' => './jogos.php'],
    'pontuacao'  => ['label' => 'Chaveamentos', 'icon' => 'bi-award-fill',        'url' => './pontuacao.php'],
    'sair'       => ['label' => 'Sair',         'icon' => 'bi-box-arrow-right',   'url' => '../../../../api/logout.php'],
];
$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>

<!-- navbar mobile -->
<nav class="d-md-none fixed-bottom py-2 bg-danger shadow-lg">
    <ul class="nav justify-content-around fs-2 list-unstyled mb-0">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-2" aria-label="<?= $item['label'] ?>" <?= $key === 'sair' ? $onclickSair : '' ?>>
                <i class="bi <?= $item['icon'] ?>"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- navbar desktop -->
<nav class="d-none d-md-flex flex-column position-fixed vh-100 start-0 shadow-lg bg-danger" style="width: 80px; z-index: 1040;">
    <ul class="nav flex-column align-items-center justify-content-around h-100 py-4 gap-4 fs-3">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white" title="<?= $item['label'] ?>" <?= $key === 'sair' ? $onclickSair : '' ?>>
                <i class="bi <?= $item['icon'] ?>"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<style>
    .nav li a:hover {
        opacity: 1 !important;
        transition: 0.3s;
    }
</style>
