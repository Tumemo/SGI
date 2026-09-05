<?php

declare(strict_types=1);

namespace App\Autenticacao\Domain;

interface TermosRepository
{
    /** @return array<string, mixed>|null */
    public function findUser(int $userId): ?array;

    public function findActiveEdition(): ?int;

    public function assignEdition(int $userId, int $interclasseId): void;

    public function accept(int $userId, int $interclasseId, string $dateTime): void;
}
