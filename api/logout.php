<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Shared\Http\SessionManager;

SessionManager::start();
SessionManager::destroy();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header("Location: ../views/index.php");
exit;
