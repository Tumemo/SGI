<?php

declare(strict_types=1);

namespace App\Interclasse\Domain;

interface JogoRepository
{
    public function localConflict(
        string $date,
        int $localId,
        string $start,
        string $end,
        ?int $currentId = null,
    ): ?string;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;
}
