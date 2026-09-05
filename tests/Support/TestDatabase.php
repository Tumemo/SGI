<?php

declare(strict_types=1);

namespace SGITests\Support;

use RuntimeException;

final class TestDatabase
{
    private function __construct()
    {
    }

    public static function resetFromSchema(string $databaseName): void
    {
        self::assertSafeDatabaseName($databaseName);

        $schemaPath = dirname(__DIR__, 2) . '/docs/sgi.sql';
        if (!is_file($schemaPath)) {
            throw new RuntimeException('Schema de teste não encontrado: ' . $schemaPath);
        }

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('Não foi possível ler o schema de teste.');
        }

        // O dump legado usa um nome fixo. A substituição ocorre apenas na
        // cópia em memória enviada ao cliente MySQL; o arquivo versionado não
        // é alterado e o banco de desenvolvimento nunca é tocado.
        $schema = preg_replace(
            '/(`)(sgi)(`)/i',
            '$1' . $databaseName . '$3',
            $schema
        );

        if (!is_string($schema) || preg_match('/`sgi`/i', $schema) === 1) {
            throw new RuntimeException('Não foi possível isolar o nome do banco no schema.');
        }

        $mysqlBin = getenv('SGI_MYSQL_BIN') ?: 'C:/xampp/mysql/bin/mysql.exe';
        if (!is_file($mysqlBin)) {
            $mysqlBin = 'mysql';
        }

        $user = getenv('SGI_DB_USER') ?: 'root';
        $password = getenv('SGI_DB_PASSWORD');
        $host = getenv('SGI_DB_HOST') ?: 'localhost';
        $port = (int) (getenv('SGI_DB_PORT') ?: '3306');
        $command = escapeshellarg($mysqlBin)
            . ' --host=' . escapeshellarg($host)
            . ' --port=' . $port
            . ' --user=' . escapeshellarg($user);

        if ($password !== false && $password !== '') {
            $command .= ' --password=' . escapeshellarg($password);
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Não foi possível iniciar o cliente MySQL.');
        }

        fwrite($pipes[0], $schema);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf(
                "Falha ao recriar banco de teste (código %d): %s%s",
                $exitCode,
                trim((string) $stderr),
                trim((string) $stdout) !== '' ? "\n" . trim((string) $stdout) : ''
            ));
        }
    }

    private static function assertSafeDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
            throw new RuntimeException('Nome de banco de teste inválido.');
        }

        if (!preg_match('/(^|_)(test|testing)(_|$)/i', $databaseName)) {
            throw new RuntimeException(sprintf(
                'Recusado reset de banco que não parece ser de teste: %s',
                $databaseName
            ));
        }
    }
}
