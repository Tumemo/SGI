<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Domain;

interface TurmaRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    public function duplicateExists(int $interclasseId, string $name, ?string $shift): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
