<?php

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    \App\Shared\Http\SessionManager::start();
}
require_once SGI_ROOT . '/bootstrap/autoload.php';
use App\Shared\Http\CsrfGuard;
if ((int) ($_SESSION['nivel'] ?? -1) !== 3) {
    header('Location: ../../../index.php');
    exit;
}
// Cache de página por sessão: o PHPSESSID protege a resposta HTTP e uma chave
// opaca por usuário separa os bancos IndexedDB no navegador. max-age +
// stale-while-revalidate permitem navegar offline nas páginas já visitadas;
// os dados dinâmicos continuam via offline-core.js (IndexedDB, por sessão).
$chaveCacheOffline = \App\Modules\Acesso\Presentation\Http\OfflineSession::obterChaveCacheOffline();
$csrfToken = CsrfGuard::token();
if (!headers_sent()) {
    header('Cache-Control: private, max-age=10800, stale-while-revalidate=86400');
    header('Vary: Cookie');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php
echo htmlspecialchars($tituloPagina ?? 'SGI');
?></title>
    <!-- Bootstrap CSS -->
    <link href="<?= \App\Shared\Http\Assets::url('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <!-- SGI Aluno Shared Styles -->
    <link rel="stylesheet" href="assets/aluno.css">
    <link rel="stylesheet" href="../../styles/style-migrated.css">
    <link rel="stylesheet" href="assets/aluno-page.css">
    <script>window.SGI_SESSION_ID = <?php
echo (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
?>; window.SGI_CACHE_KEY = <?php
echo json_encode($chaveCacheOffline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_CSRF_TOKEN = <?php
echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;</script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-core.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-form.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/shared/http-client.js') ?>"></script>

<script src="<?= \App\Shared\Http\Assets::url('js/shared/page-runtime.js') ?>"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
