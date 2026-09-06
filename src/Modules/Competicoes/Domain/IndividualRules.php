<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Domain;

final class IndividualRules
{
    /** Tag para identificar jogos de modalidade individual */
    public static function tag(int $idModalidade): string
    {
        return 'IND:' . $idModalidade;
    }
    /** Verifica se a tag é de uma modalidade individual */
    public static function parse(?string $nomeJogo): ?int
    {
        if (!\is_string($nomeJogo) || !\preg_match('/^IND:(\d+)$/', $nomeJogo, $m)) {
            return \null;
        }
        return (int) $m[1];
    }
}
