<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Participantes;

use App\Modules\Participantes\Application\TurmaDuplicadaException;
use App\Modules\Participantes\Application\TurmaNaoEncontradaException;
use App\Modules\Participantes\Application\TurmaService;
use App\Modules\Participantes\Domain\TurmaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TurmaServiceTest extends TestCase
{
    public function testCreatesClassWithNormalizedValues(): void
    {
        $repository = new InMemoryTurmaRepository();
        $id = (new TurmaService($repository))->criar([
            'interclasses_id_interclasse' => 4,
            'categorias_id_categoria' => 5,
            'nome_turma' => '  6EF  ',
            'turno_turma' => 'manha',
            'nome_fantasia_turma' => '  Azul  ',
        ]);

        self::assertSame(1, $id);
        self::assertSame('6EF', $repository->rows[1]['nome_turma']);
        self::assertSame('Azul', $repository->rows[1]['nome_fantasia_turma']);
        self::assertSame('1', $repository->rows[1]['status_turma']);
    }

    public function testRejectsDuplicateAndInvalidCreate(): void
    {
        $repository = new InMemoryTurmaRepository();
        $repository->duplicate = true;
        $service = new TurmaService($repository);
        $this->expectException(TurmaDuplicadaException::class);
        $service->criar([
            'interclasses_id_interclasse' => 1,
            'categorias_id_categoria' => 1,
            'nome_turma' => '6EF',
        ]);
    }

    public function testRejectsMissingRelationship(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TurmaService(new InMemoryTurmaRepository()))->criar([
            'nome_turma' => '6EF',
        ]);
    }

    public function testUnknownClassCannotBeUpdated(): void
    {
        $this->expectException(TurmaNaoEncontradaException::class);
        (new TurmaService(new InMemoryTurmaRepository()))->atualizar([
            'id_turma' => 99,
            'nome_turma' => '6EF',
        ]);
    }

    public function testRankingUpdateKeepsAllowedScoreAdjustmentInTheSharedTurmaService(): void
    {
        $repository = new InMemoryTurmaRepository();
        $repository->rows[7] = ['nome_turma' => '6EF', 'pontuacao_turma' => 0];

        self::assertTrue((new TurmaService($repository))->atualizarPeloRanking([
            'id_turma' => 7,
            'nome_fantasia_turma' => 'Azul',
            'pontuacao_turma' => 18,
        ]));
        self::assertSame('Azul', $repository->rows[7]['nome_fantasia_turma']);
        self::assertSame(18, $repository->rows[7]['pontuacao_turma']);
    }

    public function testRankingUpdateRejectsNegativeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TurmaService(new InMemoryTurmaRepository()))->atualizarPeloRanking([
            'id_turma' => 7,
            'pontuacao_turma' => -1,
        ]);
    }
}

final class InMemoryTurmaRepository implements TurmaRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public bool $duplicate = false;

    public function list(array $filters): array
    {
        return [];
    }

    public function create(array $data): int
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = $data;
        return $id;
    }

    public function duplicateExists(int $interclasseId, string $name, ?string $shift): bool
    {
        return $this->duplicate;
    }

    public function update(int $id, array $data): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        $this->rows[$id] = [...$this->rows[$id], ...$data];
        return true;
    }

    public function delete(int $id): bool
    {
        return isset($this->rows[$id]);
    }
}
