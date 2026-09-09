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
    public function migrate(): array
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
                if ($index === 0 && $existing) {
                    throw new RuntimeException('Base existente sem histórico de migrações. Instale o schema atual ou restaure um backup válido.');
                }
                $statement = $this->connection->prepare('INSERT INTO sgi_migrations (version, checksum) VALUES (?, ?)');
                $statement->bind_param('ss', $version, $hash);
                $statement->execute();
                $statement->close();
                // DDL faz commit implícito em MySQL/MariaDB. O marcador dirty
                // impede que uma aplicação parcial seja confundida com sucesso.
                foreach (SqlScript::statements((string) file_get_contents($file)) as $sql) {
                    $this->connection->query($sql);
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

}
