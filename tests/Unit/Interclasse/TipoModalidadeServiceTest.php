<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Modules\Interclasses\Application\TipoModalidadeNaoEncontradoException;
use App\Modules\Interclasses\Application\TipoModalidadeService;
use App\Modules\Interclasses\Domain\TipoModalidadeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TipoModalidadeServiceTest extends TestCase
{
    public function testCreatesTypeWithActiveStatusByDefault(): void
    {
        $repository = new InMemoryTipoModalidadeRepository();
        $id = (new TipoModalidadeService($repository))->criar(['nome_tipo_modalidade' => 'Mata-Mata']);

        self::assertSame(1, $id);
        self::assertSame('1', $repository->rows[1]['status_tipo_modalidade']);
    }

    public function testRequiresTypeName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TipoModalidadeService(new InMemoryTipoModalidadeRepository()))->criar([]);
    }

    public function testRejectsUnknownTypeOnUpdate(): void
    {
        $this->expectException(TipoModalidadeNaoEncontradoException::class);
        (new TipoModalidadeService(new InMemoryTipoModalidadeRepository()))->atualizar([
            'id_tipo_modalidade' => 77,
            'nome_tipo_modalidade' => 'Individual',
        ]);
    }
}

final class InMemoryTipoModalidadeRepository implements TipoModalidadeRepository
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

    public function update(int $id, array $data): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        $this->rows[$id] = [...$this->rows[$id], ...$data];
        return true;
    }
}
