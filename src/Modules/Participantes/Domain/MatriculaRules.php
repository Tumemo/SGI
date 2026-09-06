<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Domain;

final class MatriculaRules
{
    /**
     * Normaliza RA/RM para comparação com a base importada (apenas dígitos).
     */
    public static function normalizarRa(?string $rm): string
    {
        return \preg_replace('/[^0-9]/', '', (string) $rm);
    }
    /**
     * Converte data dd/mm/aaaa ou aaaa-mm-dd para Y-m-d; inválida retorna null.
     */
    public static function parseDataNascimento(?string $data): ?string
    {
        $data = \trim((string) $data);
        if ($data === '') {
            return \null;
        }
        if (\preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $data);
            return $dt && $dt->format('Y-m-d') === $data ? $data : \null;
        }
        if (\preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
            if (!\checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return \null;
            }
            return \sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        return \null;
    }
}
