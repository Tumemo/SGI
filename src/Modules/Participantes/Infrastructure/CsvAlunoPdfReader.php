<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

use App\Modules\Participantes\Domain\AlunoPdfReader;

final class CsvAlunoPdfReader implements AlunoPdfReader
{
    public function read(string $path): array
    {
        // Third-party parser output must never become part of the HTTP response.
        ob_start();
        try {
            $csv = substr($path, 0, -4) . '.csv';
            if (!PdfAlunoImporter::pdfParaCsv($path, $csv)) {
                return [];
            }
            return PdfAlunoImporter::extrairAlunosDoCsv($csv);
        } finally {
            ob_end_clean();
        }
    }
}
