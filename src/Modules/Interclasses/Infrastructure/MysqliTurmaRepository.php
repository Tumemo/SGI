<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Infrastructure;

use App\Modules\Interclasses\Application\TurmaVinculadaException;
use App\Modules\Interclasses\Domain\TurmaRepository;
use mysqli;
use RuntimeException;

final class MysqliTurmaRepository implements TurmaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma,
                    turmas.nome_fantasia_turma, turmas.categorias_id_categoria,
                    interclasses.nome_interclasse, categorias.nome_categoria,
                    COALESCE(alunos.qtd, 0) AS qtd_alunos
                FROM turmas
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
                LEFT JOIN (
                    SELECT turmas_id_turma, COUNT(*) AS qtd
                    FROM usuarios
                    WHERE status_usuario = '1' AND nivel_usuario = '3'
                    GROUP BY turmas_id_turma
                ) alunos ON alunos.turmas_id_turma = turmas.id_turma
                WHERE turmas.status_turma = '1'";
        $types = '';
        $params = [];
        if ((int) ($filters['id_interclasse'] ?? 0) > 0) {
            $sql .= ' AND turmas.interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
            $sql .= ' AND categorias.interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
        }
        foreach (['id_turma' => 'turmas.id_turma', 'id_categoria' => 'turmas.categorias_id_categoria'] as $field => $column) {
            if ((int) ($filters[$field] ?? 0) > 0) {
                $sql .= ' AND ' . $column . ' = ?';
                $types .= 'i';
                $params[] = (int) $filters[$field];
            }
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
        $sql .= ' ORDER BY turmas.nome_turma ASC';

        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar turmas.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar turmas.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO turmas (interclasses_id_interclasse, categorias_id_categoria, nome_turma,
                turno_turma, nome_fantasia_turma, status_turma)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar turma.');
        }
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $categoryId = (int) $data['categorias_id_categoria'];
        $name = (string) $data['nome_turma'];
        $shift = $data['turno_turma'];
        $fantasy = $data['nome_fantasia_turma'];
        $status = (string) $data['status_turma'];
        $statement->bind_param('iissss', $interclasseId, $categoryId, $name, $shift, $fantasy, $status);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar turma.');
        }
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function duplicateExists(int $interclasseId, string $name, ?string $shift): bool
    {
        $sql = 'SELECT 1 FROM turmas WHERE interclasses_id_interclasse = ? AND nome_turma = ?';
        $types = 'is';
        $params = [$interclasseId, $name];
        if ($shift === null) {
            $sql .= ' AND turno_turma IS NULL';
        } else {
            $sql .= ' AND turno_turma = ?';
            $types .= 's';
            $params[] = $shift;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar turma.');
        }
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar turma.');
        }
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'interclasses_id_interclasse' => 'i',
            'categorias_id_categoria' => 'i',
            'nome_turma' => 's',
            'turno_turma' => 's',
            'nome_fantasia_turma' => 's',
            'status_turma' => 's',
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

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM turmas WHERE id_turma = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível excluir turma.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $errno = $statement->errno;
            $statement->close();
            if ($errno === 1451) {
                throw new TurmaVinculadaException();
            }
            throw new RuntimeException('Não foi possível excluir turma.');
        }
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
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
