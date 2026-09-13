<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\PerfilRepository;
use App\Shared\Security\StudentInitialPassword;
use InvalidArgumentException;

final class PerfilService
{
    public function __construct(private readonly PerfilRepository $profiles)
    {
    }

    public function update(int $id, string $name, string $currentPassword, string $newPassword, int $sessionAuthVersion = 0): void
    {
        $name = trim($name);
        if ($id <= 0 || $name === '') {
            throw new InvalidArgumentException('Nome não pode ficar vazio.');
        }
        $profile = $this->profiles->find($id);
        if ($profile === null) {
            throw new UsuarioNaoEncontradoException('Usuário não encontrado.');
        }
        $hash = null;
        $expectedAuthVersion = null;
        if ($newPassword !== '') {
            if (!password_verify($currentPassword, (string) ($profile['senha_usuario'] ?? ''))) {
                throw new InvalidArgumentException('Senha atual incorreta.');
            }
            if (mb_strlen($newPassword) < 6) {
                throw new InvalidArgumentException('A senha deve ter no mínimo 6 caracteres.');
            }
            if ((int) ($profile['nivel_usuario'] ?? -1) === 3) {
                if ((int) ($profile['senha_troca_pendente'] ?? 0) === 1) {
                    throw new InvalidArgumentException('Conclua a troca obrigatória de senha pela tela de primeiro acesso.');
                }
                if ($newPassword === StudentInitialPassword::VALUE) {
                    throw new InvalidArgumentException('Escolha uma senha diferente da senha inicial compartilhada.');
                }
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $profileAuthVersion = (int) ($profile['auth_version'] ?? 0);
            $expectedAuthVersion = $sessionAuthVersion > 0 ? $sessionAuthVersion : $profileAuthVersion;
            if ($expectedAuthVersion <= 0 || $expectedAuthVersion !== $profileAuthVersion) {
                throw new InvalidArgumentException('Sessão inválida. Faça login novamente.');
            }
        }
        if (!$this->profiles->update($id, $name, $hash, $expectedAuthVersion)) {
            throw new InvalidArgumentException('A conta foi atualizada em outra sessão. Faça login novamente.');
        }
    }
}
