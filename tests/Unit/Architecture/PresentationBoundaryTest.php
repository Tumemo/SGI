<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class PresentationBoundaryTest extends TestCase
{
    public function testControllersDoNotExecuteSql(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src'));
        $count = 0;
        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Controller.php')) {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:prepare|execute|begin_transaction|commit|rollback)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/[\'\"]\s*(?:SELECT\s|INSERT\s+INTO\s|UPDATE\s+\w+\s+SET\s|DELETE\s+FROM\s)/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(10, $count);
    }

    public function testAllPresentationHelpersStayAtTheHttpBoundary(): void
    {
        $root = dirname(__DIR__, 3) . '/src/Modules';
        $count = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!$file->isFile() || $file->getExtension() !== 'php' || !str_contains($path, '/Presentation/')) {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:prepare|execute|begin_transaction|commit|rollback)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/[\'\"]\s*(?:SELECT\s|INSERT\s+INTO\s|UPDATE\s+\w+\s+SET\s|DELETE\s+FROM\s)/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(10, $count);
    }

    public function testViewsContainNeitherDatabaseQueriesNorInlinePagePrograms(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/resources/views/pages'));
        $count = 0;
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:query|prepare|execute)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/<script\s*>/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(20, $count);
    }

    public function testLegacyPageUrlsResolveOnlyToExistingPrivateTemplates(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = require $root . '/config/routes/web.php';
        foreach ($routes as $url => $template) {
            self::assertStringStartsWith('/views/', $url);
            self::assertStringStartsWith('resources/views/pages/', $template);
            self::assertFileExists($root . '/' . $template);
        }
    }
}
