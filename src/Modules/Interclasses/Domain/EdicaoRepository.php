<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Domain;

interface EdicaoRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters): array;

    /**
     * @param array<string, mixed> $data
     * @return array{id:int,equipes_padrao_garantidas:int,erros_equipes:list<string>}
     */
    public function create(array $data): array;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void;
}
