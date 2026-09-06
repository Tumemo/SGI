<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Domain;

interface ImportacaoTurmaRepository
{
    /** @return array<string, mixed>|null */
    public function findClass(int $id): ?array;

    public function findActiveEdition(): ?int;

    /** @param list<array<string, mixed>> $students
     * @return array<string, mixed>
     */
    public function import(array $students, int $class, int $edition): array;
}
