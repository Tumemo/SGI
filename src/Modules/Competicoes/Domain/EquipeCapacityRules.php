<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

use InvalidArgumentException;

final class EquipeCapacityRules
{
    public static function podeAtivar(?int $maximum, int $active): bool
    {
        if ($active < 0) {
            throw new InvalidArgumentException('A quantidade de equipes ativas não pode ser negativa.');
        }

        return $maximum === null || $maximum <= 0 || $active < $maximum;
    }
}
