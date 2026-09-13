<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

use InvalidArgumentException;

final class JogoScheduleRules
{
    public static function normalizeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $date = trim((string) $value);
        if ($date === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new InvalidArgumentException('A data do jogo é inválida.');
        }

        return $date;
    }

    public static function normalizeTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $time = trim((string) $value);
        if ($time === '' || $time === '00:00' || $time === '00:00:00') {
            return null;
        }
        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $time, $parts) !== 1
            || (int) $parts[1] > 23
            || (int) $parts[2] > 59
            || (isset($parts[3]) && (int) $parts[3] > 59)) {
            throw new InvalidArgumentException('O horário do jogo é inválido.');
        }

        return sprintf('%02d:%02d:%02d', (int) $parts[1], (int) $parts[2], (int) ($parts[3] ?? 0));
    }

    public static function assertWindow(?string $start, ?string $end): void
    {
        if ($start !== null && $end !== null && $end <= $start) {
            throw new InvalidArgumentException('O término do jogo deve ocorrer depois do início.');
        }
    }
}
