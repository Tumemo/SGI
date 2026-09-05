<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\EdicaoRepository;
use mysqli;
use RuntimeException;

final class MysqliEdicaoRepository implements EdicaoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
        require_once dirname(__DIR__, 3) . '/api/includes/locais_padrao.php';
        require_once dirname(__DIR__, 3) . '/api/includes/equipes_helper.php';
    }

    public function list(array $filters): array
    {
        $details = (bool) ($filters['detalhes'] ?? false);
        $sql = 'SELECT ' . ($details ? '*' : 'id_interclasse, nome_interclasse, ano_interclasse')
            . ' FROM interclasses WHERE 1=1';
        $types = '';
        $params = [];
        if ((int) ($filters['id_interclasse'] ?? 0) > 0) {
            $sql .= ' AND id_interclasse = ?';
            $types .= 'i';
            $params[] = (int) $filters['id_interclasse'];
        }
        if ((int) ($filters['ano'] ?? 0) > 0) {
            $sql .= ' AND YEAR(ano_interclasse) = ?';
            $types .= 'i';
            $params[] = (int) $filters['ano'];
        }
        if ((string) ($filters['busca'] ?? '') !== '') {
            $sql .= ' AND nome_interclasse LIKE ?';
            $types .= 's';
            $params[] = '%' . (string) $filters['busca'] . '%';
        }
        $sql .= ' ORDER BY ano_interclasse DESC';
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar edições.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar edições.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function create(array $data): array
    {
        $this->connection->begin_transaction();
        try {
            $interclasse = $this->connection->prepare(
                "INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse)
                 VALUES (?, ?, '', '1')",
            );
            if ($interclasse === false) {
                throw new RuntimeException('Não foi possível criar edição.');
            }
            $name = (string) $data['nome_interclasse'];
            $year = (string) $data['ano_interclasse'];
            $interclasse->bind_param('ss', $name, $year);
            if (!$interclasse->execute()) {
                $interclasse->close();
                throw new RuntimeException('Não foi possível criar edição.');
            }
            $id = (int) $this->connection->insert_id;
            $interclasse->close();

            $deactivate = $this->connection->prepare(
                "UPDATE interclasses SET status_interclasse = '0' WHERE id_interclasse != ? AND status_interclasse = '1'",
            );
            if ($deactivate === false) {
                throw new RuntimeException('Não foi possível alternar edição ativa.');
            }
            $deactivate->bind_param('i', $id);
            if (!$deactivate->execute()) {
                $deactivate->close();
                throw new RuntimeException('Não foi possível alternar edição ativa.');
            }
            $deactivate->close();

            $categoryI = $this->createCategory($id, 'Categoria I');
            $categoryII = $this->createCategory($id, 'Categoria II');
            [$mataMata, $individual] = $this->ensureModalityTypes();
            $this->createDefaultClasses($id, $categoryI, $categoryII);
            $this->createDefaultModalities($id, $categoryI, $categoryII, $mataMata, $individual);
            if (function_exists('sgi_criar_locais_padrao_interclasse')) {
                sgi_criar_locais_padrao_interclasse($this->connection, $id);
            }
            $teams = function_exists('sgi_gerar_equipes_padrao_interclasse')
                ? sgi_gerar_equipes_padrao_interclasse($this->connection, $id)
                : ['criadas' => 0, 'erros' => ['Helper de equipes não disponível.']];
            $this->connection->commit();
            return [
                'id' => $id,
                'equipes_padrao_garantidas' => (int) $teams['criadas'],
                'erros_equipes' => $teams['erros'],
            ];
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'nome_interclasse' => 's',
            'ano_interclasse' => 's',
            'regulamento_interclasse' => 's',
            'status_interclasse' => 's',
            'valor_item_arrecadacao' => 'i',
            'ponto_1_lugar' => 'i',
            'ponto_2_lugar' => 'i',
            'ponto_3_lugar' => 'i',
        ] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = ?';
                $values[] = $data[$field];
                $types .= $type;
            }
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum campo válido para atualizar edição.');
        }
        $this->connection->begin_transaction();
        try {
            if (($data['status_interclasse'] ?? null) === '1') {
                $deactivate = $this->connection->prepare(
                    "UPDATE interclasses SET status_interclasse = '0' WHERE id_interclasse != ? AND status_interclasse = '1'",
                );
                if ($deactivate === false) {
                    throw new RuntimeException('Não foi possível alternar edição ativa.');
                }
                $deactivate->bind_param('i', $id);
                $deactivate->execute();
                $deactivate->close();
            }
            $values[] = $id;
            $types .= 'i';
            $statement = $this->connection->prepare(
                'UPDATE interclasses SET ' . implode(', ', $fields) . ' WHERE id_interclasse = ?',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível atualizar edição.');
            }
            $statement->bind_param($types, ...$values);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível atualizar edição.');
            }
            $statement->close();
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    private function createCategory(int $interclasseId, string $name): int
    {
        $statement = $this->connection->prepare(
            "INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, '1', ?)",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar categoria padrão.');
        }
        $statement->bind_param('si', $name, $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível criar categoria padrão.');
        }
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    /** @return array{0:int,1:int} */
    private function ensureModalityTypes(): array
    {
        $ids = ['Mata-Mata' => 0, 'Individual' => 0];
        $result = $this->connection->query('SELECT id_tipo_modalidade, nome_tipo_modalidade FROM tipos_modalidades');
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                if (isset($ids[$row['nome_tipo_modalidade']])) {
                    $ids[$row['nome_tipo_modalidade']] = (int) $row['id_tipo_modalidade'];
                }
            }
        }
        foreach ($ids as $name => $typeId) {
            if ($typeId > 0) {
                continue;
            }
            $statement = $this->connection->prepare(
                "INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade) VALUES (?, '1')",
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível criar tipo de modalidade.');
            }
            $statement->bind_param('s', $name);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível criar tipo de modalidade.');
            }
            $ids[$name] = (int) $this->connection->insert_id;
            $statement->close();
        }
        return [$ids['Mata-Mata'], $ids['Individual']];
    }

    private function createDefaultClasses(int $interclasseId, int $categoryI, int $categoryII): void
    {
        $classes = [
            ['6EF', 'Sexto Ano', $categoryI], ['7EF', 'Sétimo Ano', $categoryI], ['8EF', 'Oitavo Ano', $categoryI],
            ['9EF', 'Nono Ano', $categoryII], ['1EMA', '1º Ano Médio', $categoryII],
            ['2EMA', '2º Ano Médio', $categoryII], ['3EMA', '3º Ano Médio', $categoryII],
        ];
        $statement = $this->connection->prepare(
            "INSERT INTO turmas (nome_turma, turno_turma, nome_fantasia_turma, status_turma,
                interclasses_id_interclasse, categorias_id_categoria) VALUES (?, 'manha', ?, '1', ?, ?)",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar turmas padrão.');
        }
        foreach ($classes as [$name, $fantasy, $categoryId]) {
            $statement->bind_param('ssii', $name, $fantasy, $interclasseId, $categoryId);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível criar turmas padrão.');
            }
        }
        $statement->close();
    }

    private function createDefaultModalities(int $interclasseId, int $categoryI, int $categoryII, int $mataMata, int $individual): void
    {
        $definitions = [
            ['Futsal - MA', 'MASC', 10, $mataMata], ['Queimada - MI', 'MISTO', 20, $mataMata],
            ['Volei - MI', 'MISTO', 12, $mataMata], ['Corrida - FE', 'FEM', 2, $individual],
            ['Corrida - MA', 'MASC', 2, $individual],
        ];
        $statement = $this->connection->prepare(
            "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade,
                max_equipes, status_modalidade, tipos_modalidades_id_tipo_modalidade,
                categorias_id_categoria, interclasses_id_interclasse)
             VALUES (?, ?, ?, NULL, '1', ?, ?, ?)",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível criar modalidades padrão.');
        }
        foreach ([$categoryI, $categoryII] as $categoryId) {
            foreach ($definitions as [$name, $gender, $limit, $typeId]) {
                $statement->bind_param('ssiiii', $name, $gender, $limit, $typeId, $categoryId, $interclasseId);
                if (!$statement->execute()) {
                    $statement->close();
                    throw new RuntimeException('Não foi possível criar modalidades padrão.');
                }
            }
        }
        $statement->close();
    }
}
