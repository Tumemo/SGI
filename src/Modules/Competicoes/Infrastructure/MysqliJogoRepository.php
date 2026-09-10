<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\JogoRepository;
use mysqli;
use RuntimeException;

final class MysqliJogoRepository implements JogoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function localConflict(string $date, int $localId, string $start, string $end, ?int $currentId = null): ?string
    {
        if ($start === '' || $end === '' || $start === '00:00:00' || $end === '00:00:00') {
            return null;
        }
        $sql = "SELECT nome_jogo FROM jogos
                WHERE data_jogo = ? AND locais_id_local = ?
                  AND status_jogo IN ('Agendado', 'Iniciado', 'Pausado')
                  AND ? < ADDTIME(termino_jogo, '00:10:00')
                  AND ADDTIME(?, '00:10:00') > inicio_jogo";
        $types = 'siss';
        $params = [$date, $localId, $start, $end];
        if ($currentId !== null && $currentId > 0) {
            $sql .= ' AND id_jogo != ?';
            $types .= 'i';
            $params[] = $currentId;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar conflito de horário.');
        }
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar conflito de horário.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row
            ? 'Já existe um jogo agendado neste mesmo local com conflito de horário (' . $row['nome_jogo'] . ').'
            : null;
    }

    public function create(array $data): int
    {
        $this->connection->begin_transaction();
        try {
            $id = $this->insertGame($data);
            $teamIds = array_values(array_map('intval', is_array($data['equipes'] ?? null) ? $data['equipes'] : []));
            if ($teamIds !== []) {
                $check = $this->connection->prepare(
                    "SELECT 1 FROM equipes
                     WHERE id_equipe = ? AND modalidades_id_modalidade = ? AND status_equipe = '1'
                     LIMIT 1",
                );
                $partida = $this->connection->prepare(
                    'INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida)
                     VALUES (?, ?, 0, \'1\')',
                );
                if ($check === false || $partida === false) {
                    if ($check !== false) {
                        $check->close();
                    }
                    if ($partida !== false) {
                        $partida->close();
                    }
                    throw new RuntimeException('Não foi possível criar os participantes do jogo.');
                }
                $modalityId = (int) $data['modalidades_id_modalidade'];
                $teamId = 0;
                $check->bind_param('ii', $teamId, $modalityId);
                $partida->bind_param('ii', $id, $teamId);
                foreach ($teamIds as $teamId) {
                    if (!$check->execute() || $check->get_result()->num_rows !== 1 || !$partida->execute()) {
                        $check->close();
                        $partida->close();
                        throw new RuntimeException('As equipes do jogo não pertencem à modalidade informada.');
                    }
                }
                $check->close();
                $partida->close();
            }
            $this->connection->commit();
            return $id;
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    private function insertGame(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo,
                modalidades_id_modalidade, locais_id_local, status_jogo)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar jogo.');
        }
        $name = (string) $data['nome_jogo'];
        $date = (string) $data['data_jogo'];
        $start = (string) $data['inicio_jogo'];
        $end = (string) $data['termino_jogo'];
        $modalityId = (int) $data['modalidades_id_modalidade'];
        $localId = (int) $data['locais_id_local'];
        $status = (string) $data['status_jogo'];
        $statement->bind_param('ssssiis', $name, $date, $start, $end, $modalityId, $localId, $status);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar jogo.');
        }
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }
}
