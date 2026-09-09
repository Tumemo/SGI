<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\MigrationRunner;
use mysqli;
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
        Assertions::assert('Categorias possuem unicidade de nome por edição', self::hasUniqueIndex($connection, 'categorias', 'uk_categorias_edicao_nome'));
        Assertions::assert('Usuários possuem versão de autorização para revogar sessões', self::hasColumn($connection, 'usuarios', 'auth_version'));
        $connection->close();
    }

    private static function hasUniqueIndex(mysqli $connection, string $table, string $index): bool
    {
        $table = $connection->real_escape_string($table);
        $index = $connection->real_escape_string($index);
        $sql = "SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = '{$table}'
                  AND index_name = '{$index}'
                  AND non_unique = 0";

        return (int) $connection->query($sql)->fetch_row()[0] === 2;
    }

    private static function hasColumn(mysqli $connection, string $table, string $column): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }
}
