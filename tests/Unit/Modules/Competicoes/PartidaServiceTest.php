<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\PartidaService;
use App\Modules\Competicoes\Domain\PartidaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PartidaServiceTest extends TestCase
{
    public function testUpdatesOnlySupportedFieldsAndNormalizesValues(): void
    {
        $repository = new InMemoryPartidaRepository();
        $updated = (new PartidaService($repository))->atualizar([
            'id_partida' => '7',
            'resultado_partida' => '3',
            'status_partida' => '1',
            'campo_interno' => 'ignorado',
        ]);

        self::assertTrue($updated);
        self::assertSame(7, $repository->id);
        self::assertSame(['resultado_partida' => 3, 'status_partida' => '1'], $repository->fields);
    }

    public function testRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PartidaService(new InMemoryPartidaRepository()))->atualizar(['id_partida' => 0]);
    }

    public function testRejectsEmptyUpdate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PartidaService(new InMemoryPartidaRepository()))->atualizar(['id_partida' => 7]);
    }

    public function testRejectsNonNumericIntegerField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PartidaService(new InMemoryPartidaRepository()))->atualizar([
            'id_partida' => 7,
            'resultado_partida' => 'abc',
        ]);
    }
}

final class InMemoryPartidaRepository implements PartidaRepository
{
    public int $id = 0;

    /** @var array<string, int|string> */
    public array $fields = [];

    /**
     * @param array<string, int|string> $fields
     */
    public function update(int $id, array $fields): bool
    {
        $this->id = $id;
        $this->fields = $fields;
        return true;
    }
}
