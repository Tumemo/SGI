<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\ModalidadeNaoEncontradaException;
use App\Modules\Competicoes\Application\ModalidadeService;
use App\Modules\Competicoes\Domain\ModalidadeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('invalidLimits')]
    public function testRejectsInvalidLimitOnCreate(string $field, mixed $value): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $payload = self::validPayload();
        $payload[$field] = $value;

        try {
            $service->criar($payload);
            self::fail("{$field} inválido deveria ser rejeitado na criação.");
        } catch (InvalidArgumentException) {
            self::assertSame([], $repository->rows);
        }

    }

    #[DataProvider('invalidLimits')]
    public function testRejectsInvalidLimitOnPartialUpdate(string $field, mixed $value): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $repository->rows[9] = [...self::validPayload(), 'max_inscrito_modalidade' => 12, 'max_equipes' => 4];
        $before = $repository->rows[9];
        try {
            $service->atualizar(['id_modalidade' => 9, $field => $value]);
            self::fail("{$field} inválido deveria ser rejeitado na atualização parcial.");
        } catch (InvalidArgumentException) {
            self::assertSame($before, $repository->rows[9]);
        }
    }

    public static function invalidLimits(): iterable
    {
        foreach (['max_inscrito_modalidade', 'max_equipes'] as $field) {
            yield "{$field} negative" => [$field, -1];
            yield "{$field} small negative fraction" => [$field, -0.5];
            yield "{$field} small negative string" => [$field, '-0.5'];
            yield "{$field} fraction" => [$field, 1.5];
            yield "{$field} decimal string" => [$field, '1.5'];
            yield "{$field} boolean" => [$field, true];
            yield "{$field} array" => [$field, [1]];
            yield "{$field} arbitrary text" => [$field, 'abc'];
            yield "{$field} signed INT overflow" => [$field, 2147483648];
        }
    }

    #[DataProvider('validLimits')]
    public function testNormalizesSupportedUnlimitedAndBoundedLimitsOnCreateAndPartialUpdate(string $field, mixed $value, int|null $expected): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $payload = self::validPayload();
        $payload[$field] = $value;
        $id = $service->criar($payload);
        self::assertSame($expected, $repository->rows[$id][$field]);

        $service->atualizar(['id_modalidade' => $id, $field => $value]);
        self::assertSame($expected, $repository->rows[$id][$field]);
    }

    public static function validLimits(): iterable
    {
        foreach (['null' => null, 'empty' => ''] as $kind => $value) {
            yield "max inscritos unlimited {$kind}" => ['max_inscrito_modalidade', $value, 0];
            yield "max equipes unlimited {$kind}" => ['max_equipes', $value, null];
        }
        foreach (['integer' => 0, 'string' => '0'] as $kind => $value) {
            yield "max inscritos zero {$kind}" => ['max_inscrito_modalidade', $value, 0];
            yield "max equipes zero {$kind}" => ['max_equipes', $value, null];
        }
        foreach (['integer-one' => 1, 'string-one' => '1', 'integer-max' => 2147483647, 'string-max' => '2147483647'] as $kind => $value) {
            $expected = (int) $value;
            yield "max inscritos bounded {$kind}" => ['max_inscrito_modalidade', $value, $expected];
            yield "max equipes bounded {$kind}" => ['max_equipes', $value, $expected];
        }
    }

    #[DataProvider('invalidStatuses')]
    public function testRejectsStatusOutsideTheDatabaseEnumOnCreate(mixed $status): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $payload = self::validPayload();
        $payload['status_modalidade'] = $status;

        try {
            $service->criar($payload);
            self::fail('Status fora do ENUM deveria ser rejeitado na criação.');
        } catch (InvalidArgumentException) {
            self::assertSame([], $repository->rows);
        }

    }

    #[DataProvider('invalidStatuses')]
    public function testRejectsStatusOutsideTheDatabaseEnumOnPartialUpdate(mixed $status): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $repository->rows[9] = [...self::validPayload(), 'status_modalidade' => '1'];
        try {
            $service->atualizar(['id_modalidade' => 9, 'status_modalidade' => $status]);
            self::fail('Status fora do ENUM deveria ser rejeitado na atualização parcial.');
        } catch (InvalidArgumentException) {
            self::assertSame('1', $repository->rows[9]['status_modalidade']);
        }
    }

    public static function invalidStatuses(): iterable
    {
        yield 'unknown enum value' => ['2'];
        yield 'arbitrary text' => ['active'];
        yield 'empty value' => [''];
        yield 'boolean true' => [true];
        yield 'boolean false' => [false];
        yield 'array' => [[1]];
        yield 'float' => [1.0];
        yield 'explicit null' => [null];
    }

    #[DataProvider('validStatuses')]
    public function testAcceptsOnlyTheTwoDatabaseStatusesOnCreateAndPartialUpdate(mixed $status): void
    {
        $repository = new InMemoryModalidadeRepository();
        $service = new ModalidadeService($repository);
        $payload = self::validPayload();
        $payload['status_modalidade'] = $status;
        $id = $service->criar($payload);

        self::assertSame((string) $status, $repository->rows[$id]['status_modalidade']);
        $service->atualizar(['id_modalidade' => $id, 'status_modalidade' => $status]);
        self::assertSame((string) $status, $repository->rows[$id]['status_modalidade']);
    }

    public static function validStatuses(): iterable
    {
        yield 'inactive integer' => [0];
        yield 'active integer' => [1];
        yield 'inactive string' => ['0'];
        yield 'active string' => ['1'];
    }

    private static function validPayload(): array
    {
        return [
            'nome_modalidade' => 'Modalidade de teste',
            'genero_modalidade' => 'MASC',
            'tipos_modalidades_id_tipo_modalidade' => 1,
            'categorias_id_categoria' => 2,
            'interclasses_id_interclasse' => 3,
        ];
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
