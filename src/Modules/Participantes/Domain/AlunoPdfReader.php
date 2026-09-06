<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Domain;

interface AlunoPdfReader
{
    /** @return list<array<string, mixed>> */
    public function read(string $path): array;
}
