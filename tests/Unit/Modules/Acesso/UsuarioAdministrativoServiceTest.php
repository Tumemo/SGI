<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

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
        $temporaryPassword = (new UsuarioAdministrativoService($repository))->resetarSenhaAluno(4);

        self::assertSame('sesi-senai', $temporaryPassword);
        self::assertTrue(password_verify($temporaryPassword, $repository->hash));
    }

    public function testProtectsOwnAccount(): void
    {
        $repository = new InMemoryUsuarioAdministrativoRepository();
        $repository->level = '0';
        $service = new UsuarioAdministrativoService($repository);

        $this->expectException(UsuarioProtegidoException::class);
        $service->excluirColaborador(2, 10, 2);
    }

    public function testAllowsDeletingAnotherAdministrator(): void
    {
        $repository = new InMemoryUsuarioAdministrativoRepository();
        $repository->level = '0';

        (new UsuarioAdministrativoService($repository))->excluirColaborador(2, 10, 1);

        self::assertSame(2, $repository->deactivatedId);
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

    public string $level = '1';

    public ?int $deactivatedId = null;

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
        return $this->level;
    }

    public function deactivateCollaborator(int $id, ?int $interclasseId): bool
    {
        $this->deactivatedId = $id;
        return true;
    }
}
