<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\EquipeNaoEncontradaException;
use App\Interclasse\Application\EquipeService;
use App\Interclasse\Domain\EquipeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EquipeServiceTest extends TestCase
{
    public function testCreatesTeamWithTrimmedCustomName(): void
    {
        $repository = new InMemoryEquipeRepository();
        $created = (new EquipeService($repository))->criar([
            'modalidades_id_modalidade' => 2,
            'turmas_id_turma' => 3,
            'nome_equipe' => '  Azul  ',
        ]);

        self::assertSame(1, $created['id_equipe']);
        self::assertSame('Azul', $created['nome_equipe']);
        self::assertSame('1', $repository->rows[1]['status_equipe']);
    }

    public function testRejectsInvalidTeamRelationshipsAndEmptyUpdate(): void
    {
        $service = new EquipeService(new InMemoryEquipeRepository());
        $this->expectException(InvalidArgumentException::class);
        $service->criar(['modalidades_id_modalidade' => 0, 'turmas_id_turma' => 3]);
    }

    public function testNormalizesUserIdsBeforeDelegating(): void
    {
        $repository = new InMemoryEquipeRepository();
        $service = new EquipeService($repository);
        $service->adicionarUsuarios(5, [10, '10', 0, -1, 12]);

        self::assertSame([10, 12], $repository->linkedUsers);
    }

    public function testUnknownTeamCannotBeUpdatedOrDeleted(): void
    {
        $service = new EquipeService(new InMemoryEquipeRepository());
        $this->expectException(EquipeNaoEncontradaException::class);
        $service->atualizar(['id_equipe' => 99, 'nome_equipe' => 'Inexistente']);
    }
}

final class InMemoryEquipeRepository implements EquipeRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var list<int> */
    public array $linkedUsers = [];

    public function create(array $data): array
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = $data;
        return ['id_equipe' => $id, 'nome_equipe' => $data['nome_equipe']];
    }

    public function addUsers(int $teamId, array $userIds): void
    {
        $this->linkedUsers = $userIds;
    }

    public function removeUser(int $teamId, int $userId): void
    {
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
