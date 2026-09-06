<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\PartidaRepository;
use mysqli;
use RuntimeException;

final class MysqliPartidaRepository implements PartidaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /**
     * @param array<string, int|string> $fields
     */
    public function update(int $id, array $fields): bool
    {
        $definitions = [
            'jogos_id_jogo' => 'i',
            'equipes_id_equipe' => 'i',
            'resultado_partida' => 'i',
            'status_partida' => 's',
        ];
        $columns = [];
        $params = [];
        $types = '';
        foreach ($fields as $field => $value) {
            if (!isset($definitions[$field])) {
                throw new RuntimeException('Campo de partida não permitido.');
            }
            $columns[] = "{$field} = ?";
            $params[] = $value;
            $types .= $definitions[$field];
        }
        if ($columns === []) {
            throw new RuntimeException('Nenhum campo de partida informado.');
        }

        $types .= 'i';
        $params[] = $id;
        $statement = $this->connection->prepare(
            'UPDATE partidas SET ' . implode(', ', $columns) . ' WHERE id_partida = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar atualização da partida.');
        }
        $statement->bind_param($types, ...$params);
        $success = $statement->execute();
        $statement->close();
        if (!$success) {
            throw new RuntimeException('Não foi possível atualizar a partida.');
        }
        return true;
    }
}
