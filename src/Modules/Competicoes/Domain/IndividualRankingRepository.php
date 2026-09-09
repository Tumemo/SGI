<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface IndividualRankingRepository
{
    /** @param array{primeiro:int,segundo:int,terceiro:int} $ranking @return array<string,mixed> */
    public function salvarRanking(int $modalityId, array $ranking, ?int $gameId = null): array;

    /** @return array<string,mixed> */
    public function criarJogoAgenda(int $modalityId): array;
}
