<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Storage\StoragePaths;

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

        self::configureSavePath();
        session_start();
    }

    public static function destroy(): void
    {
        self::start();
        unset($_SESSION['_sgi_csrf']);
    }

    public static function clearAuthentication(): void
    {
        self::start();
        $_SESSION = [];
    }

    private static function configureSavePath(): void
    {
        $configured = getenv('SGI_SESSION_DIR');
        $current = session_save_path();
        $directory = StoragePaths::sessions();

        // Preserve an explicitly configured and writable PHP path (including
        // the isolated directory used by the test bootstrap).
        if ($current !== ''
            && is_dir($current)
            && is_writable($current)
            && (($configured === false || trim($configured) === '')
                || self::sameDirectory($current, $directory))) {
            return;
        }

        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível preparar o diretório de sessões.');
        }

        if (session_save_path($directory) === false) {
            throw new \RuntimeException('Não foi possível configurar o armazenamento de sessões.');
        }
    }

    private static function sameDirectory(string $left, string $right): bool
    {
        $resolvedLeft = realpath($left);
        $resolvedRight = realpath($right);
        if ($resolvedLeft === false || $resolvedRight === false) {
            return false;
        }

        return DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($resolvedLeft, $resolvedRight) === 0
            : $resolvedLeft === $resolvedRight;
    }
}
