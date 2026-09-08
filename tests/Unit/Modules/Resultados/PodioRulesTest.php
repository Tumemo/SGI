<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Resultados;

use App\Modules\Resultados\Domain\PodioRules;
use PHPUnit\Framework\TestCase;

final class PodioRulesTest extends TestCase
{
    public function testSumsPositionDeltasWhenTheSameClassHoldsMoreThanOnePlace(): void
    {
        self::assertSame(
            [7 => -8, 8 => 8],
            PodioRules::deltas(
                [
                    ['posicao' => 1, 'id_turma' => 7, 'pontos' => 20],
                    ['posicao' => 2, 'id_turma' => 8, 'pontos' => 12],
                ],
                [
                    ['posicao' => 1, 'id_turma' => 8, 'pontos' => 20],
                    ['posicao' => 2, 'id_turma' => 7, 'pontos' => 12],
                ],
            ),
        );
    }

    public function testZeroCreditsRemainRepresentedWithoutChangingTotals(): void
    {
        self::assertSame([], PodioRules::deltas(
            [['posicao' => 3, 'id_turma' => 7, 'pontos' => 0]],
            [['posicao' => 3, 'id_turma' => 8, 'pontos' => 0]],
        ));
    }

    public function testInactiveCreditsDoNotParticipateInTotal(): void
    {
        self::assertSame([8 => 12], PodioRules::deltas(
            [['posicao' => 1, 'id_turma' => 7, 'pontos' => 20, 'ativo' => 0]],
            [['posicao' => 1, 'id_turma' => 8, 'pontos' => 12]],
        ));
    }
}
