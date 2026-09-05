<?php

declare(strict_types=1);

namespace Tests\Unit\Usuarios;

use App\Modules\Acesso\Application\UsuarioAdministrativoService;
use App\Modules\Acesso\Application\UsuarioNaoEncontradoException;
use App\Modules\Acesso\Application\UsuarioProtegidoException;
use App\Modules\Acesso\Domain\UsuarioAdministrativoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UsuarioAdministrativoServiceTest extends TestCase
{
    public function testResetsStudentPasswordWithHash(): void
    {
        $repository = new InMemoryUsuarioAdministrativoRepository();
        (new UsuarioAdministrativoService($repository))->resetarSenhaAluno(4);

        self::assertNotSame('123', $repository->hash);
        self::assertTrue(password_verify('123', $repository->hash));
    }

    public function testProtectsAdministratorAndOwnAccount(): void
    {
        $repository = new InMemoryUsuarioAdministrativoRepository();
        $service = new UsuarioAdministrativoService($repository);

        $this->expectException(UsuarioProtegidoException::class);
        $service->excluirColaborador(2, 10, 2);
    }

    public function testRejectsUnknownStudent(): void
    {
        $repository = new InMemoryUsuarioAdministrativoRepository();
        $repository->studentExists = false;
        $this->expectException(UsuarioNaoEncontradoException::class);
        (new UsuarioAdministrativoService($repository))->excluirAluno(4);
    }
}

final class InMemoryUsuarioAdministrativoRepository implements UsuarioAdministrativoRepository
{
    public bool $studentExists = true;

    public string $hash = '';

    public function deactivateStudent(int $id): bool
    {
        return $this->studentExists;
    }

    public function resetStudentPassword(int $id, string $hash): bool
    {
        $this->hash = $hash;
        return $this->studentExists;
    }

    public function findLevel(int $id): ?string
    {
        return '1';
    }

    public function deactivateCollaborator(int $id, ?int $interclasseId): bool
    {
        return true;
    }
}
