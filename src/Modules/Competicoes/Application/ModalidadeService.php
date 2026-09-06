<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\ModalidadeRepository;
use InvalidArgumentException;

final class ModalidadeService
{
    public function __construct(private readonly ModalidadeRepository $modalidades)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->modalidades->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $nome = trim((string) ($data['nome_modalidade'] ?? ''));
        $tipoId = (int) ($data['tipos_modalidades_id_tipo_modalidade'] ?? 0);
        $categoriaId = (int) ($data['categorias_id_categoria'] ?? 0);
        $interclasseId = (int) ($data['interclasses_id_interclasse'] ?? 0);
        if ($nome === '' || $tipoId <= 0 || $categoriaId <= 0 || $interclasseId <= 0) {
            throw new InvalidArgumentException('Dados incompletos.');
        }

        $maxInscritos = (int) ($data['max_inscrito_modalidade'] ?? 0);
        $maxEquipes = $this->optionalPositiveInt($data['max_equipes'] ?? null);
        return $this->modalidades->create([
            'nome_modalidade' => $nome,
            'genero_modalidade' => $this->normalizarGenero((string) ($data['genero_modalidade'] ?? '')),
            'max_inscrito_modalidade' => $maxInscritos,
            'max_equipes' => $maxEquipes,
            'tipos_modalidades_id_tipo_modalidade' => $tipoId,
            'status_modalidade' => (string) ($data['status_modalidade'] ?? '1'),
            'categorias_id_categoria' => $categoriaId,
            'interclasses_id_interclasse' => $interclasseId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_modalidade'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da modalidade é obrigatório.');
        }
        $updates = [];
        foreach (['nome_modalidade', 'status_modalidade', 'tipos_modalidades_id_tipo_modalidade', 'categorias_id_categoria', 'interclasses_id_interclasse'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'nome_modalidade') {
                    $value = trim((string) $value);
                    if ($value === '') {
                        throw new InvalidArgumentException('O nome da modalidade não pode ser vazio.');
                    }
                } elseif (in_array($field, ['tipos_modalidades_id_tipo_modalidade', 'categorias_id_categoria', 'interclasses_id_interclasse'], true)) {
                    $value = (int) $value;
                    if ($value <= 0) {
                        throw new InvalidArgumentException('Os vínculos da modalidade devem ser válidos.');
                    }
                } else {
                    $value = (string) $value;
                }
                $updates[$field] = $value;
            }
        }
        if (array_key_exists('genero_modalidade', $data)) {
            $updates['genero_modalidade'] = $this->normalizarGenero((string) $data['genero_modalidade']);
        }
        if (array_key_exists('max_inscrito_modalidade', $data)) {
            $updates['max_inscrito_modalidade'] = (int) $data['max_inscrito_modalidade'];
        }
        if (array_key_exists('max_equipes', $data)) {
            $updates['max_equipes'] = $this->optionalPositiveInt($data['max_equipes']);
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum dado fornecido para atualização.');
        }
        if (!$this->modalidades->update($id, $updates)) {
            throw new ModalidadeNaoEncontradaException();
        }
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da modalidade é obrigatório.');
        }
        if (!$this->modalidades->deactivate($id)) {
            throw new ModalidadeNaoEncontradaException();
        }
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int) $value === 0) {
            return null;
        }
        $number = (int) $value;
        if ($number < 0) {
            throw new InvalidArgumentException('Os limites da modalidade não podem ser negativos.');
        }
        return $number;
    }

    private function normalizarGenero(string $genero): string
    {
        return match (strtoupper(trim($genero))) {
            'M', 'MAS', 'MASC', 'MASCULINO' => 'MASC',
            'F', 'FEM', 'FEMININO' => 'FEM',
            'MISTO', 'MIXTO', 'MIX' => 'MISTO',
            default => throw new InvalidArgumentException('Gênero da modalidade inválido.'),
        };
    }
}
