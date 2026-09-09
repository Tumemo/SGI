<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Application;

use App\Modules\Eventos\Domain\CategoriaRepository;
use InvalidArgumentException;

final class CategoriaService
{
    public function __construct(private readonly CategoriaRepository $categorias)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->categorias->listActive($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $nome = trim((string) ($data['nome_categoria'] ?? ''));
        $interclasseId = (int) ($data['interclasses_id_interclasse'] ?? 0);

        if ($nome === '' || $interclasseId <= 0) {
            throw new InvalidArgumentException('nome_categoria e interclasses_id_interclasse são obrigatórios.');
        }
        if ($this->categorias->duplicateExists($interclasseId, $nome)) {
            throw new CategoriaDuplicadaException();
        }

        return $this->categorias->create([
            'nome_categoria' => $nome,
            'status_categoria' => (string) ($data['status_categoria'] ?? '1'),
            'interclasses_id_interclasse' => $interclasseId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_categoria'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da categoria é obrigatório.');
        }

        $categoria = $this->categorias->find($id);
        if ($categoria === null) {
            throw new CategoriaNaoEncontradaException();
        }
        if ($categoria['status_categoria'] === '0') {
            throw new CategoriaInativaException();
        }

        $updates = [];
        if (array_key_exists('nome_categoria', $data)) {
            $nome = trim((string) $data['nome_categoria']);
            if ($nome === '') {
                throw new InvalidArgumentException('O nome da categoria não pode ser vazio.');
            }
            if ($this->categorias->duplicateExists((int) $categoria['interclasses_id_interclasse'], $nome, $id)) {
                throw new CategoriaDuplicadaException();
            }
            $updates['nome_categoria'] = $nome;
        }
        if (array_key_exists('status_categoria', $data)) {
            $updates['status_categoria'] = (string) $data['status_categoria'];
        }

        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum dado enviado para atualizar.');
        }

        $this->categorias->update($id, $updates);
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da categoria é obrigatório.');
        }
        if ($this->categorias->find($id) === null) {
            throw new CategoriaNaoEncontradaException();
        }

        $this->categorias->deactivateCascade($id);
    }
}
