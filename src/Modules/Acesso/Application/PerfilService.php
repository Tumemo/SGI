<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\PerfilRepository;
use InvalidArgumentException;

final class PerfilService
{
    public function __construct(private readonly PerfilRepository $profiles)
    {
    }

    public function update(int $id, string $name, string $currentPassword, string $newPassword): void
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
        if ($newPassword !== '') {
            if (!password_verify($currentPassword, (string) ($profile['senha_usuario'] ?? ''))) {
                throw new InvalidArgumentException('Senha atual incorreta.');
            }
            if (mb_strlen($newPassword) < 6) {
                throw new InvalidArgumentException('A senha deve ter no mínimo 6 caracteres.');
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $this->profiles->update($id, $name, $hash);
    }
}
