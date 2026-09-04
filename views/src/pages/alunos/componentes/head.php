<?php
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    session_start();
}
require_once dirname(__DIR__, 5) . '/api/includes/cache_offline.php';
if ((int)($_SESSION['nivel'] ?? -1) !== 3) { header('Location: ../../../index.php'); exit; }
// Cache de página por sessão: o PHPSESSID protege a resposta HTTP e uma chave
// opaca por usuário separa os bancos IndexedDB no navegador. max-age +
// stale-while-revalidate permitem navegar offline nas páginas já visitadas;
// os dados dinâmicos continuam via offline-core.js (IndexedDB, por sessão).
$chaveCacheOffline = sgi_obter_chave_cache_offline();
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
    <link rel="stylesheet" href="../../styles/style-migrated.css">
    <link rel="stylesheet" href="assets/aluno-page.css">
    <script>window.SGI_SESSION_ID = <?= (int)($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0) ?>; window.SGI_CACHE_KEY = <?= json_encode($chaveCacheOffline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="../../componentes/offline-core.js?v=<?= filemtime(dirname(__DIR__, 3) . '/componentes/offline-core.js') ?>"></script>
    <script src="../../componentes/Comandooffline.js?v=<?= filemtime(dirname(__DIR__, 3) . '/componentes/Comandooffline.js') ?>"></script>
    
</head>
<body class="bg-light d-flex flex-column min-vh-100">
