<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface InterclasseRepository
{
    public function findActiveId(): ?int;
}
