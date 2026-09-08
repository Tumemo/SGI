<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\ArtilheiroService;
use App\Modules\Competicoes\Domain\ArtilheiroRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ArtilheiroServiceTest extends TestCase
{
    public function testRegistersAndUpdatesGoals(): void
    {
        $repository = new InMemoryArtilheiroRepository();
        $service = new ArtilheiroService($repository);
        self::assertSame(1, $service->registrar(4, 8, 2));
        self::assertTrue($service->atualizar(4, 8, 3));
        self::assertSame(3, $repository->goals);
    }

    public function testRejectsNegativeGoals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ArtilheiroService(new InMemoryArtilheiroRepository()))->registrar(4, 8, -1);
    }
}

final class InMemoryArtilheiroRepository implements ArtilheiroRepository
{
    public int $goals = 0;

    public function create(int $userId, int $gameId, int $goals): int
    {
        $this->goals = $goals;
        return 1;
    }

    public function update(int $userId, int $gameId, int $goals): bool
    {
        $this->goals = $goals;
        return true;
    }

    public function editionOfGame(int $gameId): ?int
    {
        return 1;
    }

    public function editionOfUser(int $userId): ?int
    {
        return 1;
    }

    public function roleOfUser(int $userId): ?int
    {
        return 3;
    }

    public function athleteParticipatesInGame(int $userId, int $gameId): bool
    {
        return true;
    }
}
