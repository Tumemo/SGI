<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Config;

use App\Shared\Config\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private ?string $previousValue = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousValue = getenv('SGI_ENV_TEST_VALUE') !== false
            ? (string) getenv('SGI_ENV_TEST_VALUE')
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->previousValue === null) {
            putenv('SGI_ENV_TEST_VALUE');
        } else {
            putenv('SGI_ENV_TEST_VALUE=' . $this->previousValue);
        }

        parent::tearDown();
    }

    public function testReadsValueFromEnvironment(): void
    {
        putenv('SGI_ENV_TEST_VALUE=from-environment');

        self::assertSame('from-environment', Env::get('SGI_ENV_TEST_VALUE'));
    }

    public function testUsesDefaultWhenValueIsMissing(): void
    {
        putenv('SGI_ENV_TEST_VALUE');

        self::assertSame('fallback', Env::get('SGI_ENV_TEST_VALUE', 'fallback'));
    }

    public function testRequiredConfigurationFailsClearly(): void
    {
        putenv('SGI_ENV_TEST_VALUE');

        $this->expectExceptionMessage('SGI_ENV_TEST_VALUE');
        Env::required('SGI_ENV_TEST_VALUE');
    }
}
