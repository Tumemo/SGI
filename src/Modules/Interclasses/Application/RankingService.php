<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Application;

use App\Modules\Interclasses\Domain\RankingRepository;
use InvalidArgumentException;

final class RankingService
{
    public function __construct(private readonly RankingRepository $ranking)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->ranking->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(array $data): bool
    {
        $id = (int) ($data['id_turma'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Dados inválidos ou ID da turma ausente.');
        }
        $updates = [];
        foreach ([
            'interclasses_id_interclasse' => 'int',
            'nome_turma' => 'string',
            'turno_turma' => 'string',
            'nome_fantasia_turma' => 'string',
            'categorias_id_categoria' => 'int',
            'status_turma' => 'string',
            'pontuacao_turma' => 'int',
        ] as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $type === 'int' ? (int) $data[$field] : trim((string) $data[$field]);
            if ($field === 'nome_turma' && $value === '') {
                throw new InvalidArgumentException('O nome da turma não pode ser vazio.');
            }
            if ($type === 'int' && (int) $value < 0 && $field === 'pontuacao_turma') {
                throw new InvalidArgumentException('A pontuação não pode ser negativa.');
            }
            $updates[$field] = $value;
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo enviado para atualização.');
        }
        return $this->ranking->updateTeam($id, $updates);
    }
}
