<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Presentation\Http\SessionController;
use App\Shared\Http\Request;
use PHPUnit\Framework\TestCase;

final class SessionControllerTest extends TestCase
{
    public function testGetLogoutIsRejectedWithoutDestroyingTheSession(): void
    {
        $response = (new SessionController())->logout(new Request('GET', '/api/v1/logout'));

        self::assertSame(405, $response->status());
        self::assertSame('POST', $response->headers()['Allow'] ?? null);
        self::assertStringContainsString('POST', $response->body());
    }
}
