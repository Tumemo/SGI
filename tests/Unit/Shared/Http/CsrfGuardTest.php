<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\CsrfGuard;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class CsrfGuardTest extends TestCase
{
    /** @param array<string, string> $server */
    private function sameOrigin(string $origin, array $server): bool
    {
        $previous = $_SERVER;
        $_SERVER = $server;

        try {
            $method = new ReflectionMethod(CsrfGuard::class, 'isSameOrigin');

            return (bool) $method->invoke(null, $origin);
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testAcceptsExactSchemeHostAndPort(): void
    {
        self::assertTrue($this->sameOrigin('https://sgi.test:8443', [
            'HTTP_HOST' => 'sgi.test:8443',
            'REQUEST_SCHEME' => 'https',
        ]));
    }

    public function testRejectsDifferentPort(): void
    {
        self::assertFalse($this->sameOrigin('https://sgi.test:8444', [
            'HTTP_HOST' => 'sgi.test:8443',
            'REQUEST_SCHEME' => 'https',
        ]));
    }

    public function testRejectsDifferentScheme(): void
    {
        self::assertFalse($this->sameOrigin('https://sgi.test', [
            'HTTP_HOST' => 'sgi.test',
            'REQUEST_SCHEME' => 'http',
        ]));
    }

    public function testRejectsMalformedOrigin(): void
    {
        self::assertFalse($this->sameOrigin('not-an-origin', [
            'HTTP_HOST' => 'sgi.test',
            'REQUEST_SCHEME' => 'https',
        ]));
    }
}
