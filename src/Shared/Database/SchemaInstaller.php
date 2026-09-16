<?php

declare(strict_types=1);

namespace App\Shared\Database;

use mysqli;
use RuntimeException;

final class SchemaInstaller
{
    public const BASELINE_VERSION = '__schema_inicial__';

    public function __construct(private readonly mysqli $connection, private readonly string $schemaPath)
    {
    }

    /**
     * Instala o schema em uma base vazia.
     *
     * @return bool true quando o schema foi aplicado, false quando a base já
     *              continha o baseline atual.
     */
    public function install(): bool
    {
        $lock = $this->connection->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':schema'), 10) AS acquired")->fetch_assoc();
        if ((int) ($lock['acquired'] ?? 0) !== 1) {
            throw new RuntimeException('Outra instalação do schema está em execução.');
        }

        try {
            $sql = file_get_contents($this->schemaPath);
            if ($sql === false) {
                throw new RuntimeException('Schema inicial não encontrado.');
            }

            $statements = SqlScript::statements($sql);
            $expectedTables = $this->createdTables($statements);
            if ($expectedTables === []) {
                throw new RuntimeException('Schema inicial não contém tabelas.');
            }

            $allTables = array_map('strval', array_column($this->connection->query('SHOW TABLES')->fetch_all(), 0));
            if (in_array('sgi_migrations', $allTables, true) && $this->hasBaseline()) {
                return false;
            }
            $actualTables = array_values(array_filter($allTables, static fn (string $table): bool => $table !== 'sgi_migrations'));
            sort($actualTables);
            sort($expectedTables);

            if ($actualTables === [] && $allTables !== []) {
                throw new RuntimeException('A base parcialmente criada não pode ser reutilizada.');
            }

            if ($actualTables === []) {
                foreach ($statements as $statement) {
                    $this->connection->query($statement);
                }

                $this->ensureMigrationHistory();
                return true;
            }

            if ($actualTables === $expectedTables) {
                $this->ensureMigrationHistory();
                return false;
            }

            throw new RuntimeException('A base precisa estar vazia ou conter exatamente o schema inicial atual.');
        } finally {
            $this->connection->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':schema'))");
        }
    }

    /** @param list<string> $statements @return list<string> */
    private function createdTables(array $statements): array
    {
        $tables = [];
        foreach ($statements as $statement) {
            if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`([^`]+)`|([A-Za-z0-9_]+))/i', $statement, $match) !== 1) {
                continue;
            }

            $tables[] = $match[1] !== '' ? $match[1] : $match[2];
        }

        $tables = array_values(array_unique($tables));
        sort($tables);
        return $tables;
    }

    private function ensureMigrationHistory(): void
    {
        $this->connection->query(
            'CREATE TABLE IF NOT EXISTS sgi_migrations (
                version VARCHAR(100) PRIMARY KEY,
                checksum CHAR(64) NOT NULL,
                dirty TINYINT NOT NULL DEFAULT 1,
                applied_at TIMESTAMP NULL
            ) ENGINE=InnoDB',
        );
        $statement = $this->connection->prepare('SELECT dirty FROM sgi_migrations WHERE version = ?');
        $version = self::BASELINE_VERSION;
        $statement->bind_param('s', $version);
        $statement->execute();
        $history = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($history !== null && (int) $history['dirty'] !== 0) {
            throw new RuntimeException('Instalação do schema inicial está incompleta; restaure ou recrie a base antes de continuar.');
        }
        if ($history !== null) {
            return;
        }

        $checksum = hash_file('sha256', $this->schemaPath);
        if (!is_string($checksum)) {
            throw new RuntimeException('Não foi possível calcular o checksum do schema inicial.');
        }
        $statement = $this->connection->prepare('INSERT INTO sgi_migrations (version, checksum, dirty, applied_at) VALUES (?, ?, 0, CURRENT_TIMESTAMP)');
        $statement->bind_param('ss', $version, $checksum);
        $statement->execute();
        $statement->close();
    }

    private function hasBaseline(): bool
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM sgi_migrations WHERE version = ? AND dirty = 0');
        $version = self::BASELINE_VERSION;
        $statement->bind_param('s', $version);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }
}
