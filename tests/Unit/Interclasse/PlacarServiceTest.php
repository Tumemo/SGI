<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Modules\Interclasses\Application\PlacarInvalidoException;
use App\Modules\Interclasses\Application\PlacarService;
use PHPUnit\Framework\TestCase;

final class PlacarServiceTest extends TestCase
{
    public function testAcceptsWinningScoreForNewGame(): void
    {
        (new PlacarService())->validarFinalizacao([2, 1]);
        self::assertTrue(true);
    }

    public function testRejectsZeroScoreWhenFinalizing(): void
    {
        $this->expectExceptionMessage('Não é possível finalizar um jogo com placar 0x0.');
        (new PlacarService())->validarFinalizacao([0, 0]);
    }

    public function testUsesSpecificMessageWhenChangingFinishedGameToZero(): void
    {
        $this->expectExceptionMessage('Não é possível alterar o placar de um jogo finalizado para 0x0.');
        (new PlacarService())->validarAlteracao([0, 0]);
    }

    public function testRejectsTieWithTheSameRuleForBothFlows(): void
    {
        $service = new PlacarService();
        try {
            $service->validarFinalizacao([1, 1]);
            self::fail('Era esperado rejeitar empate.');
        } catch (PlacarInvalidoException $exception) {
            self::assertStringContainsString('não pode terminar empatado', $exception->getMessage());
        }

        $this->expectException(PlacarInvalidoException::class);
        $service->validarAlteracao([3, 3]);
    }
}
