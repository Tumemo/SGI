<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Domain;

interface RankingRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters): array;

}
