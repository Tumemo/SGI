<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\MigrationRunner;
use App\Shared\Database\SchemaInstaller;
use mysqli;
use RuntimeException;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class MigrationSupportTest
{
    public static function run(): void
    {
        echo "\n  [Suporte a migrations futuras]\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $suffix = bin2hex(random_bytes(4));
        $table = 'migration_probe_' . $suffix;
        $version = '001_future_schema_probe_' . $suffix . '.sql';
        $directory = dirname(__DIR__, 2) . '/test-results/migration-probe-' . $suffix;

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            $connection->close();
            throw new RuntimeException('Não foi possível preparar a migration sintética.');
        }

        try {
            $sql = "CREATE TABLE `{$table}` (id INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;\n";
            if (file_put_contents($directory . '/' . $version, $sql) === false) {
                throw new RuntimeException('Não foi possível gravar a migration sintética.');
            }

            $runner = new MigrationRunner($connection, $directory);
            $applied = $runner->migrate();
            $repeated = $runner->migrate();
            $schemaRepeated = (new SchemaInstaller($connection, dirname(__DIR__, 2) . '/database/schema-inicial.sql'))->install();
            $exists = (int) $connection->query(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}'",
            )->fetch_column() === 1;
            $recorded = self::hasMigrationRecord($connection, $version);

            Assertions::assert(
                'Migration futura é aplicada sobre o baseline atual e repetição não duplica',
                $applied === [$version] && $repeated === [] && $schemaRepeated === false && $exists && $recorded,
            );

            $root = dirname(__DIR__, 2);
            $planningVersion = '001_cronograma_inscricoes.sql';
            $planningRunner = new MigrationRunner($connection, $root . '/database/migrations');
            $planningApplied = $planningRunner->migrate();
            $planningRepeated = $planningRunner->migrate();
            $planningTables = 0;
            foreach (['interclasse_planejamentos', 'modalidade_planejamentos', 'equipe_planejamentos', 'cronograma_compromissos', 'cronograma_nos', 'cronograma_no_equipes'] as $planningTable) {
                $planningTables += (int) $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $connection->real_escape_string($planningTable) . "'")->fetch_column();
            }
            Assertions::assert(
                'Migration do cronograma cria as tabelas auxiliares e é idempotente',
                in_array($planningVersion, $planningApplied, true) && in_array('002_cronograma_nos.sql', $planningApplied, true) && $planningRepeated === [] && $planningTables === 6,
            );
            foreach (['cronograma_no_equipes', 'cronograma_nos', 'cronograma_compromissos', 'equipe_planejamentos', 'modalidade_planejamentos', 'interclasse_planejamentos'] as $planningTable) {
                $connection->query('DROP TABLE IF EXISTS `' . $planningTable . '`');
            }
            $statement = $connection->prepare('DELETE FROM sgi_migrations WHERE version IN (?, ?)');
            $planningNodesVersion = '002_cronograma_nos.sql';
            $statement->bind_param('ss', $planningVersion, $planningNodesVersion);
            $statement->execute();
            $statement->close();
        } finally {
            $connection->query("DROP TABLE IF EXISTS `{$table}`");
            $statement = $connection->prepare('DELETE FROM sgi_migrations WHERE version = ?');
            $statement->bind_param('s', $version);
            $statement->execute();
            $statement->close();
            foreach (glob($directory . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
            $connection->close();
        }
    }

    private static function hasMigrationRecord(mysqli $connection, string $version): bool
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM sgi_migrations WHERE version = ? AND dirty = 0');
        $statement->bind_param('s', $version);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }
}
