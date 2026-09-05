<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class SessionManager
{
    private function __construct()
    {
    }

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
    }

    public static function destroy(): void
    {
        self::start();
        unset($_SESSION['_sgi_csrf']);
    }
}
