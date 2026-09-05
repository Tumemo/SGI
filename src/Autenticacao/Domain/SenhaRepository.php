<?php

declare(strict_types=1);

namespace App\Autenticacao\Domain;

interface SenhaRepository
{
    public function alterarSenha(int $usuarioId, string $hash): bool;
}
