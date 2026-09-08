<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Application;

use App\Modules\Eventos\Domain\EdicaoRepository;
use InvalidArgumentException;

final class EdicaoService
{
    public function __construct(private readonly EdicaoRepository $edicoes)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->edicoes->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id:int,equipes_padrao_garantidas:int,erros_equipes:list<string>}
     */
    public function criar(array $data): array
    {
        $name = trim((string) ($data['nome_interclasse'] ?? ''));
        $year = trim((string) ($data['ano_interclasse'] ?? ''));
        if ($name === '' || $year === '') {
            throw new InvalidArgumentException('Dados incompletos.');
        }
        return $this->edicoes->create([
            'nome_interclasse' => $name,
            'ano_interclasse' => $year,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function atualizar(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da edição é obrigatório.');
        }
        $updates = [];
        foreach (['nome_interclasse', 'ano_interclasse', 'regulamento_interclasse', 'status_interclasse'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                if ($field === 'nome_interclasse' && $value === '') {
                    throw new InvalidArgumentException('O nome da edição não pode ser vazio.');
                }
                if ($field === 'status_interclasse' && !in_array($value, ['0', '1'], true)) {
                    throw new InvalidArgumentException('O status da edição deve ser 0 ou 1.');
                }
                $updates[$field] = $value;
            }
        }
        foreach (['valor_item_arrecadacao', 'ponto_1_lugar', 'ponto_2_lugar', 'ponto_3_lugar'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = (int) $data[$field];
                if ($value < 0) {
                    throw new InvalidArgumentException('As pontuações não podem ser negativas.');
                }
                $updates[$field] = $value;
            }
        }
        if ($updates === []) {
            throw new InvalidArgumentException('Nenhum campo fornecido para atualização.');
        }
        $this->edicoes->update($id, $updates);
    }

    public function alterarStatus(int $id, string $status): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('O ID da edição é obrigatório.');
        }
        if (!in_array($status, ['0', '1'], true)) {
            throw new InvalidArgumentException('O status da edição deve ser 0 ou 1.');
        }
        $this->edicoes->update($id, ['status_interclasse' => $status]);
    }
}
