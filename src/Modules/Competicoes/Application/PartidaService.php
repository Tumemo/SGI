<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\PartidaRepository;
use InvalidArgumentException;

final class PartidaService
{
    private const ALLOWED_FIELDS = [
        'jogos_id_jogo' => 'i',
        'equipes_id_equipe' => 'i',
        'resultado_partida' => 'i',
        'status_partida' => 's',
    ];

    public function __construct(private readonly PartidaRepository $partidas)
    {
    }

    /** @return array<string, mixed>|null */
    public function encontrar(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->partidas->find($id);
    }

    /**
     * Atualiza somente os campos públicos suportados pela API.
     *
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): bool
    {
        $id = (int) ($data['id_partida'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Identificador de partida inválido.');
        }

        $fields = [];
        foreach (self::ALLOWED_FIELDS as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if ($type === 'i') {
                if (!is_numeric($data[$field])) {
                    throw new InvalidArgumentException("Valor inválido para {$field}.");
                }
                $value = (int) $data[$field];
                if ($field === 'resultado_partida' && $value < 0) {
                    throw new InvalidArgumentException('O resultado da partida não pode ser negativo.');
                }
                $fields[$field] = $value;
                continue;
            }
            $value = trim((string) $data[$field]);
            if ($value === '') {
                throw new InvalidArgumentException("Valor inválido para {$field}.");
            }
            $fields[$field] = $value;
        }

        if ($fields === []) {
            throw new InvalidArgumentException('Nenhum dado enviado para atualização.');
        }

        return $this->partidas->update($id, $fields);
    }
}
