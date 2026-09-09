<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\ModalidadeRepository;
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
        $categoryId = (int) $data['categorias_id_categoria'];
        $interclasseId = (int) $data['interclasses_id_interclasse'];
        $statement->bind_param('ssiisiii', $name, $gender, $maxInscritos, $maxEquipes, $typeId, $status, $categoryId, $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar modalidade.');
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
