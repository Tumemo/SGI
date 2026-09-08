<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\IndividualRankingService;
use App\Modules\Competicoes\Domain\IndividualRankingRepository;
use PHPUnit\Framework\TestCase;

final class IndividualRankingServiceTest extends TestCase
{
    public function testNormalizaRankingEDelegaUmaVez(): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        $result = $service->registrar(7, ['primeiro' => 11, 'segundo' => 12, 'terceiro' => 13]);

        self::assertSame(['success' => true], $result);
        self::assertSame([7, ['primeiro' => 11, 'segundo' => 12, 'terceiro' => 13]], $repository->rankingCall);
    }

    public function testRankingDuplicadoNaoEscreve(): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        try {
            $service->registrar(7, ['primeiro' => 11, 'segundo' => 11, 'terceiro' => 13]);
            self::fail('Ranking duplicado deveria ser rejeitado.');
        } catch (\InvalidArgumentException) {
            self::assertNull($repository->rankingCall);
        }
    }
}

final class IndividualRankingRepositoryFake implements IndividualRankingRepository
{
    /** @var array{0:int,1:array{primeiro:int,segundo:int,terceiro:int}}|null */
    public ?array $rankingCall = null;

    public function salvarRanking(int $modalityId, array $ranking): array
    {
        $this->rankingCall = [$modalityId, $ranking];
        return ['success' => true];
    }

    public function criarJogoAgenda(int $modalityId): array
    {
        return ['success' => true];
    }
}
