<?php

declare(strict_types=1);

namespace App\Shared\Storage;

use App\Shared\Config\Env;

final class StoragePaths
{
    private function __construct()
    {
    }

    public static function imports(): string
    {
        return self::normalizar(Env::get('SGI_IMPORT_DIR', self::projectRoot() . '/storage/imports'));
    }

    public static function turmaPdfs(): string
    {
        $default = self::projectRoot()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'turmas';

        return self::normalizar(Env::get('SGI_UPLOAD_DIR', $default));
    }

    public static function regulamentos(): string
    {
        $default = self::projectRoot()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'regulamentos';

        return self::normalizar(Env::get('SGI_REGULAMENTOS_DIR', $default));
    }

    public static function fotosUsuarios(): string
    {
        $default = self::projectRoot()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'fotos';

        return self::normalizar(Env::get('SGI_FOTOS_DIR', $default));
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function normalizar(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // Caminhos relativos do .env são relativos à raiz do projeto, nunca
        // ao diretório corrente (que pode ser alterado pelo front controller
        // antes de incluir um endpoint legado).
        $absolute = str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
        if (!$absolute) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $path = self::projectRoot() . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim($path, '/\\');
    }
}
