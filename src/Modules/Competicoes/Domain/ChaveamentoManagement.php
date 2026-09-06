<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface ChaveamentoManagement
{
    /** @return array<string, mixed>|null */
    public function modality(int $id): ?array;
    /** @return array<mixed> */
    public function read(int $id, bool $individual, string $action): array;
    /** @param array{primeiro:int, segundo:int, terceiro:int}|null $ranking
     * @return array<string, mixed>
     */
    public function saveIndividual(int $id, ?array $ranking): array;
    /** @return array<string, mixed> */
    public function createBracket(int $id): array;
}
