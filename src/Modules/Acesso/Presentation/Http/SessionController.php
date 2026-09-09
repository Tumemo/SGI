<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Http\SessionManager;
use App\Shared\Http\Url;

final class SessionController
{
    public function show(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        return Response::json(['success' => true, 'usuario' => [
            'id' => (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
            'nome' => (string) ($_SESSION['nome'] ?? ''),
            'nivel' => (int) ($_SESSION['nivel'] ?? -1),
        ]], 200, ['Cache-Control' => 'no-store']);
    }

    public function logout(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return Response::json([
                'success' => false,
                'message' => 'Logout exige uma requisição POST.',
            ], 405, [
                'Allow' => 'POST',
                'Cache-Control' => 'no-store',
            ]);
        }

        SessionManager::start();
        SessionManager::destroy();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'], 'domain' => $params['domain'],
                'secure' => $params['secure'], 'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }
        session_destroy();
        return Response::empty(302, ['Location' => Url::to('login'), 'Cache-Control' => 'no-store']);
    }
}
