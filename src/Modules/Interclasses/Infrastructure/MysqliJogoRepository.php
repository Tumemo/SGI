<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Infrastructure;

use App\Modules\Interclasses\Domain\JogoRepository;
use mysqli;
use RuntimeException;

final class MysqliJogoRepository implements JogoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function localConflict(string $date, int $localId, string $start, string $end, ?int $currentId = null): ?string
    {
        if ($start === '00:00:00' || $end === '00:00:00') {
            return null;
        }
        $sql = "SELECT nome_jogo FROM jogos
                WHERE data_jogo = ? AND locais_id_local = ? AND status_jogo != 'Cancelado'
                  AND ? < termino_jogo AND ? > inicio_jogo";
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
