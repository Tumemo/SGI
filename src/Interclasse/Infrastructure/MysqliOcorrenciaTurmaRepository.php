<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\OcorrenciaTurmaRepository;
use mysqli;
use RuntimeException;

final class MysqliOcorrenciaTurmaRepository implements OcorrenciaTurmaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $sql = 'SELECT ot.*, t.nome_turma, t.nome_fantasia_turma,
                       c.nome_categoria, u.nome_usuario
                FROM ocorrencias_turmas ot
                INNER JOIN turmas t ON t.id_turma = ot.turmas_id_turma
                INNER JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
                LEFT JOIN usuarios u ON u.id_usuario = ot.usuarios_id_usuario
                WHERE ot.interclasses_id_interclasse = ?';
        $types = 'i';
        $params = [(int) $filters['id_interclasse']];
        if ((int) ($filters['id_turma'] ?? 0) > 0) {
            $sql .= ' AND ot.turmas_id_turma = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_turma'];
        }
        $sql .= ' ORDER BY ot.data_registro DESC';
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar ocorrências da turma.');
        }
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar ocorrências da turma.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO ocorrencias_turmas (turmas_id_turma, interclasses_id_interclasse,
                titulo_ocorrencia, descricao_ocorrencia, pontos_descontados, data_ocorrencia, usuarios_id_usuario)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível registrar ocorrência da turma.');
        }
        $teamId = (int) $data['turmas_id_turma'];
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $title = (string) $data['titulo_ocorrencia'];
        $description = (string) $data['descricao_ocorrencia'];
        $points = (int) $data['pontos_descontados'];
        $date = (string) $data['data_ocorrencia'];
        $userId = $data['usuarios_id_usuario'] === null ? null : (int) $data['usuarios_id_usuario'];
        $statement->bind_param('iissisi', $teamId, $interclasseId, $title, $description, $points, $date, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível registrar ocorrência da turma.');
        }
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM ocorrencias_turmas WHERE id_ocorrencia_turma = ?');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível excluir ocorrência da turma.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível excluir ocorrência da turma.');
        }
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }
}
