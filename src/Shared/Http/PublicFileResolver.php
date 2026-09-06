<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class PublicFileResolver
{
    public function resolve(string $relativePath, string $directory): ?string
    {
        if (str_contains($relativePath, "\0") || str_contains($relativePath, '\\') || in_array('..', explode('/', $relativePath), true)) {
            return null;
        }
        $root = realpath($directory);
        $file = realpath($directory . DIRECTORY_SEPARATOR . $relativePath);
        if ($root === false || $file === false || !is_file($file)) {
            return null;
        }
        return str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) ? $file : null;
    }
}
