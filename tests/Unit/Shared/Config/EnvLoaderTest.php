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

        $previous = getenv('SGI_TEST_LOADER');
        putenv('SGI_TEST_LOADER');
        unset($_ENV['SGI_TEST_LOADER']);
        EnvLoader::load((string) $path);

        self::assertSame('valor local', getenv('SGI_TEST_LOADER'));
        if ($previous === false) {
            putenv('SGI_TEST_LOADER');
        } else {
            putenv('SGI_TEST_LOADER=' . $previous);
        }
        @unlink((string) $path);
    }
}
