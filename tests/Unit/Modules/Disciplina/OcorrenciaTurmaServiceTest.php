<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Disciplina;

use App\Modules\Disciplina\Application\OcorrenciaTurmaNaoEncontradaException;
use App\Modules\Disciplina\Application\OcorrenciaTurmaService;
use App\Modules\Disciplina\Domain\OcorrenciaTurmaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OcorrenciaTurmaServiceTest extends TestCase
{
    public function testRegistersNormalizedOccurrence(): void
    {
        $repository = new InMemoryOcorrenciaTurmaRepository();
        $id = (new OcorrenciaTurmaService($repository))->registrar([
            'turmas_id_turma' => 2,
            'interclasses_id_interclasse' => 3,
            'titulo_ocorrencia' => '  Atraso  ',
            'descricao_ocorrencia' => '  Descrição  ',
            'data_ocorrencia' => '2026-09-04',
            'pontos_descontados' => 10,
        ]);

        self::assertSame(1, $id);
        self::assertSame('Atraso', $repository->created['titulo_ocorrencia']);
        self::assertSame('Descrição', $repository->created['descricao_ocorrencia']);
        self::assertNull($repository->created['usuarios_id_usuario']);
    }

    public function testRejectsIncompleteOccurrence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new OcorrenciaTurmaService(new InMemoryOcorrenciaTurmaRepository()))->registrar([
            'turmas_id_turma' => 2,
        ]);
    }

    public function testUnknownOccurrenceCannotBeDeleted(): void
    {
        $this->expectException(OcorrenciaTurmaNaoEncontradaException::class);
        (new OcorrenciaTurmaService(new InMemoryOcorrenciaTurmaRepository()))->excluir(99);
    }
}

final class InMemoryOcorrenciaTurmaRepository implements OcorrenciaTurmaRepository
{
    /** @var array<string, mixed> */
    public array $created = [];

    public function list(array $filters): array
    {
        return [];
    }

    public function create(array $data): int
    {
        $this->created = $data;
        return 1;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}
