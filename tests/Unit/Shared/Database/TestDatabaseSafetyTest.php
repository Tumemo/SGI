<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Database;

use PHPUnit\Framework\TestCase;

final class TestDatabaseSafetyTest extends TestCase
{
    public function testRequiresDisposableContainerBeforeConnecting(): void
    {
        require_once dirname(__DIR__, 3) . '/Support/TestDatabase.php';
        $previous = getenv('SGI_TEST_DB_RUNTIME');
        try {
            putenv('SGI_TEST_DB_RUNTIME');
            try {
                \SGITests\Support\TestDatabase::assertDisposableContainerRuntime();
                self::fail('Execução sem container foi aceita.');
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('executor Docker', $exception->getMessage());
            }

            putenv('SGI_TEST_DB_RUNTIME=container');
            \SGITests\Support\TestDatabase::assertDisposableContainerRuntime();
            self::assertTrue(true);
        } finally {
            if ($previous === false) {
                putenv('SGI_TEST_DB_RUNTIME');
            } else {
                putenv('SGI_TEST_DB_RUNTIME=' . $previous);
            }
        }
    }

    public function testRefusesProductionNamesBeforeConnecting(): void
    {
        require_once dirname(__DIR__, 3) . '/Support/TestDatabase.php';
        foreach (['sgi', 'production', 'contest', 'sgi_test;DROP DATABASE sgi'] as $name) {
            try {
                \SGITests\Support\TestDatabase::assertSafeDatabaseName($name);
                self::fail('Banco inseguro aceito: ' . $name);
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('Recusada', $exception->getMessage());
            }
        }
    }
}
