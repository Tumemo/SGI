<?php
$tituloPagina = 'SGI - Notificações';
include 'componentes/head.php';
$titulo = 'Notificações';
$mostrarVoltar = true;
$urlVoltar = './home.php';
include 'componentes/header.php';
?>
    <main class="m-auto py-4" style="width: 90%; margin-bottom: 120px;">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-1">Advertência (-3 pontos)</h5>
                <p class="text-muted mb-0">O aluno demonstrou desrespeito com os colegas e conduta antidesportiva.</p>
            </div>
        </div>
    </main>
<?php
$paginaAtiva = 'home';
include 'componentes/nav.php';
?>
</body>
</html>
