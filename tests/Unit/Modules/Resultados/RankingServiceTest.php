<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Resultados;

use App\Modules\Resultados\Application\RankingService;
use App\Modules\Resultados\Domain\RankingRepository;
use App\Modules\Participantes\Domain\TurmaRankingUpdater;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RankingServiceTest extends TestCase
{
    public function testUpdatesScoreAndReturnsRepositoryResult(): void
    {
        $repository = new InMemoryRankingRepository();
        $updater = new InMemoryTurmaRankingUpdater();
        $service = new RankingService($repository, $updater);
        self::assertTrue($service->atualizar(['id_turma' => 7, 'pontuacao_turma' => 10]));
        self::assertSame(['id_turma' => 7, 'pontuacao_turma' => 10], $updater->updated);
    }

    public function testRejectsNegativeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RankingService(new InMemoryRankingRepository(), new InMemoryTurmaRankingUpdater()))->atualizar([
            'id_turma' => 7,
            'pontuacao_turma' => -1,
        ]);
    }
}

final class InMemoryRankingRepository implements RankingRepository
{
    public function list(array $filters): array
    {
        return [];
    }

}

final class InMemoryTurmaRankingUpdater implements TurmaRankingUpdater
{
    /** @var array<string, mixed> */
    public array $updated = [];

    /** @param array<string, mixed> $data */
    public function atualizarPeloRanking(array $data): bool
    {
        if ((int) ($data['pontuacao_turma'] ?? 0) < 0) {
            throw new InvalidArgumentException('A pontuação não pode ser negativa.');
        }
        $this->updated = $data;
        return true;
    }
}
