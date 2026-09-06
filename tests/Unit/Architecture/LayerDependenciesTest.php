<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class LayerDependenciesTest extends TestCase
{
    public function testBusinessLayersDoNotAccessHttpOrDatabaseDirectly(): void
    {
        $root = dirname(__DIR__, 3) . '/src/Modules';
        $count = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!$file->isFile() || $file->getExtension() !== 'php' || !preg_match('~/(Domain|Application)/~', $path)) {
                continue;
            }
            $count++;
            $code = (string) file_get_contents($path);
            self::assertDoesNotMatchRegularExpression('~\b(?:mysqli|PDO)\b|\$_(?:GET|POST|REQUEST|SESSION|SERVER)|\b(?:header|http_response_code|session_start)\s*\(~', $code, $path);
            self::assertDoesNotMatchRegularExpression('~use App\\\\.*\\\\(?:Infrastructure|Presentation|Http)\\\\~', $code, $path);
        }
        self::assertGreaterThan(0, $count, 'Nenhuma regra de negócio foi verificada.');
    }
}
