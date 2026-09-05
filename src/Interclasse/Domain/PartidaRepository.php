<?php

declare(strict_types=1);

namespace App\Interclasse\Domain;

interface PartidaRepository
{
    /**
     * @param array<string, int|string> $fields
     */
    public function update(int $id, array $fields): bool;
}
