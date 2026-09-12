<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Application;

use App\Modules\Participantes\Domain\TurmaRepository;
use App\Modules\Participantes\Domain\TurmaRankingUpdater;
use InvalidArgumentException;

final class TurmaService implements TurmaRankingUpdater
{
    public function __construct(private readonly TurmaRepository $turmas)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->turmas->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $name = trim((string) ($data['nome_turma'] ?? ''));
        $interclasseId = (int) ($data['interclasses_id_interclasse'] ?? 0);
        $categoryId = (int) ($data['categorias_id_categoria'] ?? 0);
        if ($name === '' || $interclasseId <= 0 || $categoryId <= 0) {
            throw new InvalidArgumentException('Dados obrigatórios ausentes.');
        }

        $shift = null;
        if (array_key_exists('turno_turma', $data) && trim((string) $data['turno_turma']) !== '') {
            $shift = trim((string) $data['turno_turma']);
        }
        if ($this->turmas->duplicateExists($interclasseId, $name, $shift)) {
            throw new TurmaDuplicadaException();
        }

        return $this->turmas->create([
            'interclasses_id_interclasse' => $interclasseId,
            'categorias_id_categoria' => $categoryId,
            'nome_turma' => $name,
            'turno_turma' => $shift,
            'nome_fantasia_turma' => array_key_exists('nome_fantasia_turma', $data)
                ? trim((string) $data['nome_fantasia_turma'])
                : null,
            'status_turma' => (string) ($data['status_turma'] ?? '1'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_turma'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da turma é obrigatório.');
        }

        $updates = $this->normalizeUpdates($data, false);
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo enviado para atualização.');
        }
        if (!$this->turmas->update($id, $updates)) {
            throw new TurmaNaoEncontradaException();
        }
    }

    /** @param array<string, mixed> $data */
    public function atualizarPeloRanking(array $data): bool
    {
        $id = (int) ($data['id_turma'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Dados inválidos ou ID da turma ausente.');
        }
        $updates = $this->normalizeUpdates($data, true);
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo enviado para atualização.');
        }
        return $this->turmas->update($id, $updates);
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID da turma é obrigatório.');
        }
        if (!$this->turmas->delete($id)) {
            throw new TurmaNaoEncontradaException();
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, int|string>
     */
    private function normalizeUpdates(array $data, bool $allowScore): array
    {
        $updates = [];
        foreach ([
            'interclasses_id_interclasse' => 'int',
            'categorias_id_categoria' => 'int',
            'nome_turma' => 'string',
            'turno_turma' => 'string',
            'nome_fantasia_turma' => 'string',
            'status_turma' => 'string',
            ...($allowScore ? ['pontuacao_turma' => 'int'] : []),
        ] as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $type === 'int' ? (int) $data[$field] : trim((string) $data[$field]);
            if ($field === 'interclasses_id_interclasse' && $value <= 0) {
                throw new InvalidArgumentException('O interclasse informado é inválido.');
            }
            if ($field === 'categorias_id_categoria' && $value <= 0) {
                throw new InvalidArgumentException('A categoria informada é inválida.');
            }
            if ($field === 'nome_turma' && $value === '') {
                throw new InvalidArgumentException('O nome da turma não pode ser vazio.');
            }
            if ($field === 'pontuacao_turma' && $value < 0) {
                throw new InvalidArgumentException('A pontuação não pode ser negativa.');
            }
            $updates[$field] = $value;
        }
        return $updates;
    }
}
