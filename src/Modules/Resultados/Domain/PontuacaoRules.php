<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Domain;

use InvalidArgumentException;

final class PontuacaoRules
{
    public static function doacao(string|int|float $quantidade, int $valorItem): int
    {
        if ($valorItem < 0) {
            throw new InvalidArgumentException('O valor do item não pode ser negativo.');
        }
        $centésimos = self::centésimos($quantidade);
        $produto = $centésimos * $valorItem;
        return intdiv($produto + 50, 100);
    }

    public static function revalorizar(int $brutoAtual, string|int $quantidade, int $valorAnterior, int $valorNovo): int
    {
        return $brutoAtual
            - self::doacao($quantidade, $valorAnterior)
            + self::doacao($quantidade, $valorNovo);
    }

    public static function ajuste(int $bruto, int $arrecadacao, int $esportes): int
    {
        return $bruto - $arrecadacao - $esportes;
    }

    public static function liquido(int $bruto, int $penalidades): int
    {
        return $bruto - $penalidades;
    }

    public static function somarQuantidade(string|int $quantidade, string|int|float $acrescimo): string
    {
        return self::formatarCentésimos(self::centésimos($quantidade) + self::centésimos($acrescimo));
    }

    public static function subtrairQuantidade(string|int $quantidade, string|int $decremento): string
    {
        $resultado = self::centésimos($quantidade) - self::centésimos($decremento);
        if ($resultado < 0) {
            throw new InvalidArgumentException('A quantidade arrecadada é insuficiente para o estorno.');
        }
        return self::formatarCentésimos($resultado);
    }

    private static function centésimos(string|int|float $quantidade): int
    {
        if (is_float($quantidade)) {
            if (!is_finite($quantidade)) {
                throw new InvalidArgumentException('A quantidade é inválida.');
            }
            $quantidade = number_format($quantidade, 2, '.', '');
        }
        $texto = trim((string) $quantidade);
        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $texto, $partes) !== 1) {
            throw new InvalidArgumentException('A quantidade deve ter até duas casas decimais.');
        }
        $decimais = str_pad((string) ($partes[2] ?? ''), 2, '0');
        return ((int) $partes[1] * 100) + (int) $decimais;
    }

    private static function formatarCentésimos(int $centésimos): string
    {
        return intdiv($centésimos, 100) . '.' . str_pad((string) ($centésimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
