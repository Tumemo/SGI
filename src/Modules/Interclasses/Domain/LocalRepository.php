<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Domain;

interface LocalRepository
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

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, ?int $interclasseId = null): bool;

    public function delete(int $id): bool;
}
