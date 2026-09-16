<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\HealthController;
use App\Shared\Http\Request;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function testReportsContainerRuntimeOnlyInTestEnvironment(): void
    {
        $previous = [
            'SGI_APP_ENV' => getenv('SGI_APP_ENV'),
            'SGI_DB_NAME' => getenv('SGI_DB_NAME'),
            'SGI_TEST_DB_RUNTIME' => getenv('SGI_TEST_DB_RUNTIME'),
        ];

        try {
            putenv('SGI_APP_ENV=test');
            putenv('SGI_DB_NAME=sgi_test_health');
            putenv('SGI_TEST_DB_RUNTIME=container');

            $response = (new HealthController())(new Request('GET', '/api/v1/health'));
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            self::assertSame('sgi_test_health', $payload['test_environment']['database']);
            self::assertSame('container', $payload['test_environment']['database_runtime']);
        } finally {
            self::restoreEnvironment($previous);
        }
    }

    public function testDoesNotExposeTestEnvironmentOutsideTestMode(): void
    {
        $previous = [
            'SGI_APP_ENV' => getenv('SGI_APP_ENV'),
            'SGI_DB_NAME' => getenv('SGI_DB_NAME'),
            'SGI_TEST_DB_RUNTIME' => getenv('SGI_TEST_DB_RUNTIME'),
        ];

        try {
            putenv('SGI_APP_ENV=development');
            putenv('SGI_DB_NAME=sgi');
            putenv('SGI_TEST_DB_RUNTIME=container');

            $response = (new HealthController())(new Request('GET', '/api/v1/health'));
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            self::assertArrayNotHasKey('test_environment', $payload);
        } finally {
            self::restoreEnvironment($previous);
        }
    }

    /** @param array<string, string|false> $values */
    private static function restoreEnvironment(array $values): void
    {
        foreach ($values as $name => $value) {
            if ($value === false) {
                putenv($name);
            } else {
                putenv($name . '=' . $value);
            }
        }
    }
}
