<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Application;

use App\Modules\Interclasses\Domain\PontuacaoRepository;
use InvalidArgumentException;

final class PontuacaoService
{
    public function __construct(private readonly PontuacaoRepository $pontuacoes)
    {
    }

    /** @return list<array<string, mixed>> */
    public function ranking(): array
    {
        return $this->pontuacoes->ranking();
    }

    public function atualizar(array $data): void
    {
        $id = (int) ($data['id_pontuacao'] ?? 0);
        if ($id <= 0 || !array_key_exists('pontos', $data)) {
            throw new InvalidArgumentException('Dados incompletos (id_pontuacao e pontos são obrigatórios).');
        }
        $pontos = filter_var($data['pontos'], FILTER_VALIDATE_INT);
        if ($pontos === false) {
            throw new InvalidArgumentException('A pontuação deve ser um número inteiro.');
        }
        if (!$this->pontuacoes->atualizar($id, (int) $pontos)) {
            throw new PontuacaoNaoEncontradaException();
        }
    }
}
