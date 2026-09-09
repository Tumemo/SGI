<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\ModalidadeNaoEncontradaException;
use App\Modules\Competicoes\Application\ModalidadeService;
use App\Modules\Competicoes\Domain\ModalidadeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ModalidadeServiceTest extends TestCase
{
    public function testNormalizesCommonGenderAliasesAndLimits(): void
    {
        $repository = new InMemoryModalidadeRepository();
        $id = (new ModalidadeService($repository))->criar([
            'nome_modalidade' => 'Vôlei',
            'genero_modalidade' => 'M',
            'tipos_modalidades_id_tipo_modalidade' => 1,
            'categorias_id_categoria' => 2,
            'interclasses_id_interclasse' => 3,
            'max_equipes' => 4,
        ]);

        self::assertSame(1, $id);
        self::assertSame('MASC', $repository->rows[1]['genero_modalidade']);
        self::assertSame(4, $repository->rows[1]['max_equipes']);
    }

    public function testUpdatesGender(): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $id = $service->criar([
            'nome_modalidade' => 'Futsal',
            'genero_modalidade' => 'MASC',
            'tipos_modalidades_id_tipo_modalidade' => 1,
            'categorias_id_categoria' => 2,
            'interclasses_id_interclasse' => 3,
        ]);

        $service->atualizar([
            'id_modalidade' => $id,
            'genero_modalidade' => 'FEM',
        ]);

        self::assertSame('FEM', $repository->rows[$id]['genero_modalidade']);
    }

    public function testRejectsIncompleteCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ModalidadeService(new InMemoryModalidadeRepository()))->criar([
            'nome_modalidade' => 'Futsal',
            'genero_modalidade' => 'MASC',
        ]);
    }

    public function testRejectsUnknownUpdateAndInvalidGender(): void
    {
        $service = new ModalidadeService(new InMemoryModalidadeRepository());
        try {
            $service->criar([
                'nome_modalidade' => 'Teste',
                'genero_modalidade' => 'ALIEN',
                'tipos_modalidades_id_tipo_modalidade' => 1,
                'categorias_id_categoria' => 1,
                'interclasses_id_interclasse' => 1,
            ]);
            self::fail('Gênero inválido deveria ser rejeitado.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(ModalidadeNaoEncontradaException::class);
        $service->atualizar(['id_modalidade' => 99, 'nome_modalidade' => 'Não existe']);
    }
}

final class InMemoryModalidadeRepository implements ModalidadeRepository
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

    public function deactivate(int $id): bool
    {
        return isset($this->rows[$id]);
    }
}
