<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

use InvalidArgumentException;
use RuntimeException;

final class TurmaPdfStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @param array<string, mixed> $file
     * @param callable(string):array<string, mixed> $import
     * @return array<string, mixed>
     */
    public function process(array $file, int $class, callable $import): array
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Erro no upload do arquivo. Código: ' . ($file['error'] ?? UPLOAD_ERR_NO_FILE));
        }
        if ($class <= 0 || strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf') {
            throw new InvalidArgumentException('Apenas arquivos PDF são permitidos.');
        }
        $temporary = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($temporary) || file_get_contents($temporary, false, null, 0, 5) !== '%PDF-') {
            throw new InvalidArgumentException('O arquivo enviado não é um PDF válido.');
        }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Falha ao criar pasta de destino.');
        }
        $path = $this->directory . DIRECTORY_SEPARATOR . 'turma_' . $class . '.pdf';
        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Falha ao preparar importação.');
        }
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Falha ao bloquear importação simultânea.');
            }
            if (!@move_uploaded_file($temporary, $path)) {
                throw new RuntimeException('Falha ao salvar o arquivo no servidor.');
            }
            return $import($path);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
