<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Domain;

interface ArtilheiroRepository
{
    public function create(int $userId, int $gameId, int $goals): int;

    public function update(int $userId, int $gameId, int $goals): bool;
}
