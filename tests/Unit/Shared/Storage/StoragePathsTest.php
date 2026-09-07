<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Storage;

use App\Shared\Storage\StoragePaths;
use PHPUnit\Framework\TestCase;

final class StoragePathsTest extends TestCase
{
    public function testUsesPrivateStorageDirectoriesByDefault(): void
    {
        $variables = ['SGI_UPLOAD_DIR', 'SGI_REGULAMENTOS_DIR', 'SGI_FOTOS_DIR'];
        $previousValues = [];

        foreach ($variables as $variable) {
            $previousValues[$variable] = getenv($variable);
            putenv($variable);
        }

        $root = dirname(__DIR__, 4);

        try {
            self::assertSame($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'turmas', StoragePaths::turmaPdfs());
            self::assertSame($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'regulamentos', StoragePaths::regulamentos());
            self::assertSame($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fotos', StoragePaths::fotosUsuarios());
        } finally {
            foreach ($previousValues as $variable => $value) {
                self::restoreEnvironment($variable, $value);
            }
        }
    }

    public function testUsesTheConfiguredDirectories(): void
    {
        $oldPdf = getenv('SGI_UPLOAD_DIR');
        $oldRegulamentos = getenv('SGI_REGULAMENTOS_DIR');
        $oldFotos = getenv('SGI_FOTOS_DIR');

        putenv('SGI_UPLOAD_DIR=C:/tmp/sgi-pdfs/');
        putenv('SGI_REGULAMENTOS_DIR=C:/tmp/sgi-regulamentos/');
        putenv('SGI_FOTOS_DIR=C:/tmp/sgi-fotos/');

        self::assertSame('C:/tmp/sgi-pdfs', StoragePaths::turmaPdfs());
        self::assertSame('C:/tmp/sgi-regulamentos', StoragePaths::regulamentos());
        self::assertSame('C:/tmp/sgi-fotos', StoragePaths::fotosUsuarios());

        self::restoreEnvironment('SGI_UPLOAD_DIR', $oldPdf);
        self::restoreEnvironment('SGI_REGULAMENTOS_DIR', $oldRegulamentos);
        self::restoreEnvironment('SGI_FOTOS_DIR', $oldFotos);
    }

    public function testResolvesRelativeDirectoriesFromProjectRoot(): void
    {
        $oldPdf = getenv('SGI_UPLOAD_DIR');
        $oldWorkingDirectory = getcwd();

        putenv('SGI_UPLOAD_DIR=storage/uploads/turmas');
        chdir(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'resources');

        try {
            self::assertSame(
                dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'turmas',
                StoragePaths::turmaPdfs(),
            );
        } finally {
            if (is_string($oldWorkingDirectory)) {
                chdir($oldWorkingDirectory);
            }
            self::restoreEnvironment('SGI_UPLOAD_DIR', $oldPdf);
        }
    }

    private static function restoreEnvironment(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            return;
        }

        putenv($key . '=' . $value);
    }
}
