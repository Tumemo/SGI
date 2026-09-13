<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\SenhaRepository;
use App\Shared\Security\StudentInitialPassword;
use InvalidArgumentException;
use RuntimeException;

final class SenhaService
{
    public function __construct(private readonly SenhaRepository $senhas)
    {
    }

    public function trocar(
        int $usuarioId,
        string $novaSenha,
        string $confirmacao,
        string $senhaAtual = '',
        bool $trocaInicial = false,
        int $authVersion = 0,
    ): void {
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException('Sessão expirada. Faça login novamente.');
        }
        if (mb_strlen($novaSenha) < 6) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 6 caracteres.');
        }
        if ($novaSenha !== $confirmacao) {
            throw new InvalidArgumentException('As senhas não coincidem.');
        }
        if ($novaSenha === StudentInitialPassword::VALUE) {
            throw new InvalidArgumentException('Escolha uma senha diferente da senha padrão.');
        }

        if ($this->senhas->senhaAtualValida($usuarioId, $novaSenha)) {
            throw new InvalidArgumentException('A nova senha deve ser diferente da senha atual.');
        }
        if (!$trocaInicial && !$this->senhas->senhaAtualValida($usuarioId, $senhaAtual)) {
            throw new InvalidArgumentException('Informe a senha atual para confirmar a alteração.');
        }
        if ($trocaInicial && ($authVersion <= 0 || $senhaAtual !== '')) {
            throw new InvalidArgumentException('Sessão inválida para concluir a troca de primeiro acesso.');
        }

        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        if (!$this->senhas->alterarSenha($usuarioId, $hash, $authVersion, $trocaInicial)) {
            throw new InvalidArgumentException('A conta foi atualizada em outra sessão. Faça login novamente.');
        }
    }
}
