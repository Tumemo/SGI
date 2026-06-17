<?php
$paginaAtiva = $paginaAtiva ?? 'dashboard';
$navItens = [
    'perfil'        => ['label' => 'Perfil',        'icon' => 'bi-person-gear',      'url' => './perfil.php'],
    'dashboard'     => ['label' => 'Dashboard',    'icon' => 'bi-house-door-fill',   'url' => './dashboard.php'],
    'categorias'    => ['label' => 'Categorias',   'icon' => 'bi-bookmarks',         'url' => './categorias.php'],
    'equipes'       => ['label' => 'Equipes',      'icon' => 'bi-diagram-3',         'url' => './equipes.php'],
    'modalidades'   => ['label' => 'Modalidades',  'icon' => 'bi-trophy',            'url' => './modalidades.php'],
    'pontuacoes'    => ['label' => 'Pontuações',   'icon' => 'bi-award',             'url' => './pontuacoes.php'],
    'colaboradores' => ['label' => 'Colaboradores','icon' => 'bi-people',            'url' => './colaboradores.php'],
    'sair'          => ['label' => 'Sair',         'icon' => 'bi-box-arrow-right',   'url' => '../../../../api/logout.php'],
];
$classeLink = fn($key) => $key === $paginaAtiva ? 'text-white fw-bold' : 'text-white-50';
$onclickSair = "onclick=\"return confirm('Deseja realmente sair?')\"";
?>

<!-- navbar mobile -->
<nav class="d-md-none fixed-bottom py-1 bg-danger shadow-lg">
    <ul class="nav justify-content-around flex-wrap fs-5 list-unstyled mb-0 gap-0 px-1">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classeLink($key) ?> nav-link p-1" aria-label="<?= $item['label'] ?>" <?= $key === 'sair' ? $onclickSair : '' ?>>
                <?php if ($key === 'perfil'): ?>
                <img src="" id="perfilImgCol" class="rounded-circle d-none" width="22" height="22" alt="Foto" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconCol')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconCol"' : '' ?>></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- navbar desktop -->
<nav class="d-none d-md-flex flex-column position-fixed vh-100 start-0 shadow-lg bg-danger" style="width: 80px; top: 0; z-index: 1040;">
    <ul class="nav flex-column align-items-center justify-content-around h-100 py-4 gap-4 fs-3">
        <?php foreach ($navItens as $key => $item): ?>
        <li>
            <a href="<?= $item['url'] ?>" class="text-white d-flex align-items-center justify-content-center position-relative <?= $key === $paginaAtiva ? 'active-nav-icon' : '' ?>" title="<?= $item['label'] ?>" style="<?= $key === 'perfil' ? 'width:44px;height:44px;' : '' ?>" <?= $key === 'sair' ? $onclickSair : '' ?>>
                <?php if ($key === 'perfil'): ?>
                <img src="" id="perfilImgColDesk" class="rounded-circle object-fit-cover position-absolute d-none" width="36" height="36" alt="Foto" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconColDesk')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconColDesk"' : '' ?>></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<script>
if (typeof SGI_USER_ID !== 'undefined' && SGI_USER_ID) {
    fetch('../../../../api/foto.php?user_id=' + SGI_USER_ID)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success && d.foto_usuario) {
                [
                    {img:'perfilImgCol', icon:'perfilIconCol'},
                    {img:'perfilImgColDesk', icon:'perfilIconColDesk'}
                ].forEach(function(pair) {
                    var img = document.getElementById(pair.img);
                    var icon = document.getElementById(pair.icon);
                    if (img && icon) {
                        img.onload = function() { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                        img.onerror = function() { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                        img.src = '../../../../uploads/fotosUsuarios/' + d.foto_usuario;
                    }
                });
            }
        })
        .catch(function() {});
}
</script>

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
