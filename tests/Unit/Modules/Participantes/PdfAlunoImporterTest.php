<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Participantes;

use App\Modules\Participantes\Infrastructure\PdfAlunoImporter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PdfAlunoImporterTest extends TestCase
{
    public function testRejectsOversizedPdfBeforeInvokingTheParser(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sgi-pdf-size-');
        self::assertIsString($path);

        $handle = fopen($path, 'wb');
        self::assertIsResource($handle);
        fwrite($handle, "%PDF-1.4\n");
        fseek($handle, PdfAlunoImporter::MAX_PDF_BYTES + 1);
        fwrite($handle, "x");
        fclose($handle);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('10 MB');
            PdfAlunoImporter::extrairLinhasDoPdf($path);
        } finally {
            @unlink($path);
        }
    }
}
