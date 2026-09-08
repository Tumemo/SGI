<?php

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    \App\Shared\Http\SessionManager::start();
}
require_once SGI_ROOT . '/bootstrap/autoload.php';
use App\Shared\Http\CsrfGuard;
$nivelUsuario = (int) ($_SESSION['nivel'] ?? -1);
if (!in_array($nivelUsuario, [0, 1, 2], true)) {
    header('Location: ../../index.php');
    exit;
}
$tituloPagina = $tituloPagina ?? 'SGI';
// Cache de página por sessão: o PHPSESSID protege a resposta HTTP e uma chave
// opaca por usuário separa os bancos IndexedDB no navegador.
// max-age + stale-while-revalidate permitem navegar offline nas páginas já
// visitadas; os dados dinâmicos continuam via offline-core.js (IndexedDB,
// também separado por sessão).
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
echo htmlspecialchars($tituloPagina);
?></title>
    <link href="<?= \App\Shared\Http\Assets::url('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('vendor/fontawesome/css/all.min.css') ?>" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/style-utilities.css">
    <script src="<?= \App\Shared\Http\Assets::url('vendor/axios/axios.min.js') ?>"></script>
    <script>window.SGI_SESSION_ID = <?php
echo (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
?>; window.SGI_CACHE_KEY = <?php
echo json_encode($chaveCacheOffline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_CSRF_TOKEN = <?php
echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_SESSION_NIVEL = <?php
echo (int) $nivelUsuario;
?>; window.SGI_SESSION_INTERCLASSE_ATIVO = <?php
echo (int) ($_SESSION['id_interclasse'] ?? 0);
?>;</script>
    <?php
if ($nivelUsuario === 2) {
    ?>
    <!-- A camada SPA/offline pertence exclusivamente ao fluxo do mesário. -->
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-core.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/mesario-data.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-form.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/mesario-offline.js') ?>"></script>
    <?php
}
?>
    <!-- Motor híbrido de chaveamento (avança a árvore localmente quando offline). -->
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/chaveamento-engine.js') ?>"></script>
<script src="<?= \App\Shared\Http\Assets::url('js/shared/http-client.js') ?>"></script>
<script src="<?= \App\Shared\Http\Assets::url('js/shared/cronometro.js') ?>"></script>
<script src="<?= \App\Shared\Http\Assets::url('js/shared/page-runtime.js') ?>"></script>
</head>
<body class="bg-light">
