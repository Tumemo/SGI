<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    protected function tearDown(): void
    {
        Url::configure('');
    }

    public function testRootAndSubdirectoryUseTheSameRelativeResource(): void
    {
        Url::configure('');
        self::assertSame('/api/v1/jogos', Url::to('api/v1/jogos'));
        Url::configure('/SGI/');
        self::assertSame('/SGI/api/v1/jogos', Url::to('/api/v1/jogos'));
        self::assertSame('/SGI/assets/js/pages/placar.js', Url::to('assets/js/pages/placar.js'));
    }
}
