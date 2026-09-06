<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Infrastructure;

use App\Modules\Eventos\Domain\CategoriaRepository;
use mysqli;
use RuntimeException;

final class MysqliCategoriaRepository implements CategoriaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function listActive(array $filters): array
    {
        $sql = "SELECT c.id_categoria, c.nome_categoria, i.nome_interclasse
                FROM categorias c
                INNER JOIN interclasses i ON c.interclasses_id_interclasse = i.id_interclasse
                WHERE c.status_categoria = '1'";
        $types = '';
        $params = [];

        if (($filters['id_categoria'] ?? 0) > 0) {
            $sql .= ' AND c.id_categoria = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_categoria'];
        }
        if (($filters['id_interclasse'] ?? 0) > 0) {
            $sql .= ' AND c.interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
        }
        if (($filters['busca'] ?? '') !== '') {
            $sql .= ' AND c.nome_categoria LIKE ?';
            $types .= 's';
            $params[] = '%' . (string) $filters['busca'] . '%';
        }
        $sql .= ' ORDER BY c.nome_categoria ASC';

        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar categorias.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            throw new RuntimeException('Não foi possível consultar categorias.');
        }

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, ?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar categoria.');
        }
        $name = (string) $data['nome_categoria'];
        $status = (string) $data['status_categoria'];
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $statement->bind_param('ssi', $name, $status, $interclasseId);
        if (!$statement->execute()) {
            throw new RuntimeException('Não foi possível criar categoria.');
        }
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function findStatus(int $id): ?string
    {
        $statement = $this->connection->prepare('SELECT status_categoria FROM categorias WHERE id_categoria = ?');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar categoria.');
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row === null ? null : (string) $row['status_categoria'];
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach (['nome_categoria' => 's', 'status_categoria' => 's'] as $field => $type) {
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
            'UPDATE categorias SET ' . implode(', ', $fields) . ' WHERE id_categoria = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar categoria.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            throw new RuntimeException('Não foi possível atualizar categoria.');
        }
        $statement->close();
    }

    public function deactivateCascade(int $id): void
    {
        $this->connection->begin_transaction();
        try {
            $queries = [
                "UPDATE categorias SET status_categoria = '0' WHERE id_categoria = ?",
                "UPDATE turmas SET status_turma = '0' WHERE categorias_id_categoria = ?",
                "UPDATE modalidades SET status_modalidade = '0' WHERE categorias_id_categoria = ?",
                "UPDATE equipes SET status_equipe = '0' WHERE modalidades_id_modalidade IN (SELECT id_modalidade FROM modalidades WHERE categorias_id_categoria = ?)",
                "UPDATE equipes SET status_equipe = '0' WHERE turmas_id_turma IN (SELECT id_turma FROM turmas WHERE categorias_id_categoria = ?) AND status_equipe = '1'",
            ];
            foreach ($queries as $sql) {
                $statement = $this->connection->prepare($sql);
                if ($statement === false) {
                    throw new RuntimeException('Não foi possível excluir categoria.');
                }
                $statement->bind_param('i', $id);
                if (!$statement->execute()) {
                    throw new RuntimeException('Não foi possível excluir categoria.');
                }
                $statement->close();
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }
}
