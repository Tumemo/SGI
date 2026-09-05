<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class AccessGuard
{
    /**
     * @param list<int> $levels
     */
    public static function authorize(array $levels): ?Response
    {
        SessionManager::start();

        if (!isset($_SESSION['nivel'])) {
            return Response::json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], 401);
        }

        if (!in_array((int) $_SESSION['nivel'], $levels, true)) {
            return Response::json([
                'success' => false,
                'message' => 'Acesso não autorizado para este nível.',
            ], 403);
        }

        return null;
    }

    public static function requireWrite(): ?Response
    {
        return self::authorize([0, 1]);
    }
}
