<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

use App\Interclasse\Domain\LocalRepository;
use InvalidArgumentException;

final class LocalService
{
    public function __construct(private readonly LocalRepository $locais)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->locais->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function criar(array $data): int
    {
        $nome = trim((string) ($data['nome_local'] ?? ''));
        $interclasseId = (int) ($data['interclasses_id_interclasse'] ?? 0);
        if ($nome === '' || $interclasseId <= 0) {
            throw new InvalidArgumentException('Nome do local e interclasse são obrigatórios.');
        }

        $carga = null;
        if (array_key_exists('carga_local', $data) && $data['carga_local'] !== null && $data['carga_local'] !== '') {
            $carga = (int) $data['carga_local'];
            if ($carga < 0) {
                throw new InvalidArgumentException('A carga do local não pode ser negativa.');
            }
        }

        return $this->locais->create([
            'nome_local' => $nome,
            'disponivel_local' => (string) ($data['disponivel_local'] ?? '1'),
            'carga_local' => $carga,
            'interclasses_id_interclasse' => $interclasseId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_local'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID do local é obrigatório.');
        }

        $updates = [];
        if (array_key_exists('nome_local', $data)) {
            $nome = trim((string) $data['nome_local']);
            if ($nome === '') {
                throw new InvalidArgumentException('O nome do local não pode ser vazio.');
            }
            $updates['nome_local'] = $nome;
        }
        foreach (['status_local', 'disponivel_local'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = (string) $data[$field];
            }
        }
        if (array_key_exists('carga_local', $data)) {
            $carga = $data['carga_local'];
            if ($carga === null || $carga === '') {
                $updates['carga_local'] = null;
            } else {
                $carga = (int) $carga;
                if ($carga < 0) {
                    throw new InvalidArgumentException('A carga do local não pode ser negativa.');
                }
                $updates['carga_local'] = $carga;
            }
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo enviado para atualização.');
        }

        $interclasseId = null;
        if (isset($data['interclasses_id_interclasse']) && (int) $data['interclasses_id_interclasse'] > 0) {
            $interclasseId = (int) $data['interclasses_id_interclasse'];
        }
        if (!$this->locais->update($id, $updates, $interclasseId)) {
            throw new LocalNaoEncontradoException();
        }
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do local é obrigatório.');
        }
        if (!$this->locais->delete($id)) {
            throw new LocalNaoEncontradoException();
        }
    }
}
