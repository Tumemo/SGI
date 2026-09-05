<?php
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private_no_expire');
    session_start();
}
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/api/includes/cache_offline.php';
use App\Shared\Http\CsrfGuard;
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
if (!in_array($nivelUsuario, [0, 1, 2], true)) { header('Location: ../../index.php'); exit; }
$tituloPagina = $tituloPagina ?? 'SGI';
// Cache de página por sessão: o PHPSESSID protege a resposta HTTP e uma chave
// opaca por usuário separa os bancos IndexedDB no navegador.
// max-age + stale-while-revalidate permitem navegar offline nas páginas já
// visitadas; os dados dinâmicos continuam via offline-core.js (IndexedDB,
// também separado por sessão).
$chaveCacheOffline = sgi_obter_chave_cache_offline();
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
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/style-utilities.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>window.SGI_SESSION_ID = <?= (int)($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0) ?>; window.SGI_CACHE_KEY = <?= json_encode($chaveCacheOffline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.SGI_CSRF_TOKEN = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.SGI_SESSION_NIVEL = <?= (int)$nivelUsuario ?>; window.SGI_SESSION_INTERCLASSE_ATIVO = <?= (int)($_SESSION['id_interclasse'] ?? 0) ?>;</script>
    <?php if ($nivelUsuario === 2): ?>
    <!-- A camada SPA/offline pertence exclusivamente ao fluxo do mesário. -->
    <script src="../componentes/offline-core.js?v=<?= filemtime(__DIR__ . '/../../componentes/offline-core.js') ?>"></script>
    <script src="../componentes/mesario-data.js?v=<?= filemtime(__DIR__ . '/../../componentes/mesario-data.js') ?>"></script>
    <script src="../componentes/Comandooffline.js?v=<?= filemtime(__DIR__ . '/../../componentes/Comandooffline.js') ?>"></script>
    <script src="../componentes/mesario-offline.js?v=<?= filemtime(__DIR__ . '/../../componentes/mesario-offline.js') ?>"></script>
    <?php endif; ?>
    <!-- Motor híbrido de chaveamento (avança a árvore localmente quando offline). -->
    <script src="../componentes/chaveamento-engine.js?v=<?= @filemtime(__DIR__ . '/../../componentes/chaveamento-engine.js') ?: time() ?>"></script>
    <script src="../componentes/http-client.js?v=<?= filemtime(__DIR__ . '/../../componentes/http-client.js') ?>"></script>
</head>
<body class="bg-light">
