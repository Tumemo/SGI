<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\TipoModalidadeRepository;
use mysqli;
use RuntimeException;

final class MysqliTipoModalidadeRepository implements TipoModalidadeRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $sql = 'SELECT id_tipo_modalidade, nome_tipo_modalidade FROM tipos_modalidades WHERE 1=1';
        $types = '';
        $params = [];
        if ((int) ($filters['id_tipo_modalidade'] ?? 0) > 0) {
            $sql .= ' AND id_tipo_modalidade = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_tipo_modalidade'];
        }
        if ((string) ($filters['busca'] ?? '') !== '') {
            $sql .= ' AND nome_tipo_modalidade LIKE ?';
            $types .= 's';
            $params[] = '%' . (string) $filters['busca'] . '%';
        }
        $sql .= ' ORDER BY nome_tipo_modalidade ASC';
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar tipos de modalidade.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar tipos de modalidade.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade) VALUES (?, ?)',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar tipo de modalidade.');
        }
        $name = (string) $data['nome_tipo_modalidade'];
        $status = (string) $data['status_tipo_modalidade'];
        $statement->bind_param('ss', $name, $status);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar tipo de modalidade.');
        }
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach (['nome_tipo_modalidade' => 's', 'status_tipo_modalidade' => 's'] as $field => $type) {
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
            'UPDATE tipos_modalidades SET ' . implode(', ', $fields) . ' WHERE id_tipo_modalidade = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar tipo de modalidade.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar tipo de modalidade.');
        }
        $found = $statement->affected_rows > 0;
        if (!$found) {
            $check = $this->connection->prepare('SELECT 1 FROM tipos_modalidades WHERE id_tipo_modalidade = ?');
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
