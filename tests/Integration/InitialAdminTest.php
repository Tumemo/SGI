<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Acesso\Infrastructure\InitialAdminProvisioner;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class InitialAdminTest
{
    public static function run(): void
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        // A connection-local table exercises the real schema without changing test accounts.
        $connection->query('CREATE TEMPORARY TABLE sgi_initial_admin_template LIKE usuarios');
        $connection->query('CREATE TEMPORARY TABLE usuarios LIKE sgi_initial_admin_template');
        try {
            $installer = new InitialAdminProvisioner($connection);
            try {
                $installer->create('admin', 'Administrador', 'curta');
                Assertions::assert('Instalação rejeita senha curta', false);
            } catch (\InvalidArgumentException) {
                Assertions::assert('Instalação rejeita senha curta sem criar usuário', (int) $connection->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] === 0);
            }
            $password = 'Initial-admin-test-123!';
            $id = $installer->create(' primeiro ', ' Administrador Inicial ', $password);
            $row = $connection->query('SELECT * FROM usuarios')->fetch_assoc();
            Assertions::assert('Instalação cria administrador com dados normalizados', $id > 0 && $row['matricula_usuario'] === 'primeiro' && $row['nome_usuario'] === 'Administrador Inicial' && (int) $row['nivel_usuario'] === 0);
            Assertions::assert('Senha inicial é armazenada como hash verificável', $row['senha_usuario'] !== $password && password_verify($password, $row['senha_usuario']));
            try {
                $installer->create('outro', 'Outro administrador', $password);
                Assertions::assert('Instalação não substitui administrador existente', false);
            } catch (\RuntimeException) {
                Assertions::assert('Instalação não substitui administrador existente', $row === $connection->query('SELECT * FROM usuarios')->fetch_assoc() && (int) $connection->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] === 1);
            }
            $connection->query("UPDATE usuarios SET nivel_usuario = '1'");
            try {
                $installer->create('primeiro', 'Duplicado', $password);
                Assertions::assert('Instalação não reutiliza matrícula existente', false);
            } catch (\RuntimeException) {
                Assertions::assert('Instalação não reutiliza matrícula existente', (int) $connection->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] === 1);
            }
            Assertions::assert('Bloqueio da instalação é liberado após recusa', $installer->create('novo', 'Novo administrador', $password) > $id);
        } finally {
            $connection->close();
        }
    }
}
