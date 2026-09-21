<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

use InvalidArgumentException;

/**
 * Monta a árvore estrutural que será usada pelo cronograma antes das inscrições.
 * A árvore não cria jogos, atletas ou resultados: ela apenas descreve os
 * confrontos possíveis e as dependências entre fases.
 */
final class CronogramaBracketPlanner
{
    /**
     * @param list<int> $teamIds
     * @return list<array<string,mixed>>
     */
    public static function plan(int $modalityId, int $classId, array $teamIds): array
    {
        if ($modalityId <= 0 || $classId <= 0) {
            throw new InvalidArgumentException('Modalidade e turma são obrigatórias para montar o chaveamento.');
        }
        $teamIds = array_values(array_unique(array_filter(array_map('intval', $teamIds), static fn (int $id): bool => $id > 0)));
        if ($teamIds === []) {
            return [];
        }
        sort($teamIds, SORT_NUMERIC);
        $width = self::nextPowerOfTwo(count($teamIds));
        $levels = [];
        $slots = [];
        for ($slot = 0; $slot < intdiv($width, 2); $slot++) {
            $candidateIds = array_values(array_filter([
                $teamIds[$slot * 2] ?? null,
                $teamIds[$slot * 2 + 1] ?? null,
            ], static fn (?int $id): bool => $id !== null));
            if ($candidateIds === []) {
                $slots[$slot] = null;
                continue;
            }
            $slots[$slot] = self::node($classId, $width, $width, $slot, $candidateIds, null, null);
        }
        $levels[$width] = $slots;

        while ($width > 2) {
            $nextWidth = intdiv($width, 2);
            $parents = [];
            for ($slot = 0; $slot < intdiv($nextWidth, 2); $slot++) {
                $left = $levels[$width][$slot * 2] ?? null;
                $right = $levels[$width][$slot * 2 + 1] ?? null;
                if ($left === null && $right === null) {
                    $parents[$slot] = null;
                    continue;
                }
                $candidateIds = array_values(array_unique(array_merge(
                    $left['equipe_ids'] ?? [],
                    $right['equipe_ids'] ?? [],
                )));
                $parents[$slot] = self::node(
                    $classId,
                    self::nextPowerOfTwo(count($teamIds)),
                    $nextWidth,
                    $slot,
                    $candidateIds,
                    $left['chave_tag'] ?? null,
                    $right['chave_tag'] ?? null,
                );
            }
            $levels[$nextWidth] = $parents;
            $width = $nextWidth;
        }

        $nodes = [];
        foreach ($levels as $level) {
            foreach ($level as $node) {
                if ($node !== null) {
                    $nodes[] = $node;
                }
            }
        }
        return $nodes;
    }

    /** @return array<string,mixed> */
    private static function node(int $classId, int $initialWidth, int $width, int $slot, array $teamIds, ?string $left, ?string $right): array
    {
        $isBye = ($left === null && $right === null && count($teamIds) === 1)
            || (($left === null) xor ($right === null));
        $kind = $isBye ? 'B' : 'N';
        return [
            'chave_tag' => sprintf('PL:%d:MM:%d:%d:%s', $classId, $width, $slot, $kind),
            'tipo_no' => $isBye ? 'bye' : 'normal',
            'fase_largura' => $width,
            'slot' => $slot,
            'condicional' => $width < $initialWidth ? 1 : 0,
            'id_equipe_a' => $teamIds[0] ?? null,
            'id_equipe_b' => $teamIds[1] ?? null,
            'equipe_ids' => $teamIds,
            'origem_a_tag' => $left,
            'origem_b_tag' => $right,
        ];
    }

    private static function nextPowerOfTwo(int $number): int
    {
        $width = 1;
        while ($width < $number) {
            $width *= 2;
        }
        return max(2, $width);
    }
}
