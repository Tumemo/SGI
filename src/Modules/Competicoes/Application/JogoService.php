<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\JogoRepository;
use InvalidArgumentException;

final class JogoService
{
    public function __construct(private readonly JogoRepository $jogos)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function agendar(array $data): int
    {
        $name = trim((string) ($data['nome_jogo'] ?? ''));
        $date = trim((string) ($data['data_jogo'] ?? ''));
        $modalityId = (int) ($data['modalidades_id_modalidade'] ?? 0);
        $localId = (int) ($data['locais_id_local'] ?? 0);
        if ($name === '' || $date === '' || $modalityId <= 0 || $localId <= 0) {
            throw new InvalidArgumentException('Dados incompletos.');
        }
        $start = $this->formatTime($data['inicio_jogo'] ?? null);
        $end = $this->formatTime($data['termino_jogo'] ?? $data['terminno_jogo'] ?? null);
        if ($start === null || $end === null || $this->minutes($end) <= $this->minutes($start)) {
            throw new InvalidArgumentException('Informe um horário de início e término válidos.');
        }
        $conflict = $this->jogos->localConflict($date, $localId, $start, $end);
        if ($conflict !== null) {
            throw new JogoConflitoException($conflict);
        }
        return $this->jogos->create([
            'nome_jogo' => $name,
            'data_jogo' => $date,
            'inicio_jogo' => $start,
            'termino_jogo' => $end,
            'modalidades_id_modalidade' => $modalityId,
            'locais_id_local' => $localId,
            'status_jogo' => (string) ($data['status_jogo'] ?? 'Agendado'),
        ]);
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $time = trim((string) $value);
        if ($time === '' || $time === '00:00' || $time === '00:00:00') {
            return null;
        }
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));
        return $hours * 60 + $minutes;
    }
}
