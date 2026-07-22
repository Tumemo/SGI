<?php (session_status() === PHP_SESSION_NONE) && session_start();
if ((int)($_SESSION['nivel'] ?? -1) !== 3) { header('Location: ../../index.php'); exit; } ?>
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