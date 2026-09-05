<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Domain;

interface PontuacaoRepository
{
    /** @return list<array<string, mixed>> */
    public function ranking(): array;

    public function atualizar(int $id, int $pontos): bool;
}
