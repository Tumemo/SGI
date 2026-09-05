<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\PontuacaoRepository;
use mysqli;
use RuntimeException;

final class MysqliPontuacaoRepository implements PontuacaoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function ranking(): array
    {
        $statement = $this->connection->prepare(
            'SELECT turmas.nome_turma, modalidades.nome_modalidade, SUM(pontuacao_interclasse.pontos) AS total_pontos
             FROM pontuacao_interclasse
             INNER JOIN turmas ON pontuacao_interclasse.turmas_id_turma = turmas.id_turma
             INNER JOIN modalidades ON pontuacao_interclasse.modalidades_id_modalidade = modalidades.id_modalidade
             GROUP BY turmas.id_turma, modalidades.id_modalidade
             ORDER BY total_pontos DESC',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar a pontuação.');
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar a pontuação.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function atualizar(int $id, int $pontos): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE pontuacao_interclasse SET pontos = ? WHERE id_pontuacao = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar a pontuação.');
        }
        $statement->bind_param('ii', $pontos, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar a pontuação.');
        }
        $found = $statement->affected_rows > 0;
        if (!$found) {
            $check = $this->connection->prepare('SELECT 1 FROM pontuacao_interclasse WHERE id_pontuacao = ?');
            if ($check !== false) {
                $check->bind_param('i', $id);
                $check->execute();
                $found = $check->get_result()->num_rows > 0;
                $check->close();
            }
        }
        $statement->close();
        return $found;
    }
}
