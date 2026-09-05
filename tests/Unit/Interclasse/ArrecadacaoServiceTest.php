<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\ArrecadacaoHistoricoJaRemovidoException;
use App\Interclasse\Application\ArrecadacaoHistoricoNaoEncontradoException;
use App\Interclasse\Application\ArrecadacaoService;
use App\Interclasse\Domain\ArrecadacaoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ArrecadacaoServiceTest extends TestCase
{
    public function testNormalizesBatchAndIgnoresZeroQuantities(): void
    {
        $repository = new InMemoryArrecadacaoRepository();
        (new ArrecadacaoService($repository))->adicionarLote([
            'id_interclasse' => 8,
            'arrecadacoes' => [
                ['id_turma' => 3, 'quantidade' => '4.126'],
                ['id_turma' => 4, 'quantidade' => 0],
            ],
        ], 12);

        self::assertSame(8, $repository->editionId);
        self::assertSame(12, $repository->userId);
        self::assertSame([['id_turma' => 3, 'quantidade' => 4.13]], $repository->items);
    }

    public function testRejectsEmptyBatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ArrecadacaoService(new InMemoryArrecadacaoRepository()))->adicionarLote([
            'id_interclasse' => 8,
            'arrecadacoes' => [['id_turma' => 3, 'quantidade' => 0]],
        ], 12);
    }

    public function testMapsNotFoundRemovalToDomainException(): void
    {
        $repository = new InMemoryArrecadacaoRepository();
        $this->expectException(ArrecadacaoHistoricoNaoEncontradoException::class);
        (new ArrecadacaoService($repository))->remover(10, 8);
    }

    public function testMapsAlreadyRemovedStateToDomainException(): void
    {
        $repository = new InMemoryArrecadacaoRepository();
        $repository->removeState = 'already_removed';
        $this->expectException(ArrecadacaoHistoricoJaRemovidoException::class);
        (new ArrecadacaoService($repository))->remover(10, 8);
    }
}

final class InMemoryArrecadacaoRepository implements ArrecadacaoRepository
{
    public int $editionId = 0;
    public int $userId = 0;
    /** @var list<array{id_turma: int, quantidade: float}> */
    public array $items = [];
    public string $removeState = 'not_found';

    public function listByInterclasse(int $interclasseId): array
    {
        return [];
    }

    public function addBatch(int $interclasseId, int $userId, array $items): void
    {
        $this->editionId = $interclasseId;
        $this->userId = $userId;
        $this->items = $items;
    }

    public function remove(int $historicoId, int $interclasseId): string
    {
        return $this->removeState;
    }
}
