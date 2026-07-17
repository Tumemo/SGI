<?php (session_status() === PHP_SESSION_NONE) && session_start();
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
if (!in_array($nivelUsuario, [0, 1, 2], true)) { header('Location: ../../index.php'); exit; }
$tituloPagina = $tituloPagina ?? 'SGI';
$cssExtra = $cssExtra ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        <?= $cssExtra ?>
    </style>
</head>
<body class="bg-light">
