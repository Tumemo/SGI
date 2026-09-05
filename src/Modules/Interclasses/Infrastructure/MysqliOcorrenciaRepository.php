<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Infrastructure;

use App\Modules\Interclasses\Domain\OcorrenciaRepository;
use mysqli;
use RuntimeException;

final class MysqliOcorrenciaRepository implements OcorrenciaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(array $data): array
    {
        $this->connection->begin_transaction();
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
            $this->connection->commit();
            return ['id' => $id, 'evento' => $event];
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
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
}
