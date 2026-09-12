<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Domain;

interface TurmaRankingUpdater
{
    /** @param array<string, mixed> $data */
    public function atualizarPeloRanking(array $data): bool;
}
