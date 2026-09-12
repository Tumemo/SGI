<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

final class PontoRules
{
    public static function permiteAnulacao(string $statusJogo): bool
    {
        return in_array($statusJogo, ['Iniciado', 'Pausado'], true);
    }
}
