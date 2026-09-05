<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

final class PlacarService
{
    /**
     * @param list<int> $scores
     */
    public function validarFinalizacao(array $scores): void
    {
        $this->validar($scores, 'Não é possível finalizar um jogo com placar 0x0. Registre o placar correto.');
    }

    /**
     * @param list<int> $scores
     */
    public function validarAlteracao(array $scores): void
    {
        $this->validar($scores, 'Não é possível alterar o placar de um jogo finalizado para 0x0.');
    }

    /**
     * @param list<int> $scores
     */
    private function validar(array $scores, string $zeroMessage): void
    {
        $normalized = array_map(static fn (mixed $score): int => (int) $score, $scores);
        if (array_sum($normalized) === 0) {
            throw new PlacarInvalidoException($zeroMessage);
        }
        if (count($normalized) >= 2 && $normalized[0] === $normalized[1]) {
            throw new PlacarInvalidoException('O jogo não pode terminar empatado! Registre o placar correto.');
        }
    }
}
