<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

final class PlacarService
{
    private const MAX_SCORE = 2147483647;

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

    public static function normalizarPontuacao(mixed $score): int
    {
        if (is_int($score)) {
            $normalized = $score;
        } elseif (is_string($score)) {
            $value = trim($score);
            if (preg_match('/^-?[0-9]+$/D', $value) !== 1) {
                throw new PlacarInvalidoException('A pontuação deve ser um número inteiro.');
            }

            $negative = str_starts_with($value, '-');
            $digits = ltrim($negative ? substr($value, 1) : $value, '0');
            $digits = $digits === '' ? '0' : $digits;
            if ($negative && $digits !== '0') {
                throw new PlacarInvalidoException('A pontuação não pode ser negativa.');
            }
            if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, (string) self::MAX_SCORE) > 0)) {
                throw new PlacarInvalidoException('A pontuação excede o limite permitido pelo banco de dados.');
            }

            $normalized = (int) $digits;
        } else {
            throw new PlacarInvalidoException('A pontuação deve ser um número inteiro.');
        }

        if ($normalized < 0) {
            throw new PlacarInvalidoException('A pontuação não pode ser negativa.');
        }
        if ($normalized > self::MAX_SCORE) {
            throw new PlacarInvalidoException('A pontuação excede o limite permitido pelo banco de dados.');
        }

        return $normalized;
    }

    /**
     * @param list<int> $scores
     */
    private function validar(array $scores, string $zeroMessage): void
    {
        $normalized = array_map(self::normalizarPontuacao(...), $scores);
        if (array_sum($normalized) === 0) {
            throw new PlacarInvalidoException($zeroMessage);
        }
        if (count($normalized) >= 2 && $normalized[0] === $normalized[1]) {
            throw new PlacarInvalidoException('O jogo não pode terminar empatado! Registre o placar correto.');
        }
    }
}
