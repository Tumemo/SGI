<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Application\JogoConflitoException;
use App\Modules\Competicoes\Domain\CronometroRules;
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
        // A consulta operacional de lista exclui jogos concluídos, mas o
        // placar ainda precisa abrir um jogo específico encerrado para
        // consulta e confirmação da sincronização. Preserve o filtro somente
        // quando a chamada não está apontando para um jogo individual.
        $operational = !empty($filters['operacional']) && (int) ($filters['id_jogo'] ?? 0) <= 0
            ? " AND jogos.data_jogo IS NOT NULL AND jogos.inicio_jogo IS NOT NULL
                AND jogos.termino_jogo IS NOT NULL AND jogos.locais_id_local IS NOT NULL
                AND jogos.status_jogo IN ('Agendado', 'Iniciado', 'Pausado')"
            : '';
        $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo,
                       jogos.inicio_jogo, jogos.termino_jogo, jogos.status_jogo,
                       jogos.tempo_restante_jogo, jogos.duracao_jogo,
                       jogos.tempo_extra_jogo, jogos.data_inicio_real,
                       UNIX_TIMESTAMP(jogos.data_inicio_real) AS data_inicio_epoch,
                       jogos.modalidades_id_modalidade, jogos.locais_id_local,
                       modalidades.nome_modalidade,
                       modalidades.interclasses_id_interclasse AS id_interclasse,
                       modalidades.tipos_modalidades_id_tipo_modalidade,
                       tipos_modalidades.nome_tipo_modalidade,
                       locais.nome_local, categorias.nome_categoria,
                       GROUP_CONCAT(DISTINCT COALESCE(e.nome_equipe, t.nome_turma)
                           ORDER BY p.id_partida SEPARATOR ' vs ') AS equipes_nomes,
                       MAX(art_top.nome_usuario) AS artilheiro_nome
                FROM jogos
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade
                LEFT JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade
                LEFT JOIN locais ON locais.id_local = jogos.locais_id_local
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
                    WHERE a.status_artilheiro = 'ativo' AND a.conta_no_placar = 1
                    GROUP BY a.jogos_id_jogo, a.usuarios_id_usuario, u.nome_usuario
                ) art_top ON art_top.jogos_id_jogo = jogos.id_jogo AND art_top.rn = 1
                WHERE 1=1" . $filter['sql'] . $operational . ' GROUP BY jogos.id_jogo ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC';
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
            if ($row['duracao_jogo'] === null || (int) $row['duracao_jogo'] <= 0) {
                // Jogos sem duração podem não ter saldo calculável; nesse caso só há
                // saldo calculável quando um snapshot explícito foi salvo.
                $row['tempo_restante_calculado'] = $row['tempo_restante_jogo'] === null
                    ? null
                    : max(0, (int) $row['tempo_restante_jogo']);
            } else {
                $row['tempo_restante_calculado'] = CronometroRules::saldoAtual([
                    'status_jogo' => (string) $row['status_jogo'],
                    'duracao_jogo' => $row['duracao_jogo'],
                    'tempo_extra_jogo' => $row['tempo_extra_jogo'] ?? 0,
                    'tempo_restante_jogo' => $row['tempo_restante_jogo'],
                    'data_inicio_real' => $row['data_inicio_epoch'] === null ? null : (int) $row['data_inicio_epoch'],
                ], $now);
            }
            $row['servidor_epoch_ms'] = $now * 1000;
            $row['tipo_competicao'] = \App\Modules\Competicoes\Domain\TipoCompeticaoRules::resolve($row);
            unset($row['data_inicio_epoch']);
        }
        unset($row);
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->prepare(
            'SELECT j.*, m.tipos_modalidades_id_tipo_modalidade, tm.nome_tipo_modalidade
             FROM jogos j
             INNER JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade
             LEFT JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
             WHERE j.id_jogo = ? LIMIT 1',
        );
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
        $requestedStatus = $data['status_jogo'] ?? null;
        if ($requestedStatus !== null && in_array($requestedStatus, ['Agendado', 'Iniciado', 'Pausado', 'Concluido', 'Finalizado'], true)) {
            $tipoCompeticao = \App\Modules\Competicoes\Domain\TipoCompeticaoRules::resolve($current);
            if ($tipoCompeticao === null) {
                throw new \InvalidArgumentException('O tipo da modalidade não está configurado.');
            }
            if ($tipoCompeticao === \App\Modules\Competicoes\Domain\TipoCompeticaoRules::INDIVIDUAL
                && in_array($requestedStatus, ['Concluido', 'Finalizado'], true)) {
                throw new \InvalidArgumentException('Modalidades individuais devem ser concluídas pelo lançamento do pódio.');
            }
        }
        $fields = [];
        $values = [];
        $types = '';
        $nullableSchedule = ['data_jogo', 'inicio_jogo', 'termino_jogo', 'locais_id_local'];
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
            if ($data[$field] === null || ($type === 's' && in_array($field, ['data_jogo', 'inicio_jogo', 'termino_jogo'], true) && trim((string) $data[$field]) === '')) {
                if (in_array($field, $nullableSchedule, true)) {
                    $fields[] = $field . ' = NULL';
                    continue;
                }
                throw new \InvalidArgumentException('O campo ' . $field . ' não pode ser nulo.');
            }
            if ($type === 'i' && $field === 'locais_id_local' && (int) $data[$field] <= 0) {
                $fields[] = $field . ' = NULL';
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

        $candidate = [
            'data_jogo' => array_key_exists('data_jogo', $data) ? $this->nullableString($data['data_jogo']) : $current['data_jogo'],
            'inicio_jogo' => array_key_exists('inicio_jogo', $data) ? $this->nullableString($data['inicio_jogo']) : $current['inicio_jogo'],
            'termino_jogo' => array_key_exists('termino_jogo', $data) ? $this->nullableString($data['termino_jogo']) : $current['termino_jogo'],
            'locais_id_local' => array_key_exists('locais_id_local', $data) ? ((int) $data['locais_id_local'] > 0 ? (int) $data['locais_id_local'] : null) : ($current['locais_id_local'] === null ? null : (int) $current['locais_id_local']),
        ];
        if ($candidate['data_jogo'] !== null && $candidate['inicio_jogo'] !== null && $candidate['termino_jogo'] !== null && $candidate['locais_id_local'] !== null) {
            $conflict = $this->scheduleConflict($candidate['data_jogo'], $candidate['locais_id_local'], $candidate['inicio_jogo'], $candidate['termino_jogo'], $id);
            if ($conflict !== null) {
                throw new JogoConflitoException($conflict);
            }
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

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function scheduleConflict(string $date, int $localId, string $start, string $end, int $currentId): ?string
    {
        $statement = $this->prepare("SELECT nome_jogo FROM jogos
            WHERE data_jogo = ? AND locais_id_local = ?
              AND status_jogo IN ('Agendado', 'Iniciado', 'Pausado')
              AND id_jogo <> ?
              AND ? < ADDTIME(termino_jogo, '00:10:00')
              AND ADDTIME(?, '00:10:00') > inicio_jogo
            LIMIT 1");
        $statement->bind_param('siiss', $date, $localId, $currentId, $start, $end);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row === null ? null : 'Já existe um jogo agendado neste mesmo local com conflito de horário (' . $row['nome_jogo'] . ').';
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
