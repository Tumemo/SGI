<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Domain;

interface CategoriaRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listActive(array $filters): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @return array{status_categoria:string, interclasses_id_interclasse:int}|null
     */
    public function find(int $id): ?array;

    public function duplicateExists(int $editionId, string $name, int $exceptId = 0): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void;

    public function deactivateCascade(int $id): void;
}
