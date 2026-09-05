<?php

declare(strict_types=1);

namespace App\Shared\Config;

/**
 * Acesso único às configurações fornecidas pelo ambiente.
 *
 * A aplicação continua funcionando em instalações XAMPP legadas por meio dos
 * valores padrão, mas qualquer ambiente novo deve fornecer suas credenciais
 * via variáveis de ambiente (ou pelo servidor web).
 */
final class Env
{
    private function __construct()
    {
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }

        return $default;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            throw new \RuntimeException(sprintf('Configuração obrigatória ausente: %s', $key));
        }

        return $value;
    }
}
