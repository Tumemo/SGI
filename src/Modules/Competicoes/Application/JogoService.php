<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\JogoRepository;
use App\Modules\Competicoes\Domain\JogoScheduleRules;
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
        $date = JogoScheduleRules::normalizeDate($data['data_jogo'] ?? null);
        $modalityId = (int) ($data['modalidades_id_modalidade'] ?? 0);
        $localId = (int) ($data['locais_id_local'] ?? 0);
        if ($name === '' || $date === null || $modalityId <= 0 || $localId <= 0) {
            throw new InvalidArgumentException('Dados incompletos.');
        }
        $start = JogoScheduleRules::normalizeTime($data['inicio_jogo'] ?? null);
        $end = JogoScheduleRules::normalizeTime($data['termino_jogo'] ?? $data['terminno_jogo'] ?? null);
        if ($start === null || $end === null) {
            throw new InvalidArgumentException('Informe um horário de início e término válidos.');
        }
        JogoScheduleRules::assertWindow($start, $end);
        $teams = [];
        if (array_key_exists('equipes', $data)) {
            if (!is_array($data['equipes'])) {
                throw new InvalidArgumentException('A lista de equipes do jogo é inválida.');
            }
            foreach ($data['equipes'] as $team) {
                $teamId = is_array($team)
                    ? (int) ($team['id_equipe'] ?? $team['equipes_id_equipe'] ?? 0)
                    : (int) $team;
                if ($teamId <= 0 || in_array($teamId, $teams, true)) {
                    throw new InvalidArgumentException('As equipes do jogo devem ser válidas e distintas.');
                }
                $teams[] = $teamId;
            }
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
            'equipes' => $teams,
        ]);
    }

}
