<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

interface FotoStorage
{
    public function save(string $temporaryPath): string;
    public function remove(string $filename): void;
}
