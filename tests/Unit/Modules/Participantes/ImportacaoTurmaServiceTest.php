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
        $repository->expects(self::once())->method('import')->with([['nome' => 'Aluno', 'rm' => '123', 'turma' => '6EFB']], 7, 9)->willReturn([
            'status' => 'sucesso',
            'cadastrados' => 1,
            'duplicados' => 2,
            'erros' => ['RM 456: data de nascimento inválida.'],
        ]);
        $result = (new ImportacaoTurmaService($repository, $reader))->importar('lista.pdf', 7, 0);
        self::assertTrue($result['success']);
        self::assertSame(
            'Importação concluída: 1 registros inseridos, 2 duplicados ignorados. Senha inicial: sesi-senai. A troca é obrigatória no primeiro acesso.',
            $result['message'],
        );
        self::assertSame(['RM 456: data de nascimento inválida.'], $result['avisos']);
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

    public function testRepositoryInternalFailureIsRaisedInsteadOfReturnedToTheClient(): void
    {
        $repository = $this->createMock(ImportacaoTurmaRepository::class);
        $reader = $this->createMock(AlunoPdfReader::class);
        $repository->method('findClass')->willReturn(['nome_turma' => '6EFB', 'interclasses_id_interclasse' => 9]);
        $reader->method('read')->willReturn([['nome' => 'Aluno', 'rm' => 'N07-SQL-MARKER', 'data_nascimento' => '2010-01-01']]);
        $repository->method('import')->willReturn([
            'status' => 'erro',
            'mensagem' => 'N07_SQL_MARKER C:/synthetic/private/Import.php:47 #0',
        ]);

        try {
            (new ImportacaoTurmaService($repository, $reader))->importar('synthetic.pdf', 7, 9);
            self::fail('Falhas internas do repositório não podem ser convertidas em resposta de dados inválidos.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('N07_SQL_MARKER', $exception->getMessage());
        }
    }
}
