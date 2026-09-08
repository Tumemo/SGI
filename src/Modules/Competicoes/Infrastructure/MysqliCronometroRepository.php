<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\CronometroRepository;
use mysqli;
use RuntimeException;

final class MysqliCronometroRepository implements CronometroRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findForUpdate(int $gameId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT status_jogo, duracao_jogo, tempo_extra_jogo, tempo_restante_jogo,
                    UNIX_TIMESTAMP(data_inicio_real) AS data_inicio_epoch
             FROM jogos WHERE id_jogo = ? LIMIT 1 FOR UPDATE',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível carregar o cronômetro.');
        }
        $statement->bind_param('i', $gameId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível carregar o cronômetro.');
        }
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($row === null) {
            return null;
        }
        return [
            'status_jogo' => (string) $row['status_jogo'],
            'duracao_jogo' => $row['duracao_jogo'],
            'tempo_extra_jogo' => $row['tempo_extra_jogo'],
            'tempo_restante_jogo' => $row['tempo_restante_jogo'],
            'data_inicio_real' => $row['data_inicio_epoch'] === null ? null : (int) $row['data_inicio_epoch'],
        ];
    }

    public function save(int $gameId, array $snapshot): void
    {
        if ($snapshot['data_inicio_real'] === null) {
            $statement = $this->connection->prepare(
                'UPDATE jogos
                 SET status_jogo = ?, duracao_jogo = ?, tempo_extra_jogo = ?,
                     tempo_restante_jogo = ?, data_inicio_real = NULL
                 WHERE id_jogo = ?',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível preparar o cronômetro.');
            }
            $status = $snapshot['status_jogo'];
            $duration = $snapshot['duracao_jogo'];
            $extra = $snapshot['tempo_extra_jogo'];
            $remaining = $snapshot['tempo_restante_jogo'];
            $statement->bind_param('siiii', $status, $duration, $extra, $remaining, $gameId);
        } else {
            $statement = $this->connection->prepare(
                'UPDATE jogos
                 SET status_jogo = ?, duracao_jogo = ?, tempo_extra_jogo = ?,
                     tempo_restante_jogo = ?, data_inicio_real = FROM_UNIXTIME(?)
                 WHERE id_jogo = ?',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível preparar o cronômetro.');
            }
            $status = $snapshot['status_jogo'];
            $duration = $snapshot['duracao_jogo'];
            $extra = $snapshot['tempo_extra_jogo'];
            $remaining = $snapshot['tempo_restante_jogo'];
            $reference = $snapshot['data_inicio_real'];
            $statement->bind_param('siiiii', $status, $duration, $extra, $remaining, $reference, $gameId);
        }
        if (!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            throw new RuntimeException($message !== '' ? $message : 'Não foi possível salvar o cronômetro.');
        }
        $statement->close();
    }
}
