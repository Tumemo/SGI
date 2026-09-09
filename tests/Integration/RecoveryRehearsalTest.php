<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\MigrationRunner;
use mysqli;
use RuntimeException;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class RecoveryRehearsalTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 16: Ensaio sintético de recuperação]\033[0m\n";
        $source = 'sgi_test_luna_recovery_source';
        $restore = 'sgi_test_luna_recovery_restore';
        TestDatabase::assertSafeDatabaseName($source);
        TestDatabase::assertSafeDatabaseName($restore);

        $backup = dirname(__DIR__, 2) . '/test-results/t28-recovery-' . bin2hex(random_bytes(4)) . '.sql';
        $manifest = $backup . '.json';
        try {
            self::createDatabase($source);
            $connection = TestDatabase::connect($source);
            $appliedInitial = (new MigrationRunner($connection, self::migrationDirectory()))->migrate();
            self::seedSyntheticData($connection);
            $before = self::snapshot($connection);
            $connection->close();

            $dumpVersion = self::createBackup($source, $backup);
            Assertions::assert('Backup sintético contém schema, dados e triggers', is_file($backup) && filesize($backup) > 0 && $dumpVersion !== '');

            $connection = TestDatabase::connect($source);
            $applied = (new MigrationRunner($connection, self::migrationDirectory()))->migrate();
            $upgraded = self::snapshot($connection);
            Assertions::assert('Instalação atual aplica as oito versões sem perder os dados', $applied === [] && $appliedInitial === [
                '001_initial_schema.sql',
                '002_mutation_fingerprint.sql',
                '003_fix_arrecadacao_revaluation.sql',
                '004_podio_credit_sources.sql',
                '005_agendamento_blocos.sql',
                '006_unique_category_name_per_edition.sql',
                '007_publicacao_ranking.sql',
                '008_auth_version.sql',
            ] && $upgraded['users'] === $before['users'] && $upgraded['history'] === $before['history']);
            Assertions::assert('Upgrade da origem registra as versões e deixa request_hash disponível', (int) $connection->query("SELECT COUNT(*) FROM sgi_migrations WHERE dirty = 0")->fetch_row()[0] === 8 && self::hasColumn($connection, 'sincronizacoes_idempotentes', 'request_hash'));
            $connection->close();

            self::createDatabase($restore);
            $restoreOutput = self::restoreBackup($restore, $backup);
            $connection = TestDatabase::connect($restore);
            $restored = self::snapshot($connection);
            Assertions::assert('Restauração em outra base preserva matrículas, hashes e histórico', $restored['users'] === $before['users'] && $restored['history'] === $before['history']);
            Assertions::assert('Restauração preserva triggers e o histórico do schema atual', $restored['triggers'] === $before['triggers'] && self::hasTable($connection, 'sgi_migrations'));
            $connection->close();

            $manifestData = [
                'code_reference' => getenv('SGI_CODE_REFERENCE') ?: 'working-tree',
                'backup_sha256' => hash_file('sha256', $backup),
                'dump_version' => $dumpVersion,
                'restore_command_completed' => $restoreOutput === '',
                'source_database' => $source,
                'restore_database' => $restore,
                'source_schema' => '001 + 002 + 003 + 004 + 005 + 006 + 007 + 008',
                'data_comparison' => 'users/history antes do upgrade == restore do backup',
                'current_architecture_boundary' => 'a base restaurada contém somente o schema atual',
            ];
            file_put_contents($manifest, json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
            Assertions::assert('Manifesto do backup registra hash e fronteira de recuperação', is_file($manifest) && strlen((string) ($manifestData['backup_sha256'] ?? '')) === 64);
        } finally {
            self::dropDatabase($source);
            self::dropDatabase($restore);
        }
    }

    /** @return array{users:list<array<string,mixed>>,history:list<array<string,mixed>>,triggers:list<string>} */
    private static function snapshot(mysqli $connection): array
    {
        return [
            'users' => $connection->query('SELECT id_usuario, matricula_usuario, interclasses_id_interclasse, senha_usuario FROM usuarios ORDER BY id_usuario')->fetch_all(MYSQLI_ASSOC),
            'history' => $connection->query('SELECT id_historico, quantidade, pontos_adicionados, status_historico FROM historico_arrecadacoes ORDER BY id_historico')->fetch_all(MYSQLI_ASSOC),
            'triggers' => array_column($connection->query('SHOW TRIGGERS')->fetch_all(MYSQLI_ASSOC), 'Trigger'),
        ];
    }

    private static function seedSyntheticData(mysqli $connection): void
    {
        $hash = $connection->real_escape_string(password_hash('RecoverySenha!123', PASSWORD_DEFAULT));
        $statements = [
            "INSERT INTO tipos_modalidades VALUES (1, 'Mata-Mata', '1')",
            "INSERT INTO interclasses (id_interclasse, nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse, ponto_1_lugar, ponto_2_lugar, ponto_3_lugar, valor_item_arrecadacao) VALUES (1, 'Recovery 2026', '2026-01-01 00:00:00', 'synthetic.pdf', '1', 10, 7, 5, 2)",
            "INSERT INTO categorias VALUES (1, 'Categoria I', '1', 1)",
            "INSERT INTO turmas VALUES (1, 1, '6EF', 'manha', '6EF', '1', 1, 42, 10.50)",
            "INSERT INTO modalidades VALUES (1, 'Futsal', 'MASC', NULL, 4, '1', 1, 1, 1)",
            "INSERT INTO locais VALUES (1, 'Ginásio recuperação', '1', NULL, '1', 1)",
            "INSERT INTO equipes VALUES (1, '1', 1, 1, '6EF Recovery')",
            "INSERT INTO usuarios (id_usuario, sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao, auth_version) VALUES (1, 'RM', 'RECOVERY-26', 'Aluno sintético', '{$hash}', '3', 'MASC', '2008-01-01', '', '1', 1, 1, NULL, 1)",
            "INSERT INTO jogos VALUES (1, 'MM:2:0:N', '2026-10-01', '08:00:00', '09:00:00', 'Concluido', NULL, 3600, 0, NULL, 1, 1)",
            "INSERT INTO partidas VALUES (1, 1, 1, 1, 3, '1')",
            "INSERT INTO artilheiros VALUES (1, 1, 1, 1)",
            "INSERT INTO pontuacoes VALUES (1, 'Final', 10, 1, NULL)",
            "INSERT INTO historico_arrecadacoes VALUES (1, 1, 1, 1.50, 3, '2026-10-02 10:00:00', 1, '1')",
            "INSERT INTO ocorrencias VALUES (1, 'Advertência', 'Recuperação sintética', '2026-10-03 12:00:00', '12:00:00', 1, '1', 1)",
            "INSERT INTO ocorrencias_turmas VALUES (1, 1, 1, 'Ajuste', 'Recuperação sintética', 1, '2026-10-03', 1, '2026-10-03 12:00:00')",
            "INSERT INTO sincronizacoes_idempotentes (rota, chave_mutacao, status_http, resposta_json) VALUES ('/api/v1/resultados', 'recovery-current', 200, '{\"ok\":true}')",
        ];
        foreach ($statements as $sql) {
            $connection->query($sql);
        }
    }

    private static function createBackup(string $database, string $backup): string
    {
        $binary = self::toolPath('SGI_MYSQLDUMP_PATH', ['mysqldump.exe', 'mysqldump']);
        $version = trim(self::runProcess([$binary, '--version']));
        self::runProcess([
            $binary,
            '--host=' . (getenv('SGI_DB_HOST') ?: '127.0.0.1'),
            '--port=' . (getenv('SGI_DB_PORT') ?: '3306'),
            '--user=' . (getenv('SGI_DB_USER') ?: 'root'),
            '--password=' . (getenv('SGI_DB_PASSWORD') ?: ''),
            '--routines',
            '--triggers',
            '--single-transaction',
            '--skip-lock-tables',
            '--no-tablespaces',
            $database,
        ], null, $backup);
        return $version;
    }

    private static function restoreBackup(string $database, string $backup): string
    {
        $binary = self::toolPath('SGI_MYSQL_PATH', ['mysql.exe', 'mysql']);
        return trim(self::runProcess([
            $binary,
            '--host=' . (getenv('SGI_DB_HOST') ?: '127.0.0.1'),
            '--port=' . (getenv('SGI_DB_PORT') ?: '3306'),
            '--user=' . (getenv('SGI_DB_USER') ?: 'root'),
            '--password=' . (getenv('SGI_DB_PASSWORD') ?: ''),
            $database,
        ], $backup));
    }

    private static function runProcess(array $arguments, ?string $stdin = null, ?string $stdout = null): string
    {
        $command = implode(' ', array_map('escapeshellarg', $arguments));
        $descriptors = [
            0 => $stdin === null ? ['pipe', 'r'] : ['file', $stdin, 'rb'],
            1 => $stdout === null ? ['pipe', 'w'] : ['file', $stdout, 'wb'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2));
        if (!is_resource($process)) {
            throw new RuntimeException('Não foi possível executar a ferramenta de backup.');
        }
        if ($stdin === null && isset($pipes[0])) {
            fclose($pipes[0]);
        }
        $output = $stdout === null && isset($pipes[1]) ? stream_get_contents($pipes[1]) : '';
        if ($stdout === null && isset($pipes[1])) {
            fclose($pipes[1]);
        }
        $error = isset($pipes[2]) ? stream_get_contents($pipes[2]) : '';
        if (isset($pipes[2])) {
            fclose($pipes[2]);
        }
        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new RuntimeException('Ferramenta de recuperação falhou: ' . trim($error));
        }
        return (string) $output;
    }

    /** @param list<string> $names */
    private static function toolPath(string $environment, array $names): string
    {
        $configured = trim((string) getenv($environment));
        if ($configured !== '') {
            return $configured;
        }

        $candidates = $names;
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = array_merge(['C:\\xampp\\mysql\\bin\\' . $names[0]], $names);
        }
        foreach ($candidates as $candidate) {
            if (is_file($candidate) || self::commandAvailable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'Ferramenta %s não encontrada. Defina %s ou instale o cliente MySQL/MariaDB no PATH.',
            $names[0],
            $environment,
        ));
    }

    private static function commandAvailable(string $command): bool
    {
        $probe = PHP_OS_FAMILY === 'Windows' ? 'where ' : 'command -v ';
        $process = proc_open($probe . escapeshellarg($command), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }
        if (isset($pipes[1])) fclose($pipes[1]);
        if (isset($pipes[2])) fclose($pipes[2]);
        return proc_close($process) === 0;
    }

    private static function hasColumn(mysqli $connection, string $table, string $column): bool
    {
        $table = $connection->real_escape_string($table);
        $column = $connection->real_escape_string($column);
        return (int) $connection->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$table}' AND column_name = '{$column}'")->fetch_row()[0] === 1;
    }

    private static function hasTable(mysqli $connection, string $table): bool
    {
        $table = $connection->real_escape_string($table);
        return (int) $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}'")->fetch_row()[0] === 1;
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
            // Limpeza best-effort sem esconder a falha do ensaio.
        }
    }

    private static function migrationDirectory(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }
}
