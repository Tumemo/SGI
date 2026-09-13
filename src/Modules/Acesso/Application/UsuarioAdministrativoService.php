<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\UsuarioAdministrativoRepository;
use App\Shared\Security\StudentInitialPassword;
use InvalidArgumentException;

final class UsuarioAdministrativoService
{
    public function __construct(private readonly UsuarioAdministrativoRepository $usuarios)
    {
    }

    public function excluirAluno(int $id): void
    {
        $this->validateId($id);
        if (!$this->usuarios->deactivateStudent($id)) {
            throw new UsuarioNaoEncontradoException();
        }
    }

    public function resetarSenhaAluno(int $id): string
    {
        $this->validateId($id);
        $temporaryPassword = StudentInitialPassword::VALUE;
        $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        if (!$this->usuarios->resetStudentPassword($id, $hash)) {
            throw new UsuarioNaoEncontradoException();
        }
        return $temporaryPassword;
    }

    public function excluirColaborador(int $id, ?int $interclasseId, int $currentUserId): void
    {
        $this->validateId($id);
        if ($id === $currentUserId) {
            throw new UsuarioProtegidoException('Você não pode remover a própria conta.');
        }
        $level = $this->usuarios->findLevel($id);
        if ($level === null) {
            throw new UsuarioNaoEncontradoException();
        }
        if ($level === '0') {
            throw new UsuarioProtegidoException('Não é possível remover um administrador.');
        }
        if (!$this->usuarios->deactivateCollaborator($id, $interclasseId)) {
            throw new UsuarioNaoEncontradoException();
        }
    }

    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do usuário inválido.');
        }
    }
}
