<?php

declare(strict_types=1);

namespace App\Interclasse\Domain;

interface ClassificacaoRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function podium(int $modalityId): array;
}
