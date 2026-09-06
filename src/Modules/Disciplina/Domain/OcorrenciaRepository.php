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
}
