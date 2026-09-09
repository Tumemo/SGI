<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\IndividualRankingRepository;
use mysqli;

final class MysqliIndividualRankingRepository implements IndividualRankingRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function salvarRanking(int $modalityId, array $ranking, ?int $gameId = null): array
    {
        return MysqliIndividualRepository::salvarRanking($this->connection, $modalityId, $ranking, $gameId);
    }

    public function criarJogoAgenda(int $modalityId): array
    {
        return MysqliIndividualRepository::criarJogoAgenda($this->connection, $modalityId);
    }
}
