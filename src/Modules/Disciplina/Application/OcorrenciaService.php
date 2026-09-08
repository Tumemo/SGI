<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Application;

use App\Modules\Disciplina\Domain\OcorrenciaRepository;
use InvalidArgumentException;

final class OcorrenciaService
{
    public function __construct(private readonly OcorrenciaRepository $ocorrencias)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id:int,evento:?string}
     */
    public function registrar(array $data): array
    {
        $title = trim((string) ($data['titulo_ocorrencia'] ?? ''));
        $description = (string) ($data['descricao_ocorrencia'] ?? '');
        $date = trim((string) ($data['data_ocorrencia'] ?? ''));
        $userId = (int) ($data['usuarios_id_usuario'] ?? 0);
        if ($title === '' || $description === '' || $date === '' || $userId <= 0) {
            throw new InvalidArgumentException('Dados incompletos.');
        }
        $penalty = (int) ($data['penalidade'] ?? 0);
        if ($penalty < 0) {
            throw new InvalidArgumentException('A penalidade não pode ser negativa.');
        }
        $gameId = (int) ($data['id_jogo'] ?? 0);
        $teamId = (int) ($data['id_turma'] ?? 0);
        $storedDescription = $gameId > 0
            ? '[JOGO:' . $gameId . ']' . ($teamId > 0 ? '[TURMA:' . $teamId . ']' : '') . $description
            : $description;
        return $this->ocorrencias->create([
            'titulo_ocorrencia' => $title,
            'descricao_ocorrencia' => $storedDescription,
            'data_ocorrencia' => $date,
            'usuarios_id_usuario' => $userId,
            'penalidade' => $penalty,
            'id_jogo' => $gameId,
            'id_turma' => $teamId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): bool
    {
        $id = (int) ($data['id_ocorrencia'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da ocorrência é obrigatório.');
        }
        $updates = [];
        foreach (['titulo_ocorrencia', 'descricao_ocorrencia', 'status_ocorrencia'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                if ($field !== 'status_ocorrencia' && $value === '') {
                    throw new InvalidArgumentException('Os dados textuais da ocorrência não podem ser vazios.');
                }
                $updates[$field] = $value;
            }
        }
        if (array_key_exists('penalidade', $data)) {
            $penalty = (int) $data['penalidade'];
            if ($penalty < 0) {
                throw new InvalidArgumentException('A penalidade não pode ser negativa.');
            }
            $updates['penalidade'] = $penalty;
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum dado fornecido para atualização.');
        }
        return $this->ocorrencias->update($id, $updates);
    }

    /** @return array<string, mixed>|null */
    public function encontrar(int $id): ?array
    {
        return $this->ocorrencias->find($id);
    }

    public function editionOfUser(int $userId): ?int
    {
        return $this->ocorrencias->editionOfUser($userId);
    }

    public function roleOfUser(int $userId): ?int
    {
        return $this->ocorrencias->roleOfUser($userId);
    }

    public function editionOfGame(int $gameId): ?int
    {
        return $this->ocorrencias->editionOfGame($gameId);
    }

    public function editionOfTurma(int $turmaId): ?int
    {
        return $this->ocorrencias->editionOfTurma($turmaId);
    }

    public function gameContainsTurma(int $gameId, int $turmaId): bool
    {
        return $this->ocorrencias->gameContainsTurma($gameId, $turmaId);
    }

    public function userBelongsToTurma(int $userId, int $turmaId): bool
    {
        return $this->ocorrencias->userBelongsToTurma($userId, $turmaId);
    }

    public function userParticipatesInGame(int $userId, int $gameId): bool
    {
        return $this->ocorrencias->userParticipatesInGame($userId, $gameId);
    }
}
