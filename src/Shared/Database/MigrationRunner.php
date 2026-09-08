<?php

declare(strict_types=1);

namespace App\Shared\Database;

use mysqli;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private readonly mysqli $connection, private readonly string $directory)
    {
    }

    /** @return list<string> */
    public function migrate(bool $baselineExisting = false): array
    {
        $lock = $this->connection->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':migrations'), 10) AS acquired")->fetch_assoc();
        if ((int) ($lock['acquired'] ?? 0) !== 1) {
            throw new RuntimeException('Outra migração está em execução.');
        }
        try {
            $tables = array_column($this->connection->query('SHOW TABLES')->fetch_all(), 0);
            $existing = array_diff($tables, ['sgi_migrations']) !== [];
            $this->connection->query('CREATE TABLE IF NOT EXISTS sgi_migrations (version VARCHAR(100) PRIMARY KEY, checksum CHAR(64) NOT NULL, dirty TINYINT NOT NULL DEFAULT 1, applied_at TIMESTAMP NULL) ENGINE=InnoDB');
            $applied = [];
            $files = glob($this->directory . '/*.sql') ?: [];
            sort($files);
            foreach ($files as $index => $file) {
                $version = basename($file);
                $hash = (string) hash_file('sha256', $file);
                $statement = $this->connection->prepare('SELECT checksum, dirty FROM sgi_migrations WHERE version = ?');
                $statement->bind_param('s', $version);
                $statement->execute();
                $previous = $statement->get_result()->fetch_assoc();
                $statement->close();
                if ($previous !== null) {
                    if ((int) $previous['dirty'] !== 0 || $previous['checksum'] !== $hash) {
                        throw new RuntimeException('Migração incompleta ou alterada: ' . $version . '. Restaure ou repare a base antes de continuar.');
                    }
                    continue;
                }
                $baseline = $index === 0 && $existing;
                if ($baseline && !$baselineExisting) {
                    throw new RuntimeException('Base existente sem histórico. Valide um backup e execute migrate --baseline para registrar a estrutura inicial.');
                }
                if ($baseline) {
                    $this->assertBaseline((string) file_get_contents($file));
                }
                $statement = $this->connection->prepare('INSERT INTO sgi_migrations (version, checksum) VALUES (?, ?)');
                $statement->bind_param('ss', $version, $hash);
                $statement->execute();
                $statement->close();
                // DDL faz commit implícito em MySQL/MariaDB. O marcador dirty
                // impede que uma aplicação parcial seja confundida com sucesso.
                if (!$baseline) {
                    foreach (SqlScript::statements((string) file_get_contents($file)) as $sql) {
                        $this->connection->query($sql);
                    }
                }
                $statement = $this->connection->prepare('UPDATE sgi_migrations SET dirty = 0, applied_at = CURRENT_TIMESTAMP WHERE version = ?');
                $statement->bind_param('s', $version);
                $statement->execute();
                $statement->close();
                $applied[] = $version;
            }
            return $applied;
        } finally {
            $this->connection->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':migrations'))");
        }
    }

    private function assertBaseline(string $sql): void
    {
        preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\) ENGINE=/s', $sql, $tables, PREG_SET_ORDER);
        foreach ($tables as $table) {
            preg_match_all('/^\s*`([^`]+)`/m', $table[2], $columns);
            $actual = array_column($this->connection->query('SHOW COLUMNS FROM `' . $table[1] . '`')->fetch_all(MYSQLI_ASSOC), 'Field');
            if (array_diff($columns[1], $actual) !== []) {
                throw new RuntimeException('Estrutura incompatível com a versão inicial: ' . $table[1]);
            }
        }
        $index = $this->connection->query("SHOW INDEX FROM usuarios WHERE Key_name = 'uk_matricula_interclasse'")->fetch_all(MYSQLI_ASSOC);
        usort($index, static fn (array $left, array $right): int => ((int) ($left['Seq_in_index'] ?? 0)) <=> ((int) ($right['Seq_in_index'] ?? 0)));
        if ($index === []
            || (int) ($index[0]['Non_unique'] ?? 1) !== 0
            || array_column($index, 'Column_name') !== ['matricula_usuario', 'interclasses_id_interclasse']) {
            throw new RuntimeException('Índice de matrícula por edição não confere.');
        }
    }
}
