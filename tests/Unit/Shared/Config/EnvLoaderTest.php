<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Config;

use App\Shared\Config\EnvLoader;
use PHPUnit\Framework\TestCase;

final class EnvLoaderTest extends TestCase
{
    public function testLoadsOnlyMissingEnvironmentVariables(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sgi-env-');
        self::assertNotFalse($path);
        file_put_contents((string) $path, "SGI_TEST_LOADER='valor local'\n# comentário\n");

        $keys = ['SGI_APP_ENV', 'SGI_TEST_LOADER'];
        $previous = [];
        $previousEnv = [];
        foreach ($keys as $key) {
            $previous[$key] = getenv($key);
            $previousEnv[$key] = $_ENV[$key] ?? null;
            putenv($key);
            unset($_ENV[$key]);
        }

        try {
            putenv('SGI_APP_ENV=production');
            EnvLoader::load((string) $path);

            self::assertSame('valor local', getenv('SGI_TEST_LOADER'));
        } finally {
            foreach ($keys as $key) {
                if ($previous[$key] === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $previous[$key]);
                }
                if ($previousEnv[$key] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $previousEnv[$key];
                }
            }
            @unlink((string) $path);
        }
    }

    public function testTestEnvironmentDoesNotLoadDotEnvFallback(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sgi-env-');
        self::assertNotFalse($path);
        file_put_contents((string) $path, "SGI_TEST_LOADER=valor-de-dotenv\n");

        $keys = ['SGI_APP_ENV', 'SGI_TEST_LOADER'];
        $previous = [];
        $previousEnv = [];
        foreach ($keys as $key) {
            $previous[$key] = getenv($key);
            $previousEnv[$key] = $_ENV[$key] ?? null;
            putenv($key);
            unset($_ENV[$key]);
        }

        try {
            putenv('SGI_APP_ENV=test');
            EnvLoader::load((string) $path);

            self::assertFalse(getenv('SGI_TEST_LOADER'));
        } finally {
            foreach ($keys as $key) {
                if ($previous[$key] === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $previous[$key]);
                }
                if ($previousEnv[$key] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $previousEnv[$key];
                }
            }
            @unlink((string) $path);
        }
    }

    public function testProductionEnvironmentKeepsLoadingDotEnv(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sgi-env-');
        self::assertNotFalse($path);
        file_put_contents((string) $path, "SGI_TEST_LOADER=production-value\n");

        $keys = ['SGI_APP_ENV', 'SGI_TEST_LOADER'];
        $previous = [];
        $previousEnv = [];
        foreach ($keys as $key) {
            $previous[$key] = getenv($key);
            $previousEnv[$key] = $_ENV[$key] ?? null;
            putenv($key);
            unset($_ENV[$key]);
        }

        try {
            putenv('SGI_APP_ENV=production');
            EnvLoader::load((string) $path);

            self::assertSame('production-value', getenv('SGI_TEST_LOADER'));
        } finally {
            foreach ($keys as $key) {
                if ($previous[$key] === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $previous[$key]);
                }
                if ($previousEnv[$key] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $previousEnv[$key];
                }
            }
            @unlink((string) $path);
        }
    }
}
