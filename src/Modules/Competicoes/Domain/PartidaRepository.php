<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface PartidaRepository
{
    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    /**
     * @param array<string, int|string> $fields
     */
    public function update(int $id, array $fields): bool;
}
