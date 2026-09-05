<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Application;

use App\Modules\Interclasses\Domain\ArrecadacaoRepository;
use InvalidArgumentException;

final class ArrecadacaoService
{
    public function __construct(private readonly ArrecadacaoRepository $arrecadacoes)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listar(int $interclasseId): array
    {
        if ($interclasseId <= 0) {
            throw new InvalidArgumentException('id_interclasse ausente.');
        }
        return $this->arrecadacoes->listByInterclasse($interclasseId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function adicionarLote(array $data, int $userId): void
    {
        $interclasseId = (int) ($data['id_interclasse'] ?? 0);
        if ($interclasseId <= 0 || !isset($data['arrecadacoes']) || !is_array($data['arrecadacoes'])) {
            throw new InvalidArgumentException('Dados incompletos no envio em lote.');
        }

        $items = [];
        foreach ($data['arrecadacoes'] as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Formato inválido de arrecadação.');
            }
            $turmaId = (int) ($item['id_turma'] ?? 0);
            if ($turmaId <= 0 || !array_key_exists('quantidade', $item) || !is_numeric($item['quantidade'])) {
                throw new InvalidArgumentException('Cada arrecadação precisa de turma e quantidade válidas.');
            }
            $quantidade = round((float) $item['quantidade'], 2);
            if (!is_finite($quantidade)) {
                throw new InvalidArgumentException('A quantidade da arrecadação é inválida.');
            }
            if ($quantidade == 0.0) {
                continue;
            }
            $items[] = ['id_turma' => $turmaId, 'quantidade' => $quantidade];
        }

        if ($items === []) {
            throw new InvalidArgumentException('Informe ao menos uma quantidade diferente de zero.');
        }
        $this->arrecadacoes->addBatch($interclasseId, $userId, $items);
    }

    public function remover(int $historicoId, int $interclasseId): void
    {
        if ($historicoId <= 0 || $interclasseId <= 0) {
            throw new InvalidArgumentException('id_historico e id_interclasse são obrigatórios.');
        }
        $result = $this->arrecadacoes->remove($historicoId, $interclasseId);
        if ($result === 'not_found') {
            throw new ArrecadacaoHistoricoNaoEncontradoException();
        }
        if ($result === 'already_removed') {
            throw new ArrecadacaoHistoricoJaRemovidoException();
        }
    }
}
