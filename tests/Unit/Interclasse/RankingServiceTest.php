<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Modules\Interclasses\Application\RankingService;
use App\Modules\Interclasses\Domain\RankingRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RankingServiceTest extends TestCase
{
    public function testUpdatesScoreAndReturnsRepositoryResult(): void
    {
        $repository = new InMemoryRankingRepository();
        $service = new RankingService($repository);
        self::assertTrue($service->atualizar(['id_turma' => 7, 'pontuacao_turma' => 10]));
        self::assertSame(10, $repository->updated['pontuacao_turma']);
    }

    public function testRejectsNegativeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RankingService(new InMemoryRankingRepository()))->atualizar([
            'id_turma' => 7,
            'pontuacao_turma' => -1,
        ]);
    }
}

final class InMemoryRankingRepository implements RankingRepository
{
    /** @var array<string, mixed> */
    public array $updated = [];

    public function list(array $filters): array
    {
        return [];
    }

    public function updateTeam(int $id, array $data): bool
    {
        $this->updated = $data;
        return true;
    }
}
