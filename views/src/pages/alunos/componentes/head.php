<?php (session_status() === PHP_SESSION_NONE) && session_start();
if ((int)($_SESSION['nivel'] ?? -1) !== 3) { header('Location: /sgi/views/index.php'); exit; } ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'SGI') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { padding-bottom: 90px; background-color: #f8f9fa; }
        <?= $cssExtra ?? '' ?>
    </style>
</head>
<body class="bg-light">

