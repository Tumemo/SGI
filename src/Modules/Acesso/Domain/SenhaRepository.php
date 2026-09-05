<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface SenhaRepository
{
    public function alterarSenha(int $usuarioId, string $hash): bool;
}
