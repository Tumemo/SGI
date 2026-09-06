<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use App\Modules\Resultados\Domain\RankingRepository;
use mysqli;
use RuntimeException;

final class MysqliRankingRepository implements RankingRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma,
                    turmas.nome_fantasia_turma, turmas.pontuacao_turma AS pontuacao_sem_penalidade,
                    (turmas.pontuacao_turma - COALESCE(penalidades.total_penalidades, 0)) AS pontuacao_turma,
                    interclasses.nome_interclasse, interclasses.status_interclasse AS status_interclasse,
                    categorias.nome_categoria
                FROM turmas
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
                LEFT JOIN (
                    SELECT turmas_id_turma, SUM(total) AS total_penalidades FROM (
                        SELECT ot.turmas_id_turma, SUM(ot.pontos_descontados) AS total
                        FROM ocorrencias_turmas ot GROUP BY ot.turmas_id_turma
                        UNION ALL
                        SELECT u.turmas_id_turma, SUM(o.penalidade) AS total
                        FROM ocorrencias o
                        INNER JOIN usuarios u ON o.usuarios_id_usuario = u.id_usuario
                        WHERE o.status_ocorrencia = '1'
                        GROUP BY u.turmas_id_turma
                    ) sub GROUP BY turmas_id_turma
                ) penalidades ON penalidades.turmas_id_turma = turmas.id_turma
                WHERE 1=1";
        $types = '';
        $params = [];
        if ((int) ($filters['id_turma'] ?? 0) > 0) {
            $sql .= ' AND turmas.id_turma = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_turma'];
        }
        if ((int) ($filters['id_interclasse'] ?? 0) > 0) {
            $sql .= ' AND turmas.interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
        }
        if ((int) ($filters['id_categoria'] ?? 0) > 0) {
            $sql .= ' AND turmas.categorias_id_categoria = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_categoria'];
        }
        if ((string) ($filters['turno'] ?? '') !== '') {
            $sql .= ' AND turmas.turno_turma = ?';
            $types .= 's';
            $params[] = (string) $filters['turno'];
        }
        if ((string) ($filters['busca'] ?? '') !== '') {
            $sql .= ' AND (turmas.nome_turma LIKE ? OR turmas.nome_fantasia_turma LIKE ?)';
            $types .= 'ss';
            $search = '%' . (string) $filters['busca'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        $sql .= ' ORDER BY pontuacao_turma DESC, turmas.nome_turma ASC';

        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar ranking.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar ranking.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function updateTeam(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'interclasses_id_interclasse' => 'i',
            'nome_turma' => 's',
            'turno_turma' => 's',
            'nome_fantasia_turma' => 's',
            'categorias_id_categoria' => 'i',
            'status_turma' => 's',
            'pontuacao_turma' => 'i',
        ] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = ?';
                $values[] = $data[$field];
                $types .= $type;
            }
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum campo válido para atualizar.');
        }
        $values[] = $id;
        $types .= 'i';
        $statement = $this->connection->prepare(
            'UPDATE turmas SET ' . implode(', ', $fields) . ' WHERE id_turma = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar turma.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar turma.');
        }
        $found = $statement->affected_rows > 0 || $this->exists($id);
        $statement->close();
        return $found;
    }

    private function exists(int $id): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM turmas WHERE id_turma = ? LIMIT 1');
        if ($statement === false) {
            return false;
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
}
