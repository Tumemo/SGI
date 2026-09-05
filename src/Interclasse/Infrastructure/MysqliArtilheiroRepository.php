<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\ArtilheiroRepository;
use mysqli;
use RuntimeException;

final class MysqliArtilheiroRepository implements ArtilheiroRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(int $userId, int $gameId, int $goals): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, num_gol) VALUES (?, ?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível registrar artilharia.');
        }
        $statement->bind_param('iii', $userId, $gameId, $goals);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível registrar artilharia.');
        }
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function update(int $userId, int $gameId, int $goals): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE artilheiros SET num_gol = ? WHERE usuarios_id_usuario = ? AND jogos_id_jogo = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar artilharia.');
        }
        $statement->bind_param('iii', $goals, $userId, $gameId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar artilharia.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }
}
