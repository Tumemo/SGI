<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\EdicaoService;
use App\Interclasse\Domain\EdicaoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EdicaoServiceTest extends TestCase
{
    public function testValidatesAndCreatesEdition(): void
    {
        $repository = new InMemoryEdicaoRepository();
        $result = (new EdicaoService($repository))->criar([
            'nome_interclasse' => '  Interclasses 2026 ',
            'ano_interclasse' => '2026-09-04',
        ]);

        self::assertSame(1, $result['id']);
        self::assertSame('Interclasses 2026', $repository->created['nome_interclasse']);
    }

    public function testRejectsNegativeEditionScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EdicaoService(new InMemoryEdicaoRepository()))->atualizar(1, ['ponto_1_lugar' => -1]);
    }
}

final class InMemoryEdicaoRepository implements EdicaoRepository
{
    /** @var array<string, mixed> */
    public array $created = [];

    public function list(array $filters): array
    {
        return [];
    }

    public function create(array $data): array
    {
        $this->created = $data;
        return ['id' => 1, 'equipes_padrao_garantidas' => 0, 'erros_equipes' => []];
    }

    public function update(int $id, array $data): void
    {
    }
}
