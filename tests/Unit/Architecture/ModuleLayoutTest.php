<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class ModuleLayoutTest extends TestCase
{
    public function testBusinessCodeIsOrganizedByModularBoundedContexts(): void
    {
        $root = dirname(__DIR__, 3);

        foreach ([
            'Acesso' => ['Application', 'Domain', 'Infrastructure'],
            'Participantes' => ['Application', 'Domain', 'Infrastructure'],
            'Disciplina' => ['Application', 'Domain', 'Infrastructure'],
            'Competicoes' => ['Application', 'Domain', 'Infrastructure', 'Presentation'],
            'Eventos' => ['Application', 'Domain', 'Infrastructure', 'Presentation'],
            'Resultados' => ['Application', 'Domain', 'Infrastructure', 'Presentation'],
            'Sincronizacao' => ['Domain', 'Infrastructure', 'Presentation'],
        ] as $module => $layers) {
            foreach ($layers as $layer) {
                self::assertDirectoryExists($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $layer);
            }
        }

        self::assertDirectoryExists($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Shared');
    }

    public function testLegacyNamespacesAndArtifactsWereRemoved(): void
    {
        $root = dirname(__DIR__, 3);

        foreach (['Interclasse', 'Autenticacao', 'Usuarios'] as $legacyModule) {
            self::assertDirectoryDoesNotExist($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $legacyModule);
        }
        self::assertDirectoryDoesNotExist($root . DIRECTORY_SEPARATOR . 'views');
        self::assertDirectoryDoesNotExist($root . DIRECTORY_SEPARATOR . 'api');
        self::assertFileDoesNotExist($root . DIRECTORY_SEPARATOR . 'index.php');

        $legacyNamespaces = [
            'App\\Interclasse',
            'App\\Autenticacao',
            'App\\Usuarios',
        ];

        $files = [];
        foreach (['config', 'public', 'src', 'tests'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $root . DIRECTORY_SEPARATOR . $directory,
                    \FilesystemIterator::SKIP_DOTS,
                ),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            foreach ($legacyNamespaces as $legacyNamespace) {
                self::assertStringNotContainsString($legacyNamespace, $contents, $file);
            }
        }
    }
}
