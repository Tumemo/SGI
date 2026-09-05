<?php

declare(strict_types=1);

namespace App\Usuarios\Domain;

interface UsuarioAdministrativoRepository
{
    public function deactivateStudent(int $id): bool;

    public function resetStudentPassword(int $id, string $hash): bool;

    public function findLevel(int $id): ?string;

    public function deactivateCollaborator(int $id, ?int $interclasseId): bool;
}
