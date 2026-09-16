<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface UsuarioManagementRepository
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createStudent(array $data, int $editionId): array;

    public function assignStudent(int $userId, int $classId, int $editionId): void;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createStaff(array $data, int $editionId, string $photoFilename = 'default.jpg'): array;

    /** @param array<string, mixed> $data */
    public function updateStaffRole(array $data, int $editionId): void;

    /** @param array<string, mixed> $data */
    public function updateStaffDetails(array $data, int $editionId, int $currentUserId): void;

    public function findStaffLevel(int $id, int $editionId): ?string;

    /** @param array<string, mixed> $data */
    public function updateStudent(array $data, int $editionId): void;
}
