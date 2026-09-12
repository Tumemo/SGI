<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

use InvalidArgumentException;

final class ModalidadeScopeRules
{
    public static function assertCategoryMatchesEdition(int $categoryEditionId, int $editionId, string $categoryStatus): void
    {
        if ($categoryStatus !== '1') {
            throw new InvalidArgumentException('A categoria informada não existe ou está inativa.');
        }
        if ($categoryEditionId !== $editionId) {
            throw new InvalidArgumentException('A categoria e a modalidade devem pertencer à mesma edição.');
        }
    }

    public static function assertEditionTransferAllowed(int $currentEditionId, int $targetEditionId, bool $hasRelatedData): void
    {
        if ($currentEditionId !== $targetEditionId && $hasRelatedData) {
            throw new InvalidArgumentException('Não é possível transferir uma modalidade que já possui equipes, inscrições, jogos, reservas ou créditos vinculados.');
        }
    }
}
