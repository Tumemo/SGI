<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Participantes;

use App\Modules\Participantes\Application\ImportacaoTurmaService;
use App\Modules\Participantes\Domain\ImportacaoTurmaRepository;
use App\Modules\Participantes\Domain\AlunoPdfReader;
use PHPUnit\Framework\TestCase;

final class ImportacaoTurmaServiceTest extends TestCase
{
    public function testUsesTheClassEditionAndNormalizesClassNames(): void
    {
        $repository = $this->createMock(ImportacaoTurmaRepository::class);
        $reader = $this->createMock(AlunoPdfReader::class);
        $repository->method('findClass')->with(7)->willReturn(['nome_turma' => '6EFB', 'interclasses_id_interclasse' => 9]);
        $reader->method('read')->with('lista.pdf')->willReturn([['nome' => 'Aluno', 'rm' => '123', 'turma' => 'Outra']]);
        $repository->expects(self::once())->method('import')->with([['nome' => 'Aluno', 'rm' => '123', 'turma' => '6EFB']], 7, 9)->willReturn(['status' => 'sucesso', 'cadastrados' => 1, 'duplicados' => 2, 'erros' => []]);
        $result = (new ImportacaoTurmaService($repository, $reader))->importar('lista.pdf', 7, 0);
        self::assertTrue($result['success']);
        self::assertSame('Importação concluída: 1 registros inseridos, 2 duplicados ignorados', $result['message']);
    }

    public function testRejectsAnotherEditionBeforeReadingOrWritingStudents(): void
    {
        $repository = $this->createMock(ImportacaoTurmaRepository::class);
        $reader = $this->createMock(AlunoPdfReader::class);
        $repository->method('findClass')->willReturn(['nome_turma' => '6EFB', 'interclasses_id_interclasse' => 9]);
        $reader->expects(self::never())->method('read');
        $repository->expects(self::never())->method('import');
        $this->expectException(\InvalidArgumentException::class);
        (new ImportacaoTurmaService($repository, $reader))->importar('lista.pdf', 7, 10);
    }

    public function testEmptyPdfPreservesFallbackWithoutWritingStudents(): void
    {
        $repository = $this->createMock(ImportacaoTurmaRepository::class);
        $reader = $this->createMock(AlunoPdfReader::class);
        $repository->method('findClass')->willReturn(['nome_turma' => '6EFB', 'interclasses_id_interclasse' => 9]);
        $reader->method('read')->willReturn([]);
        $repository->expects(self::never())->method('import');
        $result = (new ImportacaoTurmaService($repository, $reader))->importar('lista.pdf', 7, 9);
        self::assertFalse($result['success']);
        self::assertTrue($result['fallback_converter']);
    }
}
