<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

use App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository;
use App\Modules\Participantes\Domain\InscricaoRepository;
use mysqli;
use RuntimeException;

final class MysqliInscricaoRepository implements InscricaoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function subscribe(int $userId, int $editionId, array $teamIds): array
    {
        $user = $this->one('SELECT turmas_id_turma FROM usuarios WHERE id_usuario = ? LIMIT 1', 'i', [$userId]);
        if ($user === null) {
            throw new RuntimeException('Usuário não encontrado.');
        }
        $classId = (int) ($user['turmas_id_turma'] ?? 0);
        if ($classId <= 0) {
            throw new RuntimeException('Usuário não possui turma vinculada.');
        }

        $already = [];
        $statement = $this->prepare(
            "SELECT m.id_modalidade
             FROM equipes_has_usuarios eu
             INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe
             INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade
             WHERE eu.usuarios_id_usuario = ? AND e.status_equipe = '1'
               AND m.interclasses_id_interclasse = ?",
        );
        $statement->bind_param('ii', $userId, $editionId);
        $statement->execute();
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $already[(int) $row['id_modalidade']] = true;
        }
        $statement->close();

        $validTeams = [];
        $errors = [];
        foreach ($teamIds as $teamId) {
            $row = $this->one(
                'SELECT e.id_equipe, e.turmas_id_turma, e.modalidades_id_modalidade,
                        m.interclasses_id_interclasse
                 FROM equipes e
                 INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade
                 WHERE e.id_equipe = ? AND e.status_equipe = \'1\' LIMIT 1',
                'i',
                [$teamId],
            );
            if ($row === null) {
                $errors[] = "Equipe {$teamId} não encontrada.";
                continue;
            }
            if ((int) $row['turmas_id_turma'] !== $classId) {
                $errors[] = 'Você só pode se inscrever em equipes da sua turma.';
                continue;
            }
            if ((int) $row['interclasses_id_interclasse'] !== $editionId) {
                $errors[] = "Equipe {$teamId} não pertence a este interclasse.";
                continue;
            }
            $validTeams[] = [
                'id_equipe' => (int) $row['id_equipe'],
                'id_modalidade' => (int) $row['modalidades_id_modalidade'],
            ];
        }
        if ($validTeams === []) {
            throw new RuntimeException('Nenhuma equipe válida informada.' . ($errors !== [] ? ' ' . implode(' ', array_unique($errors)) : ''));
        }

        $modalities = array_values(array_unique(array_column($validTeams, 'id_modalidade')));
        $newModalities = array_values(array_filter($modalities, static fn (int $id): bool => !isset($already[$id])));
        if (count($already) + count($newModalities) > 3) {
            throw new RuntimeException('Máximo de 3 modalidades permitidas por aluno.');
        }

        $this->connection->begin_transaction();
        try {
            $insertions = 0;
            $existing = 0;
            foreach ($newModalities as $modalityId) {
                $modality = $this->one('SELECT max_inscrito_modalidade, max_equipes FROM modalidades WHERE id_modalidade = ? LIMIT 1', 'i', [$modalityId]);
                $maxStudents = (int) ($modality['max_inscrito_modalidade'] ?? 0);
                $maxTeams = isset($modality['max_equipes']) ? (int) $modality['max_equipes'] : 0;
                $capacity = $maxStudents > 0 && $maxTeams > 0 ? $maxStudents * $maxTeams : 0;
                if ($capacity > 0) {
                    $occupied = $this->one(
                        "SELECT COUNT(DISTINCT eu.usuarios_id_usuario) AS total
                         FROM equipes_has_usuarios eu
                         INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe
                         INNER JOIN usuarios u ON u.id_usuario = eu.usuarios_id_usuario
                         WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ?
                           AND e.status_equipe = '1' AND u.status_usuario = '1'
                         FOR UPDATE",
                        'ii',
                        [$modalityId, $classId],
                    );
                    if ((int) ($occupied['total'] ?? 0) >= $capacity) {
                        $errors[] = "A modalidade {$modalityId} está lotada.";
                        continue;
                    }
                }

                $teamId = MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($this->connection, $modalityId, $classId);
                if ($teamId === null) {
                    $errors[] = "Erro ao localizar ou criar a equipe padrão para a modalidade {$modalityId}.";
                    continue;
                }
                $check = $this->one('SELECT 1 FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ? LIMIT 1', 'ii', [$teamId, $userId]);
                if ($check !== null) {
                    $existing++;
                    continue;
                }
                $statement = $this->prepare('INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
                $statement->bind_param('ii', $teamId, $userId);
                if (!$statement->execute()) {
                    $statement->close();
                    throw new RuntimeException('Não foi possível concluir a inscrição.');
                }
                $statement->close();
                $insertions++;
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }

        $success = $insertions > 0 || $existing > 0;
        $message = $insertions > 0 ? 'Inscrição realizada com sucesso!' : 'Nenhuma inscrição nova foi necessária.';
        if ($existing > 0) {
            $message .= " Você já estava inscrito em {$existing} equipe(s).";
        }
        if ($errors !== []) {
            $message .= ' ' . implode(' ', array_unique($errors));
        }

        return [
            'success' => $success,
            'message' => $message,
            'insercoes' => $insertions,
            'ja_existentes' => $existing,
            'erros' => array_values(array_unique($errors)),
        ];
    }

    /** @param list<int> $params @return array<string, mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            throw new RuntimeException($message !== '' ? $message : 'Não foi possível consultar inscrição.');
        }
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar operação de inscrição.');
        }
        return $statement;
    }
}
