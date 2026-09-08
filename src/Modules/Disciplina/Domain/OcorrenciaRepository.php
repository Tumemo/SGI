<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Domain;

interface OcorrenciaRepository
{
    /**
     * @param array<string, mixed> $data
     * @return array{id:int,evento:?string}
     */
    public function create(array $data): array;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    public function editionOfUser(int $userId): ?int;

    public function roleOfUser(int $userId): ?int;

    public function editionOfGame(int $gameId): ?int;

    public function editionOfTurma(int $turmaId): ?int;

    public function gameContainsTurma(int $gameId, int $turmaId): bool;

    public function userBelongsToTurma(int $userId, int $turmaId): bool;

    public function userParticipatesInGame(int $userId, int $gameId): bool;
}
