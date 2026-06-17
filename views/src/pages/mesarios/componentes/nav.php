<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$navItens = [
    'home'       => ['label' => 'Início',       'icon' => 'bi-house-door-fill',   'url' => './home.php'],
    'pontuacao'  => ['label' => 'Chaveamentos', 'icon' => 'bi-award-fill',        'url' => './pontuacao.php'],
    'perfil'     => ['label' => 'Perfil',        'icon' => 'bi-person-gear',      'url' => './perfil.php'],
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
                <?php if ($key === 'perfil'): ?>
                <img src="" id="perfilImgMes" class="rounded-circle d-none" width="26" height="26" alt="Foto" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconMes')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconMes"' : '' ?>></i>
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
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative" title="<?= $item['label'] ?>" style="<?= $key === 'perfil' ? 'width:44px;height:44px;' : '' ?>" <?= $key === 'sair' ? $onclickSair : '' ?>>
                <?php if ($key === 'perfil'): ?>
                <img src="" id="perfilImgMesDesk" class="rounded-circle object-fit-cover position-absolute d-none" width="36" height="36" alt="Foto" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconMesDesk')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconMesDesk"' : '' ?>></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<script>
fetch('/sgi/api/foto.php?user_id=<?= (int)($_SESSION['id'] ?? 0) ?>')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success && d.foto_usuario) {
            [
                {img:'perfilImgMes', icon:'perfilIconMes'},
                {img:'perfilImgMesDesk', icon:'perfilIconMesDesk'}
            ].forEach(function(pair) {
                var img = document.getElementById(pair.img);
                var icon = document.getElementById(pair.icon);
                if (img && icon) {
                    img.onload = function() { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                    img.onerror = function() { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                    img.src = '/sgi/uploads/fotosUsuarios/' + d.foto_usuario;
                }
            });
        }
    })
    .catch(function() {});
</script>

<style>
    .nav li a:hover {
        opacity: 1 !important;
        transition: 0.3s;
    }
</style>
