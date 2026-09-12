<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Domain;

final class TurmaScopeRules
{
    /** @param array<string, mixed>|null $category */
    public static function categoryMatchesEdition(int $editionId, ?array $category): bool
    {
        return $editionId > 0
            && $category !== null
            && (int) ($category['interclasses_id_interclasse'] ?? 0) === $editionId
            && (string) ($category['status_categoria'] ?? '') === '1';
    }

    public static function canTransferEdition(int $currentEditionId, int $targetEditionId, bool $hasDescendants): bool
    {
        return $currentEditionId === $targetEditionId || !$hasDescendants;
    }
}
