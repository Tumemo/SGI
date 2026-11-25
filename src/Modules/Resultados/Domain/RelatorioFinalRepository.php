<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Domain;

interface RelatorioFinalRepository
{
    /** @return array<string, mixed>|null */
    public function loadEdition(int $editionId): ?array;
}
