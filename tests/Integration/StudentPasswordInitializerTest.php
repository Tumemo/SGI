<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Acesso\Infrastructure\MysqliStudentPasswordInitializer;
use mysqli;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class StudentPasswordInitializerTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Regressão: inicialização de alunos existentes em desenvolvimento]\033[0m\n";
        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($databaseName);
        $connection = TestDatabase::connect($databaseName);
        $studentsBefore = self::students($connection);
        $staffBefore = self::staff($connection);
        $expectedUpdates = count(array_filter($studentsBefore, static fn (array $student): bool =>
            (int) $student['senha_troca_pendente'] !== 1
            || !password_verify('sesi-senai', (string) $student['senha_usuario']),
        ));

        $initializer = new MysqliStudentPasswordInitializer($connection);
        $updated = $initializer->initialize();
        $studentsAfter = self::students($connection);
        Assertions::assert('Inicializador redefine alunos ativos para a senha compartilhada e marca troca obrigatória',
            $updated === $expectedUpdates
            && count($studentsAfter) > 0
            && count(array_filter($studentsAfter, static fn (array $student): bool =>
                (int) $student['senha_troca_pendente'] === 1
                && password_verify('sesi-senai', (string) $student['senha_usuario']),
            )) === count($studentsAfter),
        );
        Assertions::assert('Inicializador não altera senhas, papéis ou versões de equipe', $staffBefore === self::staff($connection));

        $stableStudents = self::students($connection);
        $repeated = $initializer->initialize();
        Assertions::assert('Repetir inicializador é idempotente e não revoga sessões de novo',
            $repeated === 0 && $stableStudents === self::students($connection),
        );
        $connection->close();
    }

    /** @return list<array<string, mixed>> */
    private static function students(mysqli $connection): array
    {
        return $connection->query(
            "SELECT id_usuario, senha_usuario, senha_troca_pendente, auth_version
             FROM usuarios WHERE nivel_usuario = '3' ORDER BY id_usuario",
        )->fetch_all(MYSQLI_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    private static function staff(mysqli $connection): array
    {
        return $connection->query(
            "SELECT id_usuario, senha_usuario, senha_troca_pendente, auth_version
             FROM usuarios WHERE nivel_usuario <> '3' ORDER BY id_usuario",
        )->fetch_all(MYSQLI_ASSOC);
    }
}
