<?php

declare(strict_types=1);

namespace App\Shared\Http;

/**
 * Proteção central para mutações autenticadas da API.
 *
 * O token é mantido na sessão para continuar válido enquanto a fila offline
 * for sincronizada. O navegador o envia automaticamente pelo http-client.js.
 */
final class CsrfGuard
{
    private const SESSION_KEY = '_sgi_csrf';

    private function __construct()
    {
    }

    public static function token(): string
    {
        SessionManager::start();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function protectCurrentApiMutation(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) || self::isExemptRoute()) {
            return;
        }

        SessionManager::start();
        if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
            // A autorização do endpoint produz a resposta 401 apropriada.
            // CSRF protege uma sessão existente; não substitui autenticação.
            return;
        }

        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '' && !self::isSameOrigin($origin)) {
            self::reject('Origem da requisição não permitida.');
        }

        $provided = trim((string) ($_SERVER['HTTP_X_SGI_CSRF'] ?? ''));
        if ($provided === '' || !hash_equals(self::token(), $provided)) {
            self::reject('Token de segurança inválido ou ausente. Atualize a página e tente novamente.');
        }
    }

    private static function isExemptRoute(): bool
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $normalised = '/' . ltrim($path, '/');
        if (str_ends_with($normalised, '/api/login.php') || str_ends_with($normalised, '/api/v1/login')) {
            return true;
        }

        return str_ends_with($normalised, '/api/usuarios.php')
            && (string) ($_GET['acao'] ?? '') === 'validar_inscricao';
    }

    private static function isSameOrigin(string $origin): bool
    {
        $originParts = parse_url($origin);
        if (!is_array($originParts)) {
            return false;
        }

        $originScheme = strtolower((string) ($originParts['scheme'] ?? ''));
        $originHost = strtolower((string) ($originParts['host'] ?? ''));
        $originPort = isset($originParts['port'])
            ? (int) $originParts['port']
            : self::defaultPort($originScheme);

        $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
        if ($requestScheme === '') {
            $requestScheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                ? 'https'
                : 'http';
        }

        $requestHostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $requestHost = strtolower((string) (parse_url('//' . $requestHostHeader, PHP_URL_HOST) ?: ''));
        $requestPort = parse_url('//' . $requestHostHeader, PHP_URL_PORT);
        $requestPort = $requestPort === null ? self::defaultPort($requestScheme) : (int) $requestPort;

        return $originScheme !== ''
            && $originHost !== ''
            && $requestScheme === $originScheme
            && $requestHost !== ''
            && $requestHost === $originHost
            && $requestPort === $originPort;
    }

    private static function defaultPort(string $scheme): int
    {
        return $scheme === 'https' ? 443 : 80;
    }

    private static function reject(string $message): never
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
