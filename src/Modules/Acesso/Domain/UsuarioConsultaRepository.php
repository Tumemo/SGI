<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface UsuarioConsultaRepository
{
    /** @return array<string, mixed> */
    public function competitors(int $classId, int $editionId, string $gender = '', bool $includeSensitive = true): array;

    /** @return array<string, mixed> */
    public function collaborators(int $editionId): array;

    /** @return array<string, mixed> */
    public function allUsers(int $editionId): array;

    /** @return array<string, mixed>|null */
    public function findCompetitorForValidation(string $registration, string $birth, int $editionId): ?array;
}
