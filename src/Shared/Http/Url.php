<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class Url
{
    private static string $basePath = '';

    public static function configure(string $basePath): void
    {
        self::$basePath = '/' . trim($basePath, '/');
        if (self::$basePath === '/') {
            self::$basePath = '';
        }
    }

    public static function to(string $path): string
    {
        return self::$basePath . '/' . ltrim($path, '/');
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }
}
