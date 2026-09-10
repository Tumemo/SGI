<?php

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    \App\Shared\Http\SessionManager::start();
}
require_once SGI_ROOT . '/bootstrap/autoload.php';
use App\Shared\Http\CsrfGuard;
if ((int) ($_SESSION['nivel'] ?? -1) !== 3) {
    header('Location: ' . \App\Shared\Http\Url::to('aluno/login'));
    exit;
}
// O cache offline é controlado pela camada explícita de dados. A resposta HTML
// do portal não deve reaparecer pelo cache HTTP após logout.
$chaveCacheOffline = \App\Modules\Acesso\Presentation\Http\OfflineSession::obterChaveCacheOffline();
$csrfToken = CsrfGuard::token();
if (!headers_sent()) {
    header('Cache-Control: private, no-store, max-age=0');
    header('Vary: Cookie');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include SGI_ROOT . '/resources/views/components/page-title.php'; ?>
    <!-- Bootstrap tematizado e folha compartilhada -->
    <link href="<?= \App\Shared\Http\Assets::url('css/bootstrap-theme.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('css/shared.css') ?>">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <!-- Folha consolidada do portal do aluno -->
    <link rel="stylesheet" href="<?= \App\Shared\Http\Assets::url('css/aluno.css') ?>">
    <script>window.SGI_SESSION_ID = <?php
echo (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
?>; window.SGI_CACHE_KEY = <?php
echo json_encode($chaveCacheOffline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_CSRF_TOKEN = <?php
echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_BASE_PATH = <?php
echo json_encode(\App\Shared\Http\Url::basePath(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_API_BASE = <?php
echo json_encode(\App\Shared\Http\Url::to('api/v1/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>; window.SGI_ASSET_BASE = <?php
echo json_encode(\App\Shared\Http\Url::to('assets'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;</script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-core.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/offline/offline-form.js') ?>"></script>
    <script src="<?= \App\Shared\Http\Assets::url('js/shared/http-client.js') ?>"></script>

<script src="<?= \App\Shared\Http\Assets::url('js/shared/page-runtime.js') ?>"></script>
<script>
(function () {
    document.addEventListener('click', function (event) {
        var link = event.target.closest && event.target.closest('[data-sgi-logout]');
        if (!link) return;
        if (event.defaultPrevented) return;
        event.preventDefault();
        fetch(link.href, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-SGI-CSRF': window.SGI_CSRF_TOKEN || ''}
        }).finally(function () {
            window.location.href = <?= json_encode(\App\Shared\Http\Url::to('aluno/login')) ?>;
        });
    });
})();
</script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
