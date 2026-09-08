<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface ArtilheiroRepository
{
    public function create(int $userId, int $gameId, int $goals): int;

    public function update(int $userId, int $gameId, int $goals): bool;

    public function editionOfGame(int $gameId): ?int;

    public function editionOfUser(int $userId): ?int;

    public function roleOfUser(int $userId): ?int;

    public function athleteParticipatesInGame(int $userId, int $gameId): bool;
}
