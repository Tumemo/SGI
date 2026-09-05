<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

use App\Interclasse\Domain\TurmaRepository;
use InvalidArgumentException;

final class TurmaService
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

        $updates = [];
        if (array_key_exists('interclasses_id_interclasse', $data)) {
            $value = (int) $data['interclasses_id_interclasse'];
            if ($value <= 0) {
                throw new InvalidArgumentException('O interclasse informado é inválido.');
            }
            $updates['interclasses_id_interclasse'] = $value;
        }
        if (array_key_exists('categorias_id_categoria', $data)) {
            $value = (int) $data['categorias_id_categoria'];
            if ($value <= 0) {
                throw new InvalidArgumentException('A categoria informada é inválida.');
            }
            $updates['categorias_id_categoria'] = $value;
        }
        foreach (['nome_turma', 'turno_turma', 'nome_fantasia_turma', 'status_turma'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                if ($field === 'nome_turma' && $value === '') {
                    throw new InvalidArgumentException('O nome da turma não pode ser vazio.');
                }
                $updates[$field] = $value;
            }
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo enviado para atualização.');
        }
        if (!$this->turmas->update($id, $updates)) {
            throw new TurmaNaoEncontradaException();
        }
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
}
