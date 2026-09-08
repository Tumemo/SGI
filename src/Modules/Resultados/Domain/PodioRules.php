<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Domain;

final class PodioRules
{
    /**
     * @param list<array{posicao:int,id_turma:int,pontos:int,ativo?:int}> $antigos
     * @param list<array{posicao:int,id_turma:int,pontos:int,ativo?:int}> $novos
     * @return array<int, int>
     */
    public static function deltas(array $antigos, array $novos): array
    {
        $totals = [];
        foreach ($antigos as $credit) {
            if (($credit['ativo'] ?? 1) !== 1) {
                continue;
            }
            $classId = (int) $credit['id_turma'];
            $totals[$classId] = ($totals[$classId] ?? 0) - (int) $credit['pontos'];
        }
        foreach ($novos as $credit) {
            if (($credit['ativo'] ?? 1) !== 1) {
                continue;
            }
            $classId = (int) $credit['id_turma'];
            $totals[$classId] = ($totals[$classId] ?? 0) + (int) $credit['pontos'];
        }
        return array_filter($totals, static fn (int $delta): bool => $delta !== 0);
    }
}
