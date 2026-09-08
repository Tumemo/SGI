<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Domain;

interface EdicaoConsulta
{
    public function findActiveId(): ?int;

    public function isActive(int $editionId): bool;

    public function isUserEditionClosed(int $userId): bool;
}
