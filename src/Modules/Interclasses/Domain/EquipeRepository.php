<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Domain;

interface EquipeRepository
{
    /**
     * Cria uma equipe e retorna o identificador e o nome efetivamente persistido.
     *
     * @param array<string, mixed> $data
     * @return array{id_equipe:int,nome_equipe:?string}
     */
    public function create(array $data): array;

    /**
     * @param list<int> $userIds
     */
    public function addUsers(int $teamId, array $userIds): void;

    public function removeUser(int $teamId, int $userId): void;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function deactivate(int $id): bool;
}
