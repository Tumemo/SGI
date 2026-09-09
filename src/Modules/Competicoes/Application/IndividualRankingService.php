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
    public function registrar(int $modalityId, ?array $ranking, ?int $gameId = null): array
    {
        if ($modalityId <= 0) {
            throw new InvalidArgumentException('Informe o ID da modalidade.');
        }
        if ($ranking === null) {
            return $this->repository->criarJogoAgenda($modalityId);
        }
        $normalized = [];
        foreach (['primeiro', 'segundo', 'terceiro'] as $position) {
            if (!array_key_exists($position, $ranking)) {
                throw new InvalidArgumentException('É necessário informar o 1º, 2º e 3º lugar.');
            }
            $value = $ranking[$position];
            if (is_int($value) && $value > 0) {
                $normalized[$position] = $value;
                continue;
            }
            if (is_string($value) && preg_match('/^[1-9][0-9]*$/', trim($value)) === 1) {
                $text = trim($value);
                $parsed = filter_var($text, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
                ]);
                if ($parsed !== false) {
                    $normalized[$position] = $parsed;
                    continue;
                }
            }
            throw new InvalidArgumentException('Cada posição deve conter um atleta válido.');
        }
        if (count(array_unique($normalized)) !== 3) {
            throw new InvalidArgumentException('Os participantes do 1º, 2º e 3º lugar devem ser diferentes.');
        }
        return $this->repository->salvarRanking($modalityId, $normalized, $gameId);
    }
}
