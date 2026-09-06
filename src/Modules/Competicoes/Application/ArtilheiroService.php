<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\ArtilheiroRepository;
use InvalidArgumentException;

final class ArtilheiroService
{
    public function __construct(private readonly ArtilheiroRepository $artilheiros)
    {
    }

    public function registrar(int $userId, int $gameId, int $goals): int
    {
        $this->validate($userId, $gameId, $goals);
        return $this->artilheiros->create($userId, $gameId, $goals);
    }

    public function atualizar(int $userId, int $gameId, int $goals): bool
    {
        $this->validate($userId, $gameId, $goals);
        return $this->artilheiros->update($userId, $gameId, $goals);
    }

    private function validate(int $userId, int $gameId, int $goals): void
    {
        if ($userId <= 0 || $gameId <= 0) {
            throw new InvalidArgumentException('Usuário e jogo devem ser válidos.');
        }
        if ($goals < 0) {
            throw new InvalidArgumentException('A quantidade de gols não pode ser negativa.');
        }
    }
}
