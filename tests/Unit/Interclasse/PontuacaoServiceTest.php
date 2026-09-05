<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\PontuacaoNaoEncontradaException;
use App\Interclasse\Application\PontuacaoService;
use App\Interclasse\Domain\PontuacaoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PontuacaoServiceTest extends TestCase
{
    public function testUpdatesIntegerScore(): void
    {
        $repository = new InMemoryPontuacaoRepository([5 => 10]);
        (new PontuacaoService($repository))->atualizar(['id_pontuacao' => 5, 'pontos' => '20']);

        self::assertSame(20, $repository->scores[5]);
    }

    public function testRejectsInvalidScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PontuacaoService(new InMemoryPontuacaoRepository([1 => 0])))->atualizar([
            'id_pontuacao' => 1,
            'pontos' => 'dez',
        ]);
    }

    public function testReportsUnknownScore(): void
    {
        $this->expectException(PontuacaoNaoEncontradaException::class);
        (new PontuacaoService(new InMemoryPontuacaoRepository()))->atualizar([
            'id_pontuacao' => 999,
            'pontos' => 1,
        ]);
    }
}

final class InMemoryPontuacaoRepository implements PontuacaoRepository
{
    /** @param array<int, int> $scores */
    public function __construct(public array $scores = [])
    {
    }

    public function ranking(): array
    {
        return [];
    }

    public function atualizar(int $id, int $pontos): bool
    {
        if (!array_key_exists($id, $this->scores)) {
            return false;
        }
        $this->scores[$id] = $pontos;
        return true;
    }
}
