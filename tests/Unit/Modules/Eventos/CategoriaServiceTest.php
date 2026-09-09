<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Eventos;

use App\Modules\Eventos\Application\CategoriaDuplicadaException;
use App\Modules\Eventos\Application\CategoriaInativaException;
use App\Modules\Eventos\Application\CategoriaNaoEncontradaException;
use App\Modules\Eventos\Application\CategoriaService;
use App\Modules\Eventos\Domain\CategoriaRepository;
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

    public function testRejectsDuplicateNameInSameEditionOnCreate(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $repository->rows[1] = [
            'nome_categoria' => 'Categoria I',
            'status_categoria' => '1',
            'interclasses_id_interclasse' => 10,
        ];

        $this->expectException(CategoriaDuplicadaException::class);
        (new CategoriaService($repository))->criar([
            'nome_categoria' => ' categoria i ',
            'interclasses_id_interclasse' => 10,
        ]);
    }

    public function testAllowsSameNameInDifferentEdition(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $repository->rows[1] = [
            'nome_categoria' => 'Categoria I',
            'status_categoria' => '1',
            'interclasses_id_interclasse' => 10,
        ];

        $id = (new CategoriaService($repository))->criar([
            'nome_categoria' => 'Categoria I',
            'interclasses_id_interclasse' => 11,
        ]);

        self::assertSame(2, $id);
    }

    public function testRejectsRenamingCategoryToAnotherNameInSameEdition(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $repository->rows = [
            1 => [
                'nome_categoria' => 'Categoria I',
                'status_categoria' => '1',
                'interclasses_id_interclasse' => 10,
            ],
            2 => [
                'nome_categoria' => 'Categoria II',
                'status_categoria' => '1',
                'interclasses_id_interclasse' => 10,
            ],
        ];

        $this->expectException(CategoriaDuplicadaException::class);
        (new CategoriaService($repository))->atualizar([
            'id_categoria' => 1,
            'nome_categoria' => 'Categoria II',
        ]);
    }

    public function testAllowsKeepingTheOwnCategoryName(): void
    {
        $repository = new InMemoryCategoriaRepository();
        $repository->rows[1] = [
            'nome_categoria' => 'Categoria I',
            'status_categoria' => '1',
            'interclasses_id_interclasse' => 10,
        ];

        (new CategoriaService($repository))->atualizar([
            'id_categoria' => 1,
            'nome_categoria' => ' categoria i ',
        ]);

        self::assertSame('categoria i', $repository->rows[1]['nome_categoria']);
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
        $id = $this->rows === [] ? 1 : max(array_keys($this->rows)) + 1;
        $this->rows[$id] = $data;
        return $id;
    }

    public function find(int $id): ?array
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        return [
            'status_categoria' => (string) $this->rows[$id]['status_categoria'],
            'interclasses_id_interclasse' => (int) ($this->rows[$id]['interclasses_id_interclasse'] ?? 0),
        ];
    }

    public function duplicateExists(int $editionId, string $name, int $exceptId = 0): bool
    {
        foreach ($this->rows as $id => $row) {
            if ($id === $exceptId || (int) ($row['interclasses_id_interclasse'] ?? 0) !== $editionId) {
                continue;
            }
            if (strcasecmp(trim((string) ($row['nome_categoria'] ?? '')), trim($name)) === 0) {
                return true;
            }
        }

        return false;
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
