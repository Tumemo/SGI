<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface PerfilRepository
{
    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    public function update(int $id, string $name, ?string $passwordHash): void;

    public function setPhoto(int $id, ?string $filename): void;
}
