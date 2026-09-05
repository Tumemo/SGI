<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Application\LocalVinculadoException;
use App\Interclasse\Domain\LocalRepository;
use mysqli;
use RuntimeException;

final class MysqliLocalRepository implements LocalRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $sql = 'SELECT id_local, nome_local, disponivel_local, carga_local FROM locais WHERE 1=1';
        $types = '';
        $params = [];

        if ((int) ($filters['id_local'] ?? 0) > 0) {
            $sql .= ' AND id_local = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_local'];
        }
        if (array_key_exists('disponivel', $filters) && $filters['disponivel'] !== '') {
            $sql .= ' AND disponivel_local = ?';
            $types .= 's';
            $params[] = (string) $filters['disponivel'];
        }
        if ((string) ($filters['busca'] ?? '') !== '') {
            $sql .= ' AND nome_local LIKE ?';
            $types .= 's';
            $params[] = '%' . (string) $filters['busca'] . '%';
        }
        if ((int) ($filters['id_interclasse'] ?? 0) > 0) {
            $sql .= ' AND interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
        }
        $sql .= ' ORDER BY nome_local ASC';

        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar locais.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar locais.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            "INSERT INTO locais (nome_local, disponivel_local, carga_local, status_local, interclasses_id_interclasse) VALUES (?, ?, ?, '1', ?)",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar local.');
        }
        $name = (string) $data['nome_local'];
        $available = (string) $data['disponivel_local'];
        $load = $data['carga_local'] === null ? null : (int) $data['carga_local'];
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $statement->bind_param('ssii', $name, $available, $load, $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar local.');
        }
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function update(int $id, array $data, ?int $interclasseId = null): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach (['nome_local' => 's', 'status_local' => 's', 'disponivel_local' => 's'] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = ?';
                $values[] = $data[$field];
                $types .= $type;
            }
        }
        if (array_key_exists('carga_local', $data)) {
            $fields[] = 'carga_local = ?';
            $values[] = $data['carga_local'] === null ? 0 : (int) $data['carga_local'];
            $types .= 'i';
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum campo válido para atualizar.');
        }
        $sql = 'UPDATE locais SET ' . implode(', ', $fields) . ' WHERE id_local = ?';
        $values[] = $id;
        $types .= 'i';
        if ($interclasseId !== null) {
            $sql .= ' AND interclasses_id_interclasse = ?';
            $values[] = $interclasseId;
            $types .= 'i';
        }
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar local.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar local.');
        }
        $found = $statement->affected_rows > 0 || $this->exists($id, $interclasseId);
        $statement->close();
        return $found;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM locais WHERE id_local = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível excluir local.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $errno = $statement->errno;
            $statement->close();
            if ($errno === 1451) {
                throw new LocalVinculadoException();
            }
            throw new RuntimeException('Não foi possível excluir local.');
        }
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }

    private function exists(int $id, ?int $interclasseId): bool
    {
        $sql = 'SELECT 1 FROM locais WHERE id_local = ?';
        $types = 'i';
        $params = [$id];
        if ($interclasseId !== null) {
            $sql .= ' AND interclasses_id_interclasse = ?';
            $types .= 'i';
            $params[] = $interclasseId;
        }
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            return false;
        }
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
}
