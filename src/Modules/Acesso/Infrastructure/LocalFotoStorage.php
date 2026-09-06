<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\FotoStorage;
use InvalidArgumentException;
use RuntimeException;

final class LocalFotoStorage implements FotoStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    public function save(string $temporaryPath): string
    {
        if (!is_uploaded_file($temporaryPath) || filesize($temporaryPath) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Envie uma imagem de até 5 MB.');
        }
        $image = @getimagesize($temporaryPath);
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$image['mime'] ?? ''] ?? null;
        if ($extension === null) {
            throw new InvalidArgumentException('Formato inválido. Use JPG, PNG, GIF ou WebP.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Erro ao preparar armazenamento.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($temporaryPath, $this->directory . '/' . $filename)) {
            throw new RuntimeException('Erro ao salvar arquivo.');
        }
        return $filename;
    }

    public function remove(string $filename): void
    {
        if ($filename === '') {
            return;
        }
        $path = $this->directory . '/' . basename($filename);
        if (is_file($path) && !unlink($path)) {
            error_log('Não foi possível remover a foto substituída.');
        }
    }
}
