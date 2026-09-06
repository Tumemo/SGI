<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Infrastructure;

use InvalidArgumentException;
use RuntimeException;

final class RegulamentoStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @param array<string,mixed> $upload */
    public function save(array $upload): string
    {
        $path = (string) ($upload['tmp_name'] ?? '');
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($path)) {
            throw new InvalidArgumentException('Falha ao receber o regulamento.');
        }
        if (filesize($path) > 20 * 1024 * 1024 || strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf' || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new InvalidArgumentException('O arquivo deve ser um PDF de até 20 MB.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Falha ao preparar armazenamento do regulamento.');
        }
        $filename = 'reg_' . bin2hex(random_bytes(12)) . '.pdf';
        if (!move_uploaded_file($path, $this->directory . '/' . $filename)) {
            throw new RuntimeException('Falha ao salvar regulamento.');
        }
        return $filename;
    }

    public function remove(string $filename): void
    {
        $path = $this->directory . '/' . basename($filename);
        if (is_file($path) && !unlink($path)) {
            error_log('Não foi possível remover o regulamento não confirmado.');
        }
    }
}
