<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\CategoriaInativaException;
use App\Interclasse\Application\CategoriaNaoEncontradaException;
use App\Interclasse\Application\CategoriaService;
use App\Interclasse\Domain\CategoriaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CategoriaServiceTest extends TestCase
{
    public function testCreatesCategoryWithSafeDefaults(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $service = new CategoriaService($repository);

        $id = $service->criar([
            'nome_categoria' => 'Categoria I',
            'interclasses_id_interclasse' => 10,
        ]);

        self::assertSame(1, $id);
        self::assertSame('1', $repository->rows[1]['status_categoria']);
    }

    public function testRejectsUpdatingAnInactiveCategory(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $repository->rows[2] = ['status_categoria' => '0'];

        $this->expectException(CategoriaInativaException::class);
        (new CategoriaService($repository))->atualizar([
            'id_categoria' => 2,
            'nome_categoria' => 'Alteração inválida',
        ]);
    }

    public function testRejectsUnknownCategoryOnDelete(): void
    {
        $this->expectException(CategoriaNaoEncontradaException::class);
        (new CategoriaService(new InMemoryCategoriaRepository()))->excluir(99);
    }

    public function testRequiresEditionOnCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CategoriaService(new InMemoryCategoriaRepository()))->criar(['nome_categoria' => 'Sem edição']);
    }
}

final class InMemoryCategoriaRepository implements CategoriaRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function listActive(array $filters): array
    {
        return [];
    }

    public function create(array $data): int
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = $data;
        return $id;
    }

    public function findStatus(int $id): ?string
    {
        return isset($this->rows[$id]) ? (string) $this->rows[$id]['status_categoria'] : null;
    }

    public function update(int $id, array $data): void
    {
        $this->rows[$id] = [...$this->rows[$id], ...$data];
    }

    public function deactivateCascade(int $id): void
    {
        $this->rows[$id]['status_categoria'] = '0';
    }
}
