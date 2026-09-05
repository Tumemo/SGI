<?php

declare(strict_types=1);

namespace App\Autenticacao\Application;

use App\Autenticacao\Domain\SenhaRepository;
use InvalidArgumentException;
use RuntimeException;

final class SenhaService
{
    public function __construct(private readonly SenhaRepository $senhas)
    {
    }

    public function trocar(int $usuarioId, string $novaSenha, string $confirmacao): void
    {
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException('Sessão expirada. Faça login novamente.');
        }
        if (mb_strlen($novaSenha) < 6) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 6 caracteres.');
        }
        if ($novaSenha !== $confirmacao) {
            throw new InvalidArgumentException('As senhas não coincidem.');
        }
        if ($novaSenha === '123') {
            throw new InvalidArgumentException('Escolha uma senha diferente da senha padrão.');
        }

        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        if (!$this->senhas->alterarSenha($usuarioId, $hash)) {
            throw new RuntimeException('Não foi possível alterar a senha. Tente novamente.');
        }
    }
}
