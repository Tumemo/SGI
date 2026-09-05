<?php

declare(strict_types=1);

namespace App\Interclasse\Domain;

interface ArrecadacaoRepository
{
    /** @return list<array<string, mixed>> */
    public function listByInterclasse(int $interclasseId): array;

    /**
     * @param list<array{id_turma: int, quantidade: float}> $items
     */
    public function addBatch(int $interclasseId, int $userId, array $items): void;

    /**
     * @return 'removed'|'already_removed'|'not_found'
     */
    public function remove(int $historicoId, int $interclasseId): string;
}
