<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Database;

use PHPUnit\Framework\TestCase;

final class TestDatabaseSafetyTest extends TestCase
{
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
