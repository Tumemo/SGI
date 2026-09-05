<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

use App\Interclasse\Domain\TipoModalidadeRepository;
use InvalidArgumentException;

final class TipoModalidadeService
{
    public function __construct(private readonly TipoModalidadeRepository $tipos)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->tipos->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $nome = trim((string) ($data['nome_tipo_modalidade'] ?? ''));
        if ($nome === '') {
            throw new InvalidArgumentException('O campo nome_tipo_modalidade é obrigatório.');
        }

        return $this->tipos->create([
            'nome_tipo_modalidade' => $nome,
            'status_tipo_modalidade' => (string) ($data['status_tipo_modalidade'] ?? '1'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_tipo_modalidade'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID do tipo de modalidade é obrigatório.');
        }

        $updates = [];
        if (array_key_exists('nome_tipo_modalidade', $data)) {
            $nome = trim((string) $data['nome_tipo_modalidade']);
            if ($nome === '') {
                throw new InvalidArgumentException('O nome do tipo de modalidade não pode ser vazio.');
            }
            $updates['nome_tipo_modalidade'] = $nome;
        }
        if (array_key_exists('status_tipo_modalidade', $data)) {
            $updates['status_tipo_modalidade'] = (string) $data['status_tipo_modalidade'];
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum dado enviado para atualização.');
        }
        if (!$this->tipos->update($id, $updates)) {
            throw new TipoModalidadeNaoEncontradoException();
        }
    }
}
