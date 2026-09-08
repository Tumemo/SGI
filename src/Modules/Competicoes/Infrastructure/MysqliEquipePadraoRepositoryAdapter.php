<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\EquipePadraoRepository;
use mysqli;

final class MysqliEquipePadraoRepositoryAdapter implements EquipePadraoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findOrCreateDefault(int $modalityId, int $classId): ?int
    {
        return MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($this->connection, $modalityId, $classId);
    }

    public function generateForEdition(int $editionId): array
    {
        return MysqliEquipePadraoRepository::gerarEquipesPadraoInterclasse($this->connection, $editionId);
    }
}
