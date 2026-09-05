<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

use App\Interclasse\Domain\EquipeRepository;
use InvalidArgumentException;

final class EquipeService
{
    public function __construct(private readonly EquipeRepository $equipes)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id_equipe:int,nome_equipe:?string}
     */
    public function criar(array $data): array
    {
        $modalidadeId = (int) ($data['modalidades_id_modalidade'] ?? 0);
        $turmaId = (int) ($data['turmas_id_turma'] ?? 0);
        if ($modalidadeId <= 0 || $turmaId <= 0) {
            throw new InvalidArgumentException('Dados incompletos: modalidade e turma são obrigatórios.');
        }

        $nome = null;
        if (array_key_exists('nome_equipe', $data) && trim((string) $data['nome_equipe']) !== '') {
            $nome = trim((string) $data['nome_equipe']);
        }

        return $this->equipes->create([
            'modalidades_id_modalidade' => $modalidadeId,
            'turmas_id_turma' => $turmaId,
            'status_equipe' => (string) ($data['status_equipe'] ?? '1'),
            'nome_equipe' => $nome,
        ]);
    }

    /**
     * @param array<int, mixed> $userIds
     */
    public function adicionarUsuarios(int $teamId, array $userIds): void
    {
        if ($teamId <= 0) {
            throw new InvalidArgumentException('ID da equipe é obrigatório.');
        }

        $normalized = [];
        foreach ($userIds as $userId) {
            $id = (int) $userId;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        $this->equipes->addUsers($teamId, array_values($normalized));
    }

    public function removerUsuario(int $teamId, int $userId): void
    {
        if ($teamId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('ID da equipe e ID do usuário são obrigatórios.');
        }

        $this->equipes->removeUser($teamId, $userId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_equipe'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da equipe é obrigatório.');
        }

        $updates = [];
        if (array_key_exists('nome_equipe', $data)) {
            $name = trim((string) $data['nome_equipe']);
            if ($name === '') {
                throw new InvalidArgumentException('O nome da equipe não pode ser vazio.');
            }
            $updates['nome_equipe'] = $name;
        }
        foreach (['modalidades_id_modalidade', 'turmas_id_turma'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = (int) $data[$field];
                if ($value <= 0) {
                    throw new InvalidArgumentException('Os vínculos da equipe devem ser válidos.');
                }
                $updates[$field] = $value;
            }
        }
        if (array_key_exists('status_equipe', $data)) {
            $updates['status_equipe'] = (string) $data['status_equipe'];
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo válido enviado para atualização.');
        }

        if (!$this->equipes->update($id, $updates)) {
            throw new EquipeNaoEncontradaException();
        }
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID da equipe é obrigatório.');
        }
        if (!$this->equipes->deactivate($id)) {
            throw new EquipeNaoEncontradaException();
        }
    }
}
