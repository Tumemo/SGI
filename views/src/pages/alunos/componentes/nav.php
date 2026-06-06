<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$navItens = [
    'home'     => ['label' => 'Início',       'icon' => 'bi-house-door-fill',   'url' => './home.php'],
    'inscricao' => ['label' => 'Inscrições',   'icon' => 'bi-people-fill',       'url' => './inscricao.php'],
    'ranking'  => ['label' => 'Ranking',       'icon' => 'bi-trophy-fill',       'url' => './ranking.php'],
    'sair'     => ['label' => 'Sair',          'icon' => 'bi-box-arrow-right',   'url' => './login.php'],
];
?>
<nav class="fixed-bottom py-2 bg-danger shadow-lg" aria-label="Navegação principal inferior">
    <ul class="nav justify-content-around fs-2 list-unstyled mb-0">
        <?php foreach ($navItens as $key => $item): ?>
        <?php
            $classe = $key === $paginaAtiva ? 'text-white' : 'text-white-50';
            $onclick = $key === 'sair' ? "onclick=\"return confirm('Deseja realmente sair?')\"" : '';
        ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classe ?> nav-link p-2" aria-label="<?= $item['label'] ?>" <?= $onclick ?>>
                <i class="bi <?= $item['icon'] ?>"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
