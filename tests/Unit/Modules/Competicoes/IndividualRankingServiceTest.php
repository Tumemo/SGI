<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\IndividualRankingService;
use App\Modules\Competicoes\Domain\IndividualRankingRepository;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testRankingIncompletoNaoCriaJogoDeAgenda(): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        try {
            $service->registrar(7, ['primeiro' => 11, 'segundo' => 12]);
            self::fail('Ranking incompleto deveria ser rejeitado.');
        } catch (\InvalidArgumentException) {
            self::assertNull($repository->rankingCall);
        }
    }

    public function testAceitaIdsComoStringsDecimaisSemCoercaoPermissiva(): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        $service->registrar(7, ['primeiro' => '11', 'segundo' => '12', 'terceiro' => '13']);

        self::assertSame([7, ['primeiro' => 11, 'segundo' => 12, 'terceiro' => 13]], $repository->rankingCall);
    }

    public function testPreservaIdExplicitoDoJogoQueVeioDaTela(): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        $service->registrar(7, ['primeiro' => 11, 'segundo' => 12, 'terceiro' => 13], 22);

        self::assertSame(22, $repository->gameIdCall);
    }

    #[DataProvider('invalidRankingIds')]
    public function testRejeitaIdsQueParecemNumericosMasNaoSaoInteirosPositivos(mixed $value): void
    {
        $repository = new IndividualRankingRepositoryFake();
        $service = new IndividualRankingService($repository);

        try {
            $service->registrar(7, ['primeiro' => $value, 'segundo' => 12, 'terceiro' => 13]);
            self::fail('O ID inválido deveria ser rejeitado.');
        } catch (\InvalidArgumentException) {
            self::assertNull($repository->rankingCall);
        }
    }

    /** @return iterable<string,array{mixed}> */
    public static function invalidRankingIds(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'float' => [1.5];
        yield 'boolean' => [true];
        yield 'prefixo' => ['12abc'];
        yield 'notacao' => ['1e2'];
        yield 'lista' => [[11]];
        yield 'overflow' => ['999999999999999999999999999999999999'];
    }
}

final class IndividualRankingRepositoryFake implements IndividualRankingRepository
{
    /** @var array{0:int,1:array{primeiro:int,segundo:int,terceiro:int}}|null */
    public ?array $rankingCall = null;
    public ?int $gameIdCall = null;

    public function salvarRanking(int $modalityId, array $ranking, ?int $gameId = null): array
    {
        $this->rankingCall = [$modalityId, $ranking];
        $this->gameIdCall = $gameId;
        return ['success' => true];
    }

    public function criarJogoAgenda(int $modalityId): array
    {
        return ['success' => true];
    }
}
