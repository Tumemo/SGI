<?php

declare(strict_types=1);

namespace App\Autenticacao\Domain;

interface InterclasseRepository
{
    public function findActiveId(): ?int;
}
