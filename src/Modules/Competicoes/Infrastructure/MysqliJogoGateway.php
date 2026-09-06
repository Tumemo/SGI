<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Shared\Database\SqlFilters;
use mysqli;
use RuntimeException;

final class MysqliJogoGateway
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function list(array $filters): array
    {
        if ((int) ($filters['id_jogo'] ?? 0) < 0) {
            return [];
        }
        $filter = SqlFilters::aplicarFiltrosJogos($filters);
        $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo,
                       jogos.inicio_jogo, jogos.termino_jogo, jogos.status_jogo,
                       jogos.tempo_restante_jogo, jogos.duracao_jogo,
                       jogos.tempo_extra_jogo, jogos.data_inicio_real,
                       jogos.modalidades_id_modalidade, jogos.locais_id_local,
                       modalidades.nome_modalidade,
                       modalidades.interclasses_id_interclasse AS id_interclasse,
                       modalidades.tipos_modalidades_id_tipo_modalidade,
                       locais.nome_local, categorias.nome_categoria,
                       GROUP_CONCAT(DISTINCT COALESCE(e.nome_equipe, t.nome_turma)
                           ORDER BY p.id_partida SEPARATOR ' vs ') AS equipes_nomes,
                       art_top.nome_usuario AS artilheiro_nome
                FROM jogos
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
                LEFT JOIN partidas p ON p.jogos_id_jogo = jogos.id_jogo
                LEFT JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
                LEFT JOIN turmas t ON t.id_turma = e.turmas_id_turma
                LEFT JOIN (
                    SELECT a.jogos_id_jogo, u.nome_usuario,
                           ROW_NUMBER() OVER (PARTITION BY a.jogos_id_jogo
                               ORDER BY COUNT(*) DESC, u.nome_usuario ASC) AS rn
                    FROM artilheiros a
                    INNER JOIN usuarios u ON u.id_usuario = a.usuarios_id_usuario
                    GROUP BY a.jogos_id_jogo, a.usuarios_id_usuario, u.nome_usuario
                ) art_top ON art_top.jogos_id_jogo = jogos.id_jogo AND art_top.rn = 1
                WHERE 1=1" . $filter['sql'] . ' GROUP BY jogos.id_jogo ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC';
        $statement = $this->prepare($sql);
        if ($filter['params'] !== []) {
            $statement->bind_param($filter['types'], ...$filter['params']);
        }
        if (!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            throw new RuntimeException($message !== '' ? $message : 'Não foi possível consultar jogos.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        $now = time();
        foreach ($rows as &$row) {
            if ($row['status_jogo'] === 'Iniciado' && $row['data_inicio_real'] && $row['duracao_jogo']) {
                $elapsed = max(0, $now - (strtotime((string) $row['data_inicio_real']) ?: $now));
                $total = (int) $row['duracao_jogo'] + (int) ($row['tempo_extra_jogo'] ?? 0);
                $row['tempo_restante_calculado'] = max(0, $total - $elapsed);
            } else {
                $row['tempo_restante_calculado'] = $row['tempo_restante_jogo'];
            }
        }
        unset($row);
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->prepare('SELECT * FROM jogos WHERE id_jogo = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    public function editionOfGame(int $id): ?int
    {
        $row = $this->one('SELECT m.interclasses_id_interclasse AS edition_id FROM jogos j INNER JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade WHERE j.id_jogo = ? LIMIT 1', $id);
        return $row === null ? null : (int) $row['edition_id'];
    }

    public function editionOfModality(int $id): ?int
    {
        $row = $this->one('SELECT interclasses_id_interclasse AS edition_id FROM modalidades WHERE id_modalidade = ? LIMIT 1', $id);
        return $row === null ? null : (int) $row['edition_id'];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $current = $this->find($id);
        if ($current === null) {
            return false;
        }
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'nome_jogo' => 's', 'data_jogo' => 's', 'inicio_jogo' => 's',
            'termino_jogo' => 's', 'tempo_restante_jogo' => 'i',
            'duracao_jogo' => 'i', 'tempo_extra_jogo' => 'i',
            'status_jogo' => 's', 'modalidades_id_modalidade' => 'i',
            'locais_id_local' => 'i',
        ] as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $type === 'i' ? (int) $data[$field] : (string) $data[$field];
            $fields[] = $field . ' = ?';
            $values[] = $value;
            $types .= $type;
        }
        if (($data['status_jogo'] ?? null) === 'Iniciado' && !array_key_exists('data_inicio_real', $data)) {
            $fields[] = 'data_inicio_real = NOW()';
        }
        if (in_array($data['status_jogo'] ?? null, ['Pausado', 'Concluido'], true)) {
            $fields[] = 'data_inicio_real = NULL';
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum dado enviado para atualização.');
        }
        $values[] = $id;
        $types .= 'i';
        $statement = $this->prepare('UPDATE jogos SET ' . implode(', ', $fields) . ' WHERE id_jogo = ?');
        $statement->bind_param($types, ...$values);
        $success = $statement->execute();
        $message = $statement->error;
        $statement->close();
        if (!$success) {
            throw new RuntimeException($message !== '' ? $message : 'Não foi possível atualizar jogo.');
        }
        return true;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar operação de jogo.');
        }
        return $statement;
    }

    /** @return array<string, mixed>|null */
    private function one(string $sql, int $id): ?array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }
}
