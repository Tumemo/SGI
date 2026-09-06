<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class Assets
{
    public static function url(string $path): string
    {
        $file = dirname(__DIR__, 3) . '/public/assets/' . $path;
        $version = is_file($file) ? substr((string) hash_file('sha256', $file), 0, 12) : 'missing';
        return Url::to('assets/' . $path) . '?v=' . $version;
    }
}
