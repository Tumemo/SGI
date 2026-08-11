<?php
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    session_start();
}
if ((int)($_SESSION['nivel'] ?? -1) !== 3) { header('Location: ../../index.php'); exit; }
// Cache de página POR USUÁRIO: cada login recebe um PHPSESSID novo
// (session_regenerate_id no login), então Vary: Cookie isola o cache entre
// competidores no mesmo navegador — sem vazamento. max-age +
// stale-while-revalidate permitem navegar offline nas páginas já visitadas;
// os dados dinâmicos continuam via offline-core.js (IndexedDB, por sessão).
if (!headers_sent()) {
    header('Cache-Control: private, max-age=10800, stale-while-revalidate=86400');
    header('Vary: Cookie');
} ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'SGI') ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SGI Aluno Shared Styles -->
    <link rel="stylesheet" href="assets/aluno.css">
    <script>window.SGI_SESSION_ID = <?= (int)($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0) ?>;</script>
    <script src="../../componentes/offline-core.js"></script>
    <script src="../../componentes/Comandooffline.js"></script>
    
    <style>
        body { 
            background-color: #f8f9fa;
            padding-bottom: 70px; /* Margem para menu mobile inferior */
        }

        /* Deslocamento no Desktop para o menu lateral de 80px */
        @media (min-width: 768px) {
            body { 
                padding-bottom: 0;
                margin-left: 80px; /* Evita que o conteúdo fique sob a sidebar */
            }
        }

        .header-banner-container {
            max-height: 180px;
            overflow: hidden;
        }
        .header-banner-img {
            object-fit: cover;
            height: 180px;
        }

        <?= $cssExtra ?? '' ?>
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">