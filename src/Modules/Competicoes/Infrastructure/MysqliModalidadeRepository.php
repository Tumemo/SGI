<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\ModalidadeRepository;
use App\Modules\Competicoes\Domain\ModalidadeScopeRules;
use App\Shared\Database\Transaction;
use InvalidArgumentException;
use mysqli;
use RuntimeException;

final class MysqliModalidadeRepository implements ModalidadeRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function list(array $filters): array
    {
        $idTurma = (int) ($filters['id_turma'] ?? 0);
        $selectTurma = $idTurma > 0
            ? ', (SELECT COUNT(DISTINCT eu.usuarios_id_usuario)
                    FROM equipes_has_usuarios eu
                    INNER JOIN equipes e2 ON e2.id_equipe = eu.equipes_id_equipe
                    INNER JOIN usuarios u2 ON u2.id_usuario = eu.usuarios_id_usuario
                    WHERE e2.modalidades_id_modalidade = modalidades.id_modalidade
                      AND e2.turmas_id_turma = ?
                      AND e2.status_equipe = \'1\' AND u2.status_usuario = \'1\') AS qtd_inscritos_turma'
            : '';
        $sql = 'SELECT DISTINCT modalidades.id_modalidade, modalidades.nome_modalidade,
                    modalidades.genero_modalidade, modalidades.max_inscrito_modalidade,
                    modalidades.max_equipes, modalidades.status_modalidade,
                    modalidades.categorias_id_categoria, tipos_modalidades.nome_tipo_modalidade,
                    tipos_modalidades.id_tipo_modalidade, categorias.nome_categoria,
                    modalidades.interclasses_id_interclasse, interclasses.nome_interclasse,
                    (SELECT COUNT(*) FROM equipes e2 WHERE e2.modalidades_id_modalidade = modalidades.id_modalidade
                      AND e2.status_equipe = \'1\') AS qtd_equipes,
                    (SELECT COUNT(*) FROM turmas t2 WHERE t2.categorias_id_categoria = modalidades.categorias_id_categoria)
                      AS max_turmas' . $selectTurma . '
             FROM modalidades
             INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade
             INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
             INNER JOIN interclasses ON interclasses.id_interclasse = modalidades.interclasses_id_interclasse';
        if (array_key_exists('ano', $filters)) {
            $sql .= ' INNER JOIN jogos ON jogos.modalidades_id_modalidade = modalidades.id_modalidade';
        }
        $sql .= " WHERE modalidades.status_modalidade = '1'";
        $types = $idTurma > 0 ? 'i' : '';
        $params = $idTurma > 0 ? [$idTurma] : [];
        foreach ([
            'id_interclasse' => ['i', 'modalidades.interclasses_id_interclasse'],
            'id_modalidade' => ['i', 'modalidades.id_modalidade'],
            'id_categoria' => ['i', 'modalidades.categorias_id_categoria'],
            'id_tipo_modalidade' => ['i', 'modalidades.tipos_modalidades_id_tipo_modalidade'],
        ] as $field => [$type, $column]) {
            if ((int) ($filters[$field] ?? 0) > 0) {
                $sql .= ' AND ' . $column . ' = ?';
                $types .= $type;
                $params[] = (int) $filters[$field];
            }
        }
        if ((string) ($filters['genero'] ?? '') !== '') {
            $sql .= ' AND modalidades.genero_modalidade = ?';
            $types .= 's';
            $params[] = strtoupper((string) $filters['genero']);
        }

        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar modalidades.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar modalidades.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return array_map(static function (array $row): array {
            $row['tipo_competicao'] = \App\Modules\Competicoes\Domain\TipoCompeticaoRules::resolve($row);
            return $row;
        }, $rows);
    }

    public function create(array $data): int
    {
        Transaction::begin($this->connection);
        try {
            $categoryId = (int) $data['categorias_id_categoria'];
            $interclasseId = (int) $data['interclasses_id_interclasse'];
            $this->lockEditions([$interclasseId]);
            $categories = $this->lockCategories([$categoryId]);
            ModalidadeScopeRules::assertCategoryMatchesEdition(
                (int) $categories[$categoryId]['interclasses_id_interclasse'],
                $interclasseId,
                (string) $categories[$categoryId]['status_categoria'],
            );

            $statement = $this->connection->prepare(
                'INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes,
                    tipos_modalidades_id_tipo_modalidade, status_modalidade, categorias_id_categoria, interclasses_id_interclasse)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível criar modalidade.');
            }
            $name = (string) $data['nome_modalidade'];
            $gender = (string) $data['genero_modalidade'];
            $maxInscritos = (int) $data['max_inscrito_modalidade'];
            $maxEquipes = $data['max_equipes'] === null ? 0 : (int) $data['max_equipes'];
            $typeId = (int) $data['tipos_modalidades_id_tipo_modalidade'];
            $status = (string) $data['status_modalidade'];
            $statement->bind_param('ssiisiii', $name, $gender, $maxInscritos, $maxEquipes, $typeId, $status, $categoryId, $interclasseId);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível criar modalidade.');
            }
            $id = (int) $this->connection->insert_id;
            $statement->close();
            Transaction::commit($this->connection);
            return $id;
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function update(int $id, array $data): bool
    {
        if (array_key_exists('categorias_id_categoria', $data) || array_key_exists('interclasses_id_interclasse', $data)) {
            return $this->updateScope($id, $data);
        }

        return $this->persistUpdates($id, $data);
    }

    /** @param array<string, mixed> $data */
    private function persistUpdates(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach (['nome_modalidade' => 's', 'genero_modalidade' => 's', 'max_inscrito_modalidade' => 'i', 'max_equipes' => 'i', 'status_modalidade' => 's', 'tipos_modalidades_id_tipo_modalidade' => 'i', 'categorias_id_categoria' => 'i', 'interclasses_id_interclasse' => 'i'] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = ?';
                $values[] = $data[$field] === null ? 0 : $data[$field];
                $types .= $type;
            }
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum campo válido para atualizar.');
        }
        $values[] = $id;
        $types .= 'i';
        $statement = $this->connection->prepare('UPDATE modalidades SET ' . implode(', ', $fields) . ' WHERE id_modalidade = ?');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar modalidade.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar modalidade.');
        }
        $found = $statement->affected_rows > 0;
        if (!$found) {
            $check = $this->connection->prepare('SELECT 1 FROM modalidades WHERE id_modalidade = ?');
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

    /** @param array<string, mixed> $data */
    private function updateScope(int $id, array $data): bool
    {
        Transaction::begin($this->connection);
        try {
            $initial = $this->modalityScope($id, false);
            if ($initial === null) {
                Transaction::rollback($this->connection);
                return false;
            }

            $targetCategoryId = (int) ($data['categorias_id_categoria'] ?? $initial['categorias_id_categoria']);
            $targetEditionId = (int) ($data['interclasses_id_interclasse'] ?? $initial['interclasses_id_interclasse']);
            $this->lockEditions([$initial['interclasses_id_interclasse'], $targetEditionId]);
            $categories = $this->lockCategories([$initial['categorias_id_categoria'], $targetCategoryId]);
            $current = $this->modalityScope($id, true);
            if ($current === null) {
                Transaction::rollback($this->connection);
                return false;
            }
            if ($current !== $initial) {
                throw new InvalidArgumentException('A modalidade foi alterada por outra operação. Recarregue os dados.');
            }

            ModalidadeScopeRules::assertCategoryMatchesEdition(
                (int) $categories[$targetCategoryId]['interclasses_id_interclasse'],
                $targetEditionId,
                (string) $categories[$targetCategoryId]['status_categoria'],
            );
            $hasRelatedData = $current['interclasses_id_interclasse'] !== $targetEditionId
                && $this->hasRelatedData($id);
            ModalidadeScopeRules::assertEditionTransferAllowed(
                $current['interclasses_id_interclasse'],
                $targetEditionId,
                $hasRelatedData,
            );

            $updated = $this->persistUpdates($id, $data);
            Transaction::commit($this->connection);
            return $updated;
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    /** @param list<int> $editionIds */
    private function lockEditions(array $editionIds): void
    {
        $editionIds = array_values(array_unique($editionIds));
        sort($editionIds, SORT_NUMERIC);
        $placeholders = implode(', ', array_fill(0, count($editionIds), '?'));
        $statement = $this->connection->prepare(
            'SELECT id_interclasse FROM interclasses WHERE id_interclasse IN (' . $placeholders . ') ORDER BY id_interclasse FOR UPDATE',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a edição da modalidade.');
        }
        $types = str_repeat('i', count($editionIds));
        $statement->bind_param($types, ...$editionIds);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a edição da modalidade.');
        }
        $found = $statement->get_result()->num_rows;
        $statement->close();
        if ($found !== count($editionIds)) {
            throw new InvalidArgumentException('A edição informada não foi encontrada.');
        }
    }

    /**
     * @param list<int> $categoryIds
     * @return array<int, array{status_categoria:string,interclasses_id_interclasse:int}>
     */
    private function lockCategories(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique($categoryIds));
        sort($categoryIds, SORT_NUMERIC);
        $placeholders = implode(', ', array_fill(0, count($categoryIds), '?'));
        $statement = $this->connection->prepare(
            'SELECT id_categoria, status_categoria, interclasses_id_interclasse
             FROM categorias WHERE id_categoria IN (' . $placeholders . ') ORDER BY id_categoria FOR UPDATE',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a categoria da modalidade.');
        }
        $types = str_repeat('i', count($categoryIds));
        $statement->bind_param($types, ...$categoryIds);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a categoria da modalidade.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        if (count($rows) !== count($categoryIds)) {
            throw new InvalidArgumentException('A categoria informada não foi encontrada.');
        }

        $categories = [];
        foreach ($rows as $row) {
            $categories[(int) $row['id_categoria']] = [
                'status_categoria' => (string) $row['status_categoria'],
                'interclasses_id_interclasse' => (int) $row['interclasses_id_interclasse'],
            ];
        }
        return $categories;
    }

    /** @return array{categorias_id_categoria:int,interclasses_id_interclasse:int}|null */
    private function modalityScope(int $id, bool $forUpdate): ?array
    {
        $sql = 'SELECT categorias_id_categoria, interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar a modalidade.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar a modalidade.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($row === null) {
            return null;
        }
        return [
            'categorias_id_categoria' => (int) $row['categorias_id_categoria'],
            'interclasses_id_interclasse' => (int) $row['interclasses_id_interclasse'],
        ];
    }

    private function hasRelatedData(int $modalityId): bool
    {
        $queries = [
            'SELECT 1 FROM equipes WHERE modalidades_id_modalidade = ? LIMIT 1 FOR UPDATE',
            'SELECT 1 FROM equipes_has_usuarios eu
             INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe
             WHERE e.modalidades_id_modalidade = ? LIMIT 1 FOR UPDATE',
            'SELECT 1 FROM jogos WHERE modalidades_id_modalidade = ? LIMIT 1 FOR UPDATE',
            'SELECT 1 FROM agenda_reservas WHERE id_modalidade = ? LIMIT 1 FOR UPDATE',
            'SELECT 1 FROM pontuacoes_podio WHERE id_modalidade = ? LIMIT 1 FOR UPDATE',
        ];
        foreach ($queries as $sql) {
            $statement = $this->connection->prepare($sql);
            if ($statement === false) {
                throw new RuntimeException('Não foi possível verificar os vínculos da modalidade.');
            }
            $statement->bind_param('i', $modalityId);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível verificar os vínculos da modalidade.');
            }
            $found = $statement->get_result()->num_rows > 0;
            $statement->close();
            if ($found) {
                return true;
            }
        }
        return false;
    }

    public function deactivate(int $id): bool
    {
        $this->connection->begin_transaction();
        try {
            $modalidade = $this->connection->prepare("UPDATE modalidades SET status_modalidade = '0' WHERE id_modalidade = ?");
            $equipes = $this->connection->prepare("UPDATE equipes SET status_equipe = '0' WHERE modalidades_id_modalidade = ?");
            if ($modalidade === false || $equipes === false) {
                if ($modalidade !== false) {
                    $modalidade->close();
                }
                if ($equipes !== false) {
                    $equipes->close();
                }
                throw new RuntimeException('Não foi possível excluir modalidade.');
            }
            $modalidade->bind_param('i', $id);
            if (!$modalidade->execute()) {
                throw new RuntimeException('Não foi possível excluir modalidade.');
            }
            $found = $modalidade->affected_rows > 0;
            $equipes->bind_param('i', $id);
            if (!$equipes->execute()) {
                throw new RuntimeException('Não foi possível desativar equipes.');
            }
            $modalidade->close();
            $equipes->close();
            $this->connection->commit();
            if (!$found) {
                $check = $this->connection->prepare('SELECT 1 FROM modalidades WHERE id_modalidade = ?');
                if ($check !== false) {
                    $check->bind_param('i', $id);
                    $check->execute();
                    $found = $check->get_result()->num_rows > 0;
                    $check->close();
                }
            }
            return $found;
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }
}
