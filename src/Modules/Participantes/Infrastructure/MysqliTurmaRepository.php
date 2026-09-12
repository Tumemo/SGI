<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

use App\Modules\Participantes\Application\TurmaVinculadaException;
use App\Modules\Participantes\Domain\TurmaRepository;
use App\Modules\Participantes\Domain\TurmaScopeRules;
use App\Shared\Database\Transaction;
use InvalidArgumentException;
use mysqli;
use RuntimeException;
use Throwable;

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
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $categoryId = (int) $data['categorias_id_categoria'];
        $name = (string) $data['nome_turma'];
        $shift = $data['turno_turma'];
        $fantasy = $data['nome_fantasia_turma'];
        $status = (string) $data['status_turma'];

        Transaction::begin($this->connection);
        try {
            $this->lockEditions([$interclasseId]);
            $categories = $this->lockCategories([$categoryId]);
            if (!TurmaScopeRules::categoryMatchesEdition($interclasseId, $categories[$categoryId] ?? null)) {
                throw new InvalidArgumentException('A categoria informada precisa estar ativa e pertencer à edição da turma.');
            }

            $statement = $this->connection->prepare(
                'INSERT INTO turmas (interclasses_id_interclasse, categorias_id_categoria, nome_turma,
                    turno_turma, nome_fantasia_turma, status_turma)
                 VALUES (?, ?, ?, ?, ?, ?)',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível criar turma.');
            }
            $statement->bind_param('iissss', $interclasseId, $categoryId, $name, $shift, $fantasy, $status);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível criar turma.');
            }
            $id = (int) $this->connection->insert_id;
            $statement->close();
            Transaction::commit($this->connection);
            return $id;
        } catch (Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
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

        Transaction::begin($this->connection);
        try {
            $snapshot = $this->classScope($id, false);
            if ($snapshot === null) {
                Transaction::commit($this->connection);
                return false;
            }

            $changesScope = array_key_exists('interclasses_id_interclasse', $data)
                || array_key_exists('categorias_id_categoria', $data);
            if ($changesScope) {
                $targetEditionId = (int) ($data['interclasses_id_interclasse'] ?? $snapshot['interclasses_id_interclasse']);
                $targetCategoryId = (int) ($data['categorias_id_categoria'] ?? $snapshot['categorias_id_categoria']);
                $this->lockEditions([(int) $snapshot['interclasses_id_interclasse'], $targetEditionId]);
                $categories = $this->lockCategories([(int) $snapshot['categorias_id_categoria'], $targetCategoryId]);
                $current = $this->classScope($id, true);
                if ($current === null) {
                    Transaction::commit($this->connection);
                    return false;
                }
                if ($current !== $snapshot) {
                    throw new InvalidArgumentException('A turma foi alterada; recarregue os dados e tente novamente.');
                }
                if (!TurmaScopeRules::categoryMatchesEdition($targetEditionId, $categories[$targetCategoryId] ?? null)) {
                    throw new InvalidArgumentException('A categoria informada precisa estar ativa e pertencer à edição da turma.');
                }
                if ($targetEditionId !== (int) $current['interclasses_id_interclasse']) {
                    if (!TurmaScopeRules::canTransferEdition((int) $current['interclasses_id_interclasse'], $targetEditionId, $this->hasDescendants($id))) {
                        throw new InvalidArgumentException('Não é possível transferir uma turma que possui vínculos ou histórico.');
                    }
                }
            } elseif ($this->classScope($id, true) === null) {
                Transaction::commit($this->connection);
                return false;
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
            $statement->close();
            Transaction::commit($this->connection);
            return true;
        } catch (Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
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

    /** @return array{interclasses_id_interclasse:int,categorias_id_categoria:int}|null */
    private function classScope(int $id, bool $forUpdate): ?array
    {
        $sql = 'SELECT interclasses_id_interclasse, categorias_id_categoria FROM turmas WHERE id_turma = ? LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->connection->prepare($sql);
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
        if ($row === null) {
            return null;
        }
        return [
            'interclasses_id_interclasse' => (int) $row['interclasses_id_interclasse'],
            'categorias_id_categoria' => (int) $row['categorias_id_categoria'],
        ];
    }

    /** @param list<int> $editionIds */
    private function lockEditions(array $editionIds): void
    {
        $editionIds = array_values(array_unique($editionIds));
        sort($editionIds, SORT_NUMERIC);
        if ($editionIds === [] || min($editionIds) <= 0) {
            throw new InvalidArgumentException('O interclasse informado é inválido.');
        }
        $placeholders = implode(',', array_fill(0, count($editionIds), '?'));
        $statement = $this->connection->prepare(
            "SELECT id_interclasse FROM interclasses WHERE id_interclasse IN ({$placeholders}) ORDER BY id_interclasse FOR UPDATE",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a edição da turma.');
        }
        $statement->bind_param(str_repeat('i', count($editionIds)), ...$editionIds);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a edição da turma.');
        }
        $foundIds = array_map(
            static fn (array $row): int => (int) $row['id_interclasse'],
            $statement->get_result()->fetch_all(MYSQLI_ASSOC),
        );
        $statement->close();
        if (array_diff($editionIds, $foundIds) !== []) {
            throw new InvalidArgumentException('O interclasse informado não existe.');
        }
    }

    /**
     * @param list<int> $categoryIds
     * @return array<int, array<string, mixed>>
     */
    private function lockCategories(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique($categoryIds));
        sort($categoryIds, SORT_NUMERIC);
        if ($categoryIds === [] || min($categoryIds) <= 0) {
            throw new InvalidArgumentException('A categoria informada é inválida.');
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $statement = $this->connection->prepare(
            "SELECT id_categoria, interclasses_id_interclasse, status_categoria
             FROM categorias WHERE id_categoria IN ({$placeholders}) ORDER BY id_categoria FOR UPDATE",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a categoria da turma.');
        }
        $statement->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a categoria da turma.');
        }
        $categories = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $categories[(int) $row['id_categoria']] = $row;
        }
        $statement->close();
        if (array_diff($categoryIds, array_keys($categories)) !== []) {
            throw new InvalidArgumentException('A categoria informada não existe.');
        }
        return $categories;
    }

    private function hasDescendants(int $id): bool
    {
        foreach ([
            ['usuarios', 'turmas_id_turma'],
            ['equipes', 'turmas_id_turma'],
            ['historico_arrecadacoes', 'id_turma'],
            ['pontuacoes_podio', 'id_turma'],
            ['ocorrencias_turmas', 'turmas_id_turma'],
        ] as [$table, $column]) {
            $statement = $this->connection->prepare(
                "SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1 FOR UPDATE",
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível validar os vínculos da turma.');
            }
            $statement->bind_param('i', $id);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível validar os vínculos da turma.');
            }
            $hasRows = $statement->get_result()->num_rows > 0;
            $statement->close();
            if ($hasRows) {
                return true;
            }
        }
        return false;
    }
}
