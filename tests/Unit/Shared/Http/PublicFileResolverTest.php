<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\PublicFileResolver;
use PHPUnit\Framework\TestCase;

final class PublicFileResolverTest extends TestCase
{
    public function testOnlyExistingFilesInsideTheRequestedDirectoryResolve(): void
    {
        $resolver = new PublicFileResolver();
        $root = dirname(__DIR__, 4);
        self::assertSame(realpath($root . '/public/index.php'), $resolver->resolve('index.php', $root . '/public'));
        foreach (['../composer.json', '..\\composer.json', "index.php\0", 'missing.js', '.'] as $path) {
            self::assertNull($resolver->resolve($path, $root . '/public'));
        }
    }
}
