<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$navItens = [
    'home'     => ['label' => 'Início',       'icon' => 'bi-house-door-fill',   'url' => './home.php'],
    'inscricao' => ['label' => 'Inscrições',   'icon' => 'bi-people-fill',       'url' => './modalidade.php'],
    'ranking'  => ['label' => 'Ranking',       'icon' => 'bi-trophy-fill',       'url' => './ranking.php'],
    'perfil'   => ['label' => 'Perfil',        'icon' => 'bi-person-gear',      'url' => '../perfil.php'],
    'termos'   => ['label' => 'Termos',        'icon' => 'bi-file-text-fill',    'url' => './termos.php'],
    'sair'     => ['label' => 'Sair',          'icon' => 'bi-box-arrow-right',   'url' => '../../../../api/logout.php'],
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
                <?php if ($key === 'perfil'): ?>
                <img src="" id="perfilImgAluno" class="rounded-circle d-none" width="26" height="26" alt="Foto" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconAluno')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconAluno"' : '' ?>></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
<script>
if (typeof SGI_USER_ID !== 'undefined' && SGI_USER_ID) {
    fetch('/sgi/api/foto.php?user_id=' + SGI_USER_ID)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success && d.url) {
                var img = document.getElementById('perfilImgAluno');
                var icon = document.getElementById('perfilIconAluno');
                if (img && icon) {
                    img.onload = function() { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                    img.onerror = function() { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                    img.src = d.url;
                }
            }
        })
        .catch(function() {});
}
</script>
