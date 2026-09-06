<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Domain;

interface ClassificacaoRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function podium(int $modalityId): array;
}
