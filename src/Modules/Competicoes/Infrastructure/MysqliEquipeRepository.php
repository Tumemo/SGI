<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Application\EquipeLimiteException;
use App\Modules\Competicoes\Domain\EquipeCapacityRules;
use App\Modules\Competicoes\Domain\EquipeRepository;
use App\Shared\Database\Transaction;
use mysqli;
use RuntimeException;

final class MysqliEquipeRepository implements EquipeRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(array $data): array
    {
        $modalityId = (int) $data['modalidades_id_modalidade'];
        $classId = (int) $data['turmas_id_turma'];
        $status = (string) ($data['status_equipe'] ?? '1');

        Transaction::begin($this->connection);
        try {
            $scope = $this->lockScope($modalityId, $classId);
            if ($status === '1') {
                $active = $this->activeCount($modalityId, $classId);
                $this->assertCapacity($scope['max_equipes'], $active);
            }
            $name = $data['nome_equipe'] === null ? $this->generateName($modalityId, $classId) : (string) $data['nome_equipe'];

            $statement = $this->connection->prepare(
                'INSERT INTO equipes (modalidades_id_modalidade, turmas_id_turma, status_equipe, nome_equipe)
                 VALUES (?, ?, ?, ?)',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível criar equipe.');
            }
            $statement->bind_param('iiss', $modalityId, $classId, $status, $name);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível criar equipe.');
            }
            $id = (int) $this->connection->insert_id;
            $statement->close();
            Transaction::commit($this->connection);

            return ['id_equipe' => $id, 'nome_equipe' => $name];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function addUsers(int $teamId, array $userIds): void
    {
        $this->connection->begin_transaction();
        try {
            $statement = $this->connection->prepare(
                'INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível vincular usuários à equipe.');
            }
            $userId = 0;
            $statement->bind_param('ii', $teamId, $userId);
            foreach ($userIds as $candidate) {
                $userId = (int) $candidate;
                if (!$statement->execute()) {
                    $statement->close();
                    throw new RuntimeException('Não foi possível vincular usuários à equipe.');
                }
            }
            $statement->close();
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function removeUser(int $teamId, int $userId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível remover aluno da equipe.');
        }
        $statement->bind_param('ii', $teamId, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível remover aluno da equipe.');
        }
        $statement->close();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'nome_equipe' => 's',
            'modalidades_id_modalidade' => 'i',
            'turmas_id_turma' => 'i',
            'status_equipe' => 's',
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

        Transaction::begin($this->connection);
        try {
            $current = $this->one(
                'SELECT modalidades_id_modalidade, turmas_id_turma, status_equipe
                 FROM equipes WHERE id_equipe = ? LIMIT 1 FOR UPDATE',
                'i',
                [$id],
            );
            if ($current === null) {
                Transaction::rollback($this->connection);
                return false;
            }
            $targetModality = array_key_exists('modalidades_id_modalidade', $data)
                ? (int) $data['modalidades_id_modalidade']
                : (int) $current['modalidades_id_modalidade'];
            $targetClass = array_key_exists('turmas_id_turma', $data)
                ? (int) $data['turmas_id_turma']
                : (int) $current['turmas_id_turma'];
            $targetStatus = array_key_exists('status_equipe', $data)
                ? (string) $data['status_equipe']
                : (string) $current['status_equipe'];
            $scope = $this->lockScope($targetModality, $targetClass);
            if ($targetStatus === '1') {
                $this->assertCapacity($scope['max_equipes'], $this->activeCount($targetModality, $targetClass, $id));
            }

            $values[] = $id;
            $types .= 'i';
            $statement = $this->connection->prepare(
                'UPDATE equipes SET ' . implode(', ', $fields) . ' WHERE id_equipe = ?',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível atualizar equipe.');
            }
            $statement->bind_param($types, ...$values);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível atualizar equipe.');
            }
            $found = $statement->affected_rows > 0 || $this->exists($id);
            $statement->close();
            Transaction::commit($this->connection);
            return $found;
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function deactivate(int $id): bool
    {
        $statement = $this->connection->prepare("UPDATE equipes SET status_equipe = '0' WHERE id_equipe = ?");
        if ($statement === false) {
            throw new RuntimeException('Não foi possível excluir equipe.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível excluir equipe.');
        }
        $found = $statement->affected_rows > 0 || $this->exists($id);
        $statement->close();
        return $found;
    }

    /**
     * @return array{nome:string,max_equipes:?int}
     */
    private function modality(int $id): array
    {
        $statement = $this->connection->prepare(
            'SELECT nome_modalidade, max_equipes FROM modalidades WHERE id_modalidade = ? LIMIT 1',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar modalidade.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar modalidade.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new RuntimeException('Modalidade não encontrada.');
        }
        return [
            'nome' => (string) $row['nome_modalidade'],
            'max_equipes' => $row['max_equipes'] === null ? null : (int) $row['max_equipes'],
        ];
    }

    /** @return array{max_equipes:?int} */
    private function lockScope(int $modalityId, int $classId): array
    {
        $modality = $this->one(
            'SELECT nome_modalidade, max_equipes, categorias_id_categoria, interclasses_id_interclasse, status_modalidade
             FROM modalidades WHERE id_modalidade = ? LIMIT 1 FOR UPDATE',
            'i',
            [$modalityId],
        );
        if ($modality === null || (string) $modality['status_modalidade'] !== '1') {
            throw new RuntimeException('Modalidade não encontrada ou inativa.');
        }
        $class = $this->one(
            'SELECT categorias_id_categoria, interclasses_id_interclasse, status_turma
             FROM turmas WHERE id_turma = ? LIMIT 1 FOR UPDATE',
            'i',
            [$classId],
        );
        if ($class === null || (string) $class['status_turma'] !== '1') {
            throw new RuntimeException('Turma não encontrada ou inativa.');
        }
        if ((int) $modality['interclasses_id_interclasse'] !== (int) $class['interclasses_id_interclasse']) {
            throw new RuntimeException('Modalidade e turma não pertencem ao mesmo interclasse.');
        }
        if ((int) $modality['categorias_id_categoria'] !== (int) $class['categorias_id_categoria']) {
            throw new RuntimeException('Modalidade e turma não pertencem à mesma categoria.');
        }
        return ['max_equipes' => $modality['max_equipes'] === null ? null : (int) $modality['max_equipes']];
    }

    private function activeCount(int $modalityId, int $classId, ?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) FROM equipes
                WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'";
        $params = [$modalityId, $classId];
        $types = 'ii';
        if ($excludeId !== null) {
            $sql .= ' AND id_equipe <> ?';
            $params[] = $excludeId;
            $types .= 'i';
        }
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível contar equipes ativas.');
        }
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private function assertCapacity(?int $maxTeams, int $active): void
    {
        if (!EquipeCapacityRules::podeAtivar($maxTeams, $active)) {
            throw new EquipeLimiteException(
                'Limite de ' . $maxTeams . ' equipes por turma atingido para esta modalidade.',
            );
        }
    }

    /** @param list<int> $params @return array<string,mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar equipe.');
        }
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    private function className(int $id): string
    {
        $statement = $this->connection->prepare('SELECT nome_turma FROM turmas WHERE id_turma = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar turma.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar turma.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new RuntimeException('Turma não encontrada.');
        }
        return trim((string) $row['nome_turma']);
    }

    private function generateName(int $modalityId, int $classId): string
    {
        $modality = $this->modality($modalityId);
        $className = $this->className($classId);
        $statement = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM equipes
             WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível numerar equipe.');
        }
        $statement->bind_param('ii', $modalityId, $classId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível numerar equipe.');
        }
        $total = (int) (($statement->get_result()->fetch_assoc()['total'] ?? 0));
        $statement->close();
        $number = $total + 1;
        $base = preg_replace('/\s*-\s*(MA|MI|FE|MASC|FEM|MISTO|MISTA)$/i', '', trim($modality['nome'])) ?? $modality['nome'];
        $name = $base . ' - ' . $number;
        return $className !== '' ? $className . ' ' . $name : $name;
    }

    private function exists(int $id): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM equipes WHERE id_equipe = ? LIMIT 1');
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
