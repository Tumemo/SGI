<?php

declare(strict_types=1);

namespace App\Autenticacao\Domain;

interface UsuarioRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findActiveByMatricula(string $matricula, ?int $activeInterclasseId): ?array;
}
