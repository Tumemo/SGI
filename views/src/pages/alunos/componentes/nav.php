<?php
$paginaAtiva = $paginaAtiva ?? 'home';
$navItens = [
    'home'    => ['label' => 'Início',   'icon' => 'bi-house-door-fill',   'url' => './home.php'],
    'ranking' => ['label' => 'Ranking',  'icon' => 'bi-trophy-fill',       'url' => './ranking.php'],
    'perfil'  => ['label' => 'Perfil',   'icon' => 'bi-person-gear',      'url' => './perfil.php'],
    'termos'  => ['label' => 'Termos',   'icon' => 'bi-file-text-fill',    'url' => './termos.php'],
    'sair'    => ['label' => 'Sair',     'icon' => 'bi-box-arrow-right',   'url' => '../../../../api/logout.php'],
];
?>

<!-- NAVEGAÇÃO PRINCIPAL -->
<nav class="bg-danger shadow-lg border-top border-white border-opacity-25 
            fixed-bottom d-lg-none py-2" aria-label="Navegação mobile">
    <!-- LAYOUT MOBILE (Rodapé) -->
    <ul class="nav justify-content-around fs-2 list-unstyled mb-0">
        <?php foreach ($navItens as $key => $item): 
            $classe = $key === $paginaAtiva ? 'text-white' : 'text-white-50';
            $onclick = $key === 'sair' ? "onclick=\"return confirm('Deseja realmente sair?')\"" : '';
        ?>
        <li>
            <a href="<?= $item['url'] ?>" class="<?= $classe ?> nav-link p-2 d-flex flex-column align-items-center" aria-label="<?= $item['label'] ?>" <?= $onclick ?>>
                <?php if ($key === 'perfil'): ?>
                    <img src="" id="perfilImgAlunoMobile" class="rounded-circle d-none" width="28" height="28" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconAlunoMobile')?.classList.remove('d-none')">
                <?php endif; ?>
                <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconAlunoMobile"' : '' ?>></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- LAYOUT DESKTOP (Topo Superior) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm d-none d-lg-block sticky-top" aria-label="Navegação desktop">
    <div class="container">
        <a class="navbar-brand fw-bold" href="./home.php">
            <i class="bi bi-mortarboard-fill me-2"></i>SGI Aluno
        </a>
        
        <div class="collapse navbar-collapse" id="navbarDesktop">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2">
                <?php foreach ($navItens as $key => $item): 
                    $ativo = $key === $paginaAtiva ? 'active fw-bold border-bottom border-2 border-white' : '';
                    $onclick = $key === 'sair' ? "onclick=\"return confirm('Deseja realmente sair?')\"" : '';
                ?>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center gap-2 px-3 <?= $ativo ?>" href="<?= $item['url'] ?>" <?= $onclick ?>>
                        <?php if ($key === 'perfil'): ?>
                            <img src="" id="perfilImgAlunoDesktop" class="rounded-circle d-none" width="24" height="24" style="object-fit: cover;" onerror="this.classList.add('d-none');document.getElementById('perfilIconAlunoDesktop')?.classList.remove('d-none')">
                        <?php endif; ?>
                        <i class="bi <?= $item['icon'] ?>"<?= $key === 'perfil' ? ' id="perfilIconAlunoDesktop"' : '' ?>></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
// Carregamento dinâmico da imagem do perfil em ambas as versões
fetch('../../../../api/foto.php?user_id=<?= (int)($_SESSION['id'] ?? 0) ?>')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success && d.foto_usuario) {
            var fotoUrl = '../../../../uploads/fotosUsuarios/' + d.foto_usuario;
            
            // Atualiza imagem no mobile
            var imgM = document.getElementById('perfilImgAlunoMobile');
            var iconM = document.getElementById('perfilIconAlunoMobile');
            if (imgM && iconM) {
                imgM.onload = function() { imgM.classList.remove('d-none'); iconM.classList.add('d-none'); };
                imgM.src = fotoUrl;
            }

            // Atualiza imagem no desktop
            var imgD = document.getElementById('perfilImgAlunoDesktop');
            var iconD = document.getElementById('perfilIconAlunoDesktop');
            if (imgD && iconD) {
                imgD.onload = function() { imgD.classList.remove('d-none'); iconD.classList.add('d-none'); };
                imgD.src = fotoUrl;
            }
        }
    })
    .catch(function() {});
</script>