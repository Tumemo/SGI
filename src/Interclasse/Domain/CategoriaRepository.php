<?php

declare(strict_types=1);

namespace App\Interclasse\Domain;

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

    public function findStatus(int $id): ?string;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void;

    public function deactivateCascade(int $id): void;
}
