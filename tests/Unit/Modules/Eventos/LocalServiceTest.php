<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Eventos;

use App\Modules\Eventos\Application\LocalNaoEncontradoException;
use App\Modules\Eventos\Application\LocalService;
use App\Modules\Eventos\Domain\LocalRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocalServiceTest extends TestCase
{
    public function testCreatesLocalWithDefaults(): void
    {
        $repository = new InMemoryLocalRepository();
        $id = (new LocalService($repository))->criar([
            'nome_local' => 'Ginásio A',
            'interclasses_id_interclasse' => 10,
        ]);

        self::assertSame(1, $id);
        self::assertSame('1', $repository->rows[1]['disponivel_local']);
        self::assertNull($repository->rows[1]['carga_local']);
    }

    public function testRejectsInvalidCreateData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LocalService(new InMemoryLocalRepository()))->criar([
            'nome_local' => 'Quadra',
            'interclasses_id_interclasse' => 10,
            'carga_local' => -1,
        ]);
    }

    public function testUpdatesOnlyKnownFieldsAndRequiresExistingLocal(): void
    {
        $repository = new InMemoryLocalRepository();
        $repository->rows[2] = [
            'nome_local' => 'Quadra antiga',
            'status_local' => '1',
            'disponivel_local' => '1',
            'carga_local' => 4,
        ];

        $service = new LocalService($repository);
        $service->atualizar(['id_local' => 2, 'nome_local' => 'Quadra nova', 'carga_local' => 8]);

        self::assertSame('Quadra nova', $repository->rows[2]['nome_local']);
        self::assertSame(8, $repository->rows[2]['carga_local']);
        self::assertArrayNotHasKey('campo_injetado', $repository->rows[2]);

        $this->expectException(LocalNaoEncontradoException::class);
        $service->atualizar(['id_local' => 99, 'nome_local' => 'Não existe']);
    }

    public function testRejectsDeletingUnknownLocal(): void
    {
        $this->expectException(LocalNaoEncontradoException::class);
        (new LocalService(new InMemoryLocalRepository()))->excluir(99);
    }
}

final class InMemoryLocalRepository implements LocalRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

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

    public function update(int $id, array $data, ?int $interclasseId = null): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        $this->rows[$id] = [...$this->rows[$id], ...$data];
        return true;
    }

    public function delete(int $id): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        unset($this->rows[$id]);
        return true;
    }
}
