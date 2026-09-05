<?php

declare(strict_types=1);

namespace App\Shared\Config;

final class EnvLoader
{
    private function __construct()
    {
    }

    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches) !== 1) {
                continue;
            }
            $key = $matches[1];
            if (getenv($key) !== false) {
                continue;
            }
            $value = trim($matches[2]);
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}
