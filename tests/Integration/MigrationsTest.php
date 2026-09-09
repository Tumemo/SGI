<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\MigrationRunner;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class MigrationsTest
{
    public static function run(): void
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $query = 'SELECT id_usuario, matricula_usuario, interclasses_id_interclasse, senha_usuario FROM usuarios ORDER BY id_usuario';
        $before = $connection->query($query)->fetch_all(MYSQLI_ASSOC);
        $runner = new MigrationRunner($connection, dirname(__DIR__, 2) . '/database/migrations');
        Assertions::assert('Segunda execução das migrações não reaplica alterações', $runner->migrate() === []);
        Assertions::assert('Migrações preservam identidades, edições e senhas', $before === $connection->query($query)->fetch_all(MYSQLI_ASSOC) && count($before) > 0);
        $connection->close();
    }
}
