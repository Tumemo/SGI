<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\FotoStorage;
use App\Modules\Acesso\Domain\PerfilRepository;

final class FotoService
{
    public function __construct(private readonly PerfilRepository $profiles, private readonly FotoStorage $storage)
    {
    }

    public function find(int $id): string
    {
        return (string) ($this->profiles->find($id)['foto_usuario'] ?? '');
    }

    public function replace(int $id, string $temporaryPath): string
    {
        $previous = $this->find($id);
        $filename = $this->storage->save($temporaryPath);
        try {
            $this->profiles->setPhoto($id, $filename);
        } catch (\Throwable $exception) {
            $this->storage->remove($filename);
            throw $exception;
        }
        $this->storage->remove($previous);
        return $filename;
    }

    public function remove(int $id): void
    {
        $previous = $this->find($id);
        $this->profiles->setPhoto($id, '');
        $this->storage->remove($previous);
    }
}
