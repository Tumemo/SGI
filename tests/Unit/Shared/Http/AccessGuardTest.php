<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\AccessGuard;
use PHPUnit\Framework\TestCase;

final class AccessGuardTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $previousSession = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSession = isset($_SESSION) ? $_SESSION : null;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession ?? [];
        parent::tearDown();
    }

    public function testRejectsAnonymousRequests(): void
    {
        $response = AccessGuard::authorize([0]);

        self::assertNotNull($response);
        self::assertSame(401, $response->status());
    }

    public function testRejectsAuthenticatedUsersOutsideAllowedLevels(): void
    {
        $_SESSION['nivel'] = 3;

        $response = AccessGuard::authorize([0, 1]);

        self::assertNotNull($response);
        self::assertSame(403, $response->status());
    }

    public function testAuthorizesUsersAtAllowedLevel(): void
    {
        $_SESSION['nivel'] = 1;

        self::assertNull(AccessGuard::authorize([0, 1]));
    }
}
