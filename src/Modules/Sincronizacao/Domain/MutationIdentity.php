<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Domain;

use InvalidArgumentException;

final readonly class MutationIdentity
{
    public function __construct(public ?string $key, public string $fingerprint)
    {
        if ($key !== null && preg_match('/\A[a-zA-Z0-9._:-]{12,180}\z/D', $key) !== 1) {
            throw new InvalidArgumentException('Identificador de sincronização inválido.');
        }
    }

    public static function create(string $key, int $actorId, string $body): self
    {
        // Preserve the exact queued payload: the same key must represent the same command.
        return new self(trim($key) ?: null, hash('sha256', $actorId . ':' . $body));
    }
}
