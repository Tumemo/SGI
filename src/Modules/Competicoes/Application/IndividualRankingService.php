<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\IndividualRankingRepository;
use InvalidArgumentException;

final class IndividualRankingService
{
    public function __construct(private readonly IndividualRankingRepository $repository)
    {
    }

    /** @param array<string,mixed>|null $ranking @return array<string,mixed> */
    public function registrar(int $modalityId, ?array $ranking): array
    {
        if ($modalityId <= 0) {
            throw new InvalidArgumentException('Informe o ID da modalidade.');
        }
        if ($ranking === null) {
            return $this->repository->criarJogoAgenda($modalityId);
        }
        $normalized = [
            'primeiro' => isset($ranking['primeiro']) ? (int) $ranking['primeiro'] : 0,
            'segundo' => isset($ranking['segundo']) ? (int) $ranking['segundo'] : 0,
            'terceiro' => isset($ranking['terceiro']) ? (int) $ranking['terceiro'] : 0,
        ];
        if (in_array(0, $normalized, true)) {
            throw new InvalidArgumentException('É necessário informar o 1º, 2º e 3º lugar.');
        }
        if (count(array_unique($normalized)) !== 3) {
            throw new InvalidArgumentException('Os participantes do 1º, 2º e 3º lugar devem ser diferentes.');
        }
        return $this->repository->salvarRanking($modalityId, $normalized);
    }
}
