<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Infrastructure;

use App\Modules\Disciplina\Domain\OcorrenciaRepository;
use mysqli;
use RuntimeException;

final class MysqliOcorrenciaRepository implements OcorrenciaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(array $data): array
    {
        \App\Shared\Database\Transaction::begin($this->connection);
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia,
                    usuarios_id_usuario, penalidade) VALUES (?, ?, ?, ?, ?)',
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível registrar ocorrência.');
            }
            $title = (string) $data['titulo_ocorrencia'];
            $description = (string) $data['descricao_ocorrencia'];
            $date = (string) $data['data_ocorrencia'];
            $userId = (int) $data['usuarios_id_usuario'];
            $penalty = (int) $data['penalidade'];
            $statement->bind_param('sssii', $title, $description, $date, $userId, $penalty);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível registrar ocorrência.');
            }
            $id = (int) $this->connection->insert_id;
            $statement->close();
            $event = null;
            $gameId = (int) ($data['id_jogo'] ?? 0);
            if ($title === 'Amarelo' && $gameId > 0) {
                $like = '%[JOGO:' . $gameId . ']%';
                $count = $this->connection->prepare(
                    "SELECT COUNT(*) AS total FROM ocorrencias
                     WHERE titulo_ocorrencia = 'Amarelo' AND usuarios_id_usuario = ?
                       AND descricao_ocorrencia LIKE ? AND status_ocorrencia = '1'",
                );
                if ($count === false) {
                    throw new RuntimeException('Não foi possível verificar cartões.');
                }
                $count->bind_param('is', $userId, $like);
                $count->execute();
                $total = (int) (($count->get_result()->fetch_assoc()['total'] ?? 0));
                $count->close();
                if ($total >= 2) {
                    $event = 'segundo_amarelo';
                    $redDescription = '[JOGO:' . $gameId . ']' . ((int) ($data['id_turma'] ?? 0) > 0
                        ? '[TURMA:' . (int) $data['id_turma'] . ']'
                        : '') . 'Segundo cartão amarelo — expulso automático';
                    $red = $this->connection->prepare(
                        "INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia,
                            usuarios_id_usuario, penalidade) VALUES ('Vermelho', ?, ?, ?, 1)",
                    );
                    if ($red === false) {
                        throw new RuntimeException('Não foi possível registrar expulsão automática.');
                    }
                    $red->bind_param('ssi', $redDescription, $date, $userId);
                    if (!$red->execute()) {
                        $red->close();
                        throw new RuntimeException('Não foi possível registrar expulsão automática.');
                    }
                    $red->close();
                }
            }
            \App\Shared\Database\Transaction::commit($this->connection);
            return ['id' => $id, 'evento' => $event];
        } catch (\Throwable $exception) {
            \App\Shared\Database\Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function find(int $id): ?array
    {
        $statement = $this->prepare('SELECT id_ocorrencia, usuarios_id_usuario, descricao_ocorrencia, status_ocorrencia FROM ocorrencias WHERE id_ocorrencia = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    public function editionOfUser(int $userId): ?int
    {
        return $this->scalar('SELECT interclasses_id_interclasse FROM usuarios WHERE id_usuario = ? LIMIT 1', $userId);
    }

    public function roleOfUser(int $userId): ?int
    {
        $value = $this->scalar('SELECT nivel_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1', $userId);
        return $value;
    }

    public function editionOfGame(int $gameId): ?int
    {
        return $this->scalar(
            'SELECT m.interclasses_id_interclasse
             FROM jogos j
             INNER JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade
             WHERE j.id_jogo = ?
             LIMIT 1',
            $gameId,
        );
    }

    public function editionOfTurma(int $turmaId): ?int
    {
        return $this->scalar('SELECT interclasses_id_interclasse FROM turmas WHERE id_turma = ? LIMIT 1', $turmaId);
    }

    public function gameContainsTurma(int $gameId, int $turmaId): bool
    {
        return $this->exists(
            'SELECT 1
             FROM partidas p
             INNER JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
             INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
             WHERE p.jogos_id_jogo = ? AND t.id_turma = ?
             LIMIT 1',
            $gameId,
            $turmaId,
        );
    }

    public function userBelongsToTurma(int $userId, int $turmaId): bool
    {
        return $this->exists('SELECT 1 FROM usuarios WHERE id_usuario = ? AND turmas_id_turma = ? LIMIT 1', $userId, $turmaId);
    }

    public function userParticipatesInGame(int $userId, int $gameId): bool
    {
        return $this->exists(
            'SELECT 1
             FROM equipes_has_usuarios eu
             INNER JOIN partidas p ON p.equipes_id_equipe = eu.equipes_id_equipe
             WHERE eu.usuarios_id_usuario = ? AND p.jogos_id_jogo = ?
             LIMIT 1',
            $userId,
            $gameId,
        );
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $types = '';
        foreach ([
            'titulo_ocorrencia' => 's',
            'descricao_ocorrencia' => 's',
            'status_ocorrencia' => 's',
            'penalidade' => 'i',
        ] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = ?';
                $values[] = $data[$field];
                $types .= $type;
            }
        }
        if ($fields === []) {
            throw new RuntimeException('Nenhum dado fornecido para atualização.');
        }
        $values[] = $id;
        $types .= 'i';
        $statement = $this->connection->prepare(
            'UPDATE ocorrencias SET ' . implode(', ', $fields) . ' WHERE id_ocorrencia = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível atualizar ocorrência.');
        }
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar ocorrência.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar ocorrência.');
        }
        return $statement;
    }

    private function scalar(string $sql, int $id): ?int
    {
        $statement = $this->prepare($sql);
        $statement->bind_param('i', $id);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return $value === null || $value === false ? null : (int) $value;
    }

    private function exists(string $sql, int $firstId, int $secondId): bool
    {
        $statement = $this->prepare($sql);
        $statement->bind_param('ii', $firstId, $secondId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
}
