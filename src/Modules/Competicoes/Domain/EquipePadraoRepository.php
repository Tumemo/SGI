<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface EquipePadraoRepository
{
    public function findOrCreateDefault(int $modalityId, int $classId): ?int;

    /** @return array{criadas:int,erros:list<string>} */
    public function generateForEdition(int $editionId): array;
}
