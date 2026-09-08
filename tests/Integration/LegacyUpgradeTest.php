<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Resultados\Infrastructure\MysqliPodioRepository;
use App\Shared\Database\MigrationRunner;
use App\Shared\Database\SqlScript;
use mysqli;
use RuntimeException;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class LegacyUpgradeTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 15: Upgrade legado e falhas de migração]\033[0m\n";
        $databases = [
            'sgi_test_luna_upgrade',
            'sgi_test_luna_restore',
            'sgi_test_luna_dirty',
            'sgi_test_luna_checksum',
            'sgi_test_luna_empty',
        ];
        foreach ($databases as $database) {
            TestDatabase::assertSafeDatabaseName($database);
        }

        $temporaryDirectories = [];
        try {
            self::runEmptyInstallation($databases[4]);
            self::runLegacyUpgrade($databases[0]);
            self::runIncompatibleBaseline($databases[1]);
            self::runDirtyMigration($databases[2], $temporaryDirectories);
            self::runChecksumMismatch($databases[3], $temporaryDirectories);
        } finally {
            foreach ($databases as $database) {
                self::dropDatabase($database);
            }
            foreach ($temporaryDirectories as $directory) {
                self::removeDirectory($directory);
            }
        }
    }

    private static function runEmptyInstallation(string $database): void
    {
        self::createDatabase($database);
        $connection = TestDatabase::connect($database);
        $runner = new MigrationRunner($connection, self::migrationDirectory());
        $applied = $runner->migrate();
        Assertions::assert('Instalação vazia aplica todas as migrações', $applied === [
            '001_initial_schema.sql',
            '002_mutation_fingerprint.sql',
            '003_fix_arrecadacao_revaluation.sql',
            '004_podio_credit_sources.sql',
        ]);
        Assertions::assert('Instalação vazia não cria credencial de demonstração', (int) $connection->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] === 0);
        $connection->close();
    }

    private static function runLegacyUpgrade(string $database): void
    {
        self::createLegacyDatabase($database);
        $connection = TestDatabase::connect($database);
        self::seedLegacy($connection);
        $before = self::snapshot($connection);
        $runner = new MigrationRunner($connection, self::migrationDirectory());

        $rejectedWithoutBaseline = false;
        try {
            $runner->migrate();
        } catch (RuntimeException $exception) {
            $rejectedWithoutBaseline = str_contains($exception->getMessage(), '--baseline');
        }
        Assertions::assert('Base anterior sem baseline solicitado recusa sem apagar dados', $rejectedWithoutBaseline && $before === self::snapshot($connection));

        $applied = $runner->migrate(true);
        Assertions::assert('Base anterior compatível registra baseline e aplica somente evoluções', $applied === [
            '001_initial_schema.sql',
            '002_mutation_fingerprint.sql',
            '003_fix_arrecadacao_revaluation.sql',
            '004_podio_credit_sources.sql',
        ]);
        self::verifyUpgrade($connection, $before);
        Assertions::assert('Segunda execução do upgrade não reaplica migrações', $runner->migrate() === []);
        Assertions::assert('Segunda execução preserva dados legados', $before === self::snapshot($connection));
        $connection->close();
    }

    /** @param array<string, mixed> $before */
    private static function verifyUpgrade(mysqli $connection, array $before): void
    {
        $columns = $connection->query('SHOW COLUMNS FROM sincronizacoes_idempotentes')->fetch_all(MYSQLI_ASSOC);
        $columnNames = array_column($columns, 'Field');
        Assertions::assert('Upgrade adiciona fingerprint de mutação', in_array('request_hash', $columnNames, true));
        Assertions::assert('Registro legado de idempotência preserva fingerprint nulo', $connection->query("SELECT request_hash FROM sincronizacoes_idempotentes WHERE chave_mutacao = 'mut-legacy'")->fetch_row()[0] === null);

        $connection->query("INSERT INTO sincronizacoes_idempotentes (rota, chave_mutacao, status_http, resposta_json, request_hash) VALUES ('/api/v1/teste', 'mut-upgrade', 201, '{\"ok\":true}', '" . hash('sha256', 'upgrade') . "')");
        Assertions::assert('Novo registro de idempotência aceita fingerprint', $connection->query("SELECT request_hash FROM sincronizacoes_idempotentes WHERE chave_mutacao = 'mut-upgrade'")->fetch_row()[0] === hash('sha256', 'upgrade'));

        $connection->query("INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, id_equipe, id_jogo, pontos, ativo, origem_registro) VALUES (1, 1, 1, 1, 1, 1, 10, 1, 'novo'), (1, 1, 2, 1, 2, 1, 7, 1, 'novo')");
        $connection->query("INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, id_usuario, id_jogo, pontos, ativo, origem_registro) VALUES (2, 2, 1, 2, 2, 2, 7, 1, 'novo')");
        $podium = new MysqliPodioRepository($connection);
        $diagnostic = $podium->diagnosticar();
        Assertions::assert('Diagnóstico pós-upgrade não encontra pódios órfãos ou sem origem', $diagnostic['podios_sem_origem'] === [] && $diagnostic['creditos_orfaos'] === [] && $diagnostic['diferenças'] === []);
        $countBeforeAdoption = (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio')->fetch_row()[0];
        $podium->adotar([[
            'id_interclasse' => 1,
            'id_modalidade' => 1,
            'posicao' => 1,
            'id_turma' => 1,
            'id_equipe' => 1,
            'id_jogo' => 1,
            'pontos' => 10,
        ]]);
        Assertions::assert('Adoção idêntica pós-upgrade não duplica crédito', (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio')->fetch_row()[0] === $countBeforeAdoption);

        $connection->query('UPDATE interclasses SET valor_item_arrecadacao = 3 WHERE id_interclasse = 1');
        Assertions::assert('Migração de revalorização preserva pontos esportivos e ajustes', (int) $connection->query('SELECT pontuacao_turma FROM turmas WHERE id_turma = 1')->fetch_row()[0] === 53);
        Assertions::assert('Upgrade preserva matrículas repetidas por edição e hashes', $before['users'] === self::snapshot($connection)['users'] && password_verify('LegacySenha!123', (string) $connection->query('SELECT senha_usuario FROM usuarios WHERE id_usuario = 1')->fetch_row()[0]));
        Assertions::assert('Upgrade preserva histórico fracionário e registro inativo', $before['history'] === self::snapshot($connection)['history']);
        Assertions::assert('Índice de matrícula por edição continua efetivamente único', self::isUniqueIndex($connection, 'usuarios', 'uk_matricula_interclasse'));
        Assertions::assert('Upgrade preserva FKs sem órfãos nos créditos', (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio p LEFT JOIN turmas t ON t.id_turma = p.id_turma WHERE t.id_turma IS NULL')->fetch_row()[0] === 0);
    }

    private static function runIncompatibleBaseline(string $database): void
    {
        self::createLegacyDatabase($database);
        $connection = TestDatabase::connect($database);
        $connection->query('ALTER TABLE usuarios DROP INDEX uk_matricula_interclasse, ADD INDEX uk_matricula_interclasse (matricula_usuario, interclasses_id_interclasse)');
        $runner = new MigrationRunner($connection, self::migrationDirectory());
        $rejected = false;
        try {
            $runner->migrate(true);
        } catch (RuntimeException $exception) {
            $rejected = str_contains($exception->getMessage(), 'Índice de matrícula');
        }
        Assertions::assert('Índice incompatível recusa baseline sem aprovação falsa', $rejected && (int) $connection->query("SELECT COUNT(*) FROM sgi_migrations WHERE version = '002_mutation_fingerprint.sql'")->fetch_row()[0] === 0);
        $connection->close();
    }

    /** @param list<string> $temporaryDirectories */
    private static function runDirtyMigration(string $database, array &$temporaryDirectories): void
    {
        self::createDatabase($database);
        $directory = self::temporaryDirectory('dirty');
        $temporaryDirectories[] = $directory;
        file_put_contents($directory . '/001_dirty.sql', "CREATE TABLE dirty_marker (id INT NOT NULL) ENGINE=InnoDB;\nTHIS IS INVALID SQL;");
        $connection = TestDatabase::connect($database);
        $runner = new MigrationRunner($connection, $directory);
        $failed = false;
        try {
            $runner->migrate();
        } catch (\Throwable) {
            $failed = true;
        }
        $dirty = $connection->query("SELECT dirty FROM sgi_migrations WHERE version = '001_dirty.sql'")->fetch_row()[0] ?? null;
        Assertions::assert('Falha após DDL mantém migração dirty e objeto parcial visível', $failed && (int) $dirty === 1 && (int) $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'dirty_marker'")->fetch_row()[0] === 1);
        $blocked = false;
        try {
            $runner->migrate();
        } catch (RuntimeException $exception) {
            $blocked = str_contains($exception->getMessage(), 'incompleta ou alterada');
        }
        Assertions::assert('Migração dirty bloqueia uma nova tentativa automática', $blocked);
        $connection->close();
    }

    /** @param list<string> $temporaryDirectories */
    private static function runChecksumMismatch(string $database, array &$temporaryDirectories): void
    {
        self::createDatabase($database);
        $directory = self::temporaryDirectory('checksum');
        $temporaryDirectories[] = $directory;
        foreach (glob(self::migrationDirectory() . '/*.sql') ?: [] as $file) {
            copy($file, $directory . '/' . basename($file));
        }
        $connection = TestDatabase::connect($database);
        $runner = new MigrationRunner($connection, $directory);
        $runner->migrate();
        file_put_contents($directory . '/004_podio_credit_sources.sql', file_get_contents($directory . '/004_podio_credit_sources.sql') . "\n-- alteração incompatível do teste\n");
        $blocked = false;
        try {
            $runner->migrate();
        } catch (RuntimeException $exception) {
            $blocked = str_contains($exception->getMessage(), 'Migração incompleta ou alterada');
        }
        Assertions::assert('Checksum divergente bloqueia a migração', $blocked);
        $connection->close();
    }

    private static function createLegacyDatabase(string $database): void
    {
        self::createDatabase($database);
        $connection = TestDatabase::connect($database);
        foreach (SqlScript::statements((string) file_get_contents(self::migrationDirectory() . '/001_initial_schema.sql')) as $sql) {
            $connection->query($sql);
        }
        $connection->close();
    }

    private static function seedLegacy(mysqli $connection): void
    {
        $hash = $connection->real_escape_string(password_hash('LegacySenha!123', PASSWORD_DEFAULT));
        $statements = [
            "INSERT INTO tipos_modalidades VALUES (1, 'Mata-Mata', '1')",
            "INSERT INTO interclasses VALUES (1, 'Luna 2025', '2025-01-01 00:00:00', 'legacy.pdf', '0', 10, 7, 5, 2), (2, 'Luna 2026', '2026-01-01 00:00:00', 'legacy.pdf', '1', 10, 7, 5, 2)",
            "INSERT INTO categorias VALUES (1, 'Categoria I', '1', 1), (2, 'Categoria II', '1', 2)",
            "INSERT INTO turmas VALUES (1, 1, '6EF', 'manha', '6EF', '1', 1, 42, 10.50), (2, 2, '6EF', 'manha', '6EF', '1', 2, 30, 5.00)",
            "INSERT INTO modalidades VALUES (1, 'Futsal', 'MASC', NULL, 4, '1', 1, 1, 1), (2, 'Atletismo', 'MISTO', NULL, 0, '1', 1, 2, 2)",
            "INSERT INTO locais VALUES (1, 'Ginásio legado A', '1', NULL, '1', 1), (2, 'Ginásio legado B', '1', NULL, '1', 2)",
            "INSERT INTO equipes VALUES (1, '1', 1, 1, '6EF 2025'), (2, '1', 1, 1, '6EF 2025 B')",
            "INSERT INTO usuarios VALUES (1, 'RM', '12345', 'Aluno 2025', '" . $hash . "', '3', 'MASC', '2007-01-01', '', '0', 1, 1, NULL), (2, 'RM', '12345', 'Aluno 2026', '" . $hash . "', '3', 'MASC', '2007-01-01', '', '1', 2, 2, NULL), (3, 'SS', 'COLAB-26', 'Colaborador Sintético', '" . $hash . "', '1', 'MASC', '1990-01-01', '', '1', NULL, 2, NULL), (4, 'SS', 'ADMIN-26', 'Administrador Sintético', '" . $hash . "', '0', 'MASC', '1980-01-01', '', '1', NULL, 2, NULL)",
            "INSERT INTO equipes_has_usuarios VALUES (1, 1), (2, 1)",
            "INSERT INTO jogos VALUES (1, 'MM:2:0:N', '2025-10-01', '08:00:00', '09:00:00', 'Concluido', NULL, 3600, 0, NULL, 1, 1), (2, 'IND:1', '2026-10-01', '10:00:00', '11:00:00', 'Concluido', NULL, 3600, 0, NULL, 2, 2)",
            "INSERT INTO partidas VALUES (1, 1, 1, 1, 3, '1'), (2, 1, 2, 1, 1, '1')",
            "INSERT INTO artilheiros VALUES (1, 1, 1, 3)",
            "INSERT INTO pontuacoes VALUES (1, 'Final', 10, 1, NULL), (2, 'Individual', 7, 2, 2)",
            "INSERT INTO historico_arrecadacoes VALUES (1, 1, 1, 1.50, 3, '2025-10-02 10:00:00', 3, '1'), (2, 1, 1, 2.50, 5, '2025-10-03 10:00:00', 3, '0')",
            "INSERT INTO ocorrencias VALUES (1, 'Advertência', 'Penalidade sintética', '2025-10-03 12:00:00', '12:00:00', 2, '1', 1)",
            "INSERT INTO ocorrencias_turmas VALUES (1, 1, 1, 'Ajuste', 'Ajuste manual sintético', 3, '2025-10-03', 3, '2025-10-03 12:00:00')",
            "INSERT INTO sincronizacoes_idempotentes (rota, chave_mutacao, status_http, resposta_json) VALUES ('/api/legacy', 'mut-legacy', 200, '{\"ok\":true}')",
        ];
        foreach ($statements as $sql) {
            $connection->query($sql);
        }
    }

    /** @return array{users:list<array<string,mixed>>,history:list<array<string,mixed>>} */
    private static function snapshot(mysqli $connection): array
    {
        return [
            'users' => $connection->query('SELECT id_usuario, matricula_usuario, interclasses_id_interclasse, senha_usuario FROM usuarios ORDER BY id_usuario')->fetch_all(MYSQLI_ASSOC),
            'history' => $connection->query('SELECT id_historico, quantidade, pontos_adicionados, status_historico FROM historico_arrecadacoes ORDER BY id_historico')->fetch_all(MYSQLI_ASSOC),
        ];
    }

    private static function isUniqueIndex(mysqli $connection, string $table, string $index): bool
    {
        $index = $connection->real_escape_string($index);
        $rows = $connection->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'")->fetch_all(MYSQLI_ASSOC);
        return $rows !== [] && (int) $rows[0]['Non_unique'] === 0;
    }

    private static function createDatabase(string $database): void
    {
        $connection = TestDatabase::connect();
        $connection->query('DROP DATABASE IF EXISTS `' . $database . '`');
        $connection->query('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        $connection->close();
    }

    private static function dropDatabase(string $database): void
    {
        try {
            $connection = TestDatabase::connect();
            $connection->query('DROP DATABASE IF EXISTS `' . $database . '`');
            $connection->close();
        } catch (\Throwable) {
            // A cleanup failure must not hide the assertion that already ran.
        }
    }

    private static function migrationDirectory(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }

    private static function temporaryDirectory(string $name): string
    {
        $directory = dirname(__DIR__, 2) . '/test-results/t26-' . $name . '-' . bin2hex(random_bytes(4));
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar o diretório temporário de migração.');
        }
        return $directory;
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
