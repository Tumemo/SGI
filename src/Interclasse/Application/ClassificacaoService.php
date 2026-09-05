<?php

declare(strict_types=1);

namespace App\Interclasse\Application;

use App\Interclasse\Domain\ClassificacaoRepository;
use InvalidArgumentException;

final class ClassificacaoService
{
    public function __construct(private readonly ClassificacaoRepository $classificacao)
    {
    }

    /**
     * @return array{modalidade_id:int,podio:list<array<string, mixed>>}
     */
    public function gerar(int $modalityId): array
    {
        if ($modalityId <= 0) {
            throw new InvalidArgumentException('ID da modalidade é obrigatório.');
        }
        return [
            'modalidade_id' => $modalityId,
            'podio' => $this->classificacao->podium($modalityId),
        ];
    }
}
