<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\ChaveamentoRules;
use PHPUnit\Framework\TestCase;

final class ChaveamentoRulesTest extends TestCase
{
    public function testTagsPreserveTheOfflineProtocol(): void
    {
        self::assertSame('MM:8:2:N', ChaveamentoRules::tag(8, 2, 'N'));
        self::assertNull(ChaveamentoRules::parse('jogo sem tag'));
        $metadata = ChaveamentoRules::parse('MM:8:2:N');
        self::assertSame(8, $metadata['largura']);
        self::assertSame(2, $metadata['slot']);
    }

    public function testSiblingAndParentSlotsStayConsistent(): void
    {
        foreach (range(0, 15) as $slot) {
            $sibling = ChaveamentoRules::slotIrmao($slot);
            self::assertNotSame($slot, $sibling);
            self::assertSame($slot, ChaveamentoRules::slotIrmao($sibling));
            self::assertSame(ChaveamentoRules::slotPai($slot), ChaveamentoRules::slotPai($sibling));
        }
    }

    public function testDerivesThirdPlaceFromTheChampionsSemifinalLoser(): void
    {
        self::assertSame(12, ChaveamentoRules::terceiroLugarDoCampeao(10, [
            [
                'kind' => 'N',
                'partidas' => [
                    ['equipes_id_equipe' => 10, 'resultado_partida' => 3],
                    ['equipes_id_equipe' => 12, 'resultado_partida' => 1],
                ],
            ],
            [
                'kind' => 'N',
                'partidas' => [
                    ['equipes_id_equipe' => 20, 'resultado_partida' => 2],
                    ['equipes_id_equipe' => 22, 'resultado_partida' => 0],
                ],
            ],
        ]));
    }

    public function testByeSemifinalDoesNotCreateThirdPlace(): void
    {
        self::assertNull(ChaveamentoRules::terceiroLugarDoCampeao(10, [
            [
                'kind' => 'B',
                'partidas' => [
                    ['equipes_id_equipe' => 10, 'resultado_partida' => 1],
                ],
            ],
            [
                'kind' => 'N',
                'partidas' => [
                    ['equipes_id_equipe' => 20, 'resultado_partida' => 2],
                    ['equipes_id_equipe' => 22, 'resultado_partida' => 0],
                ],
            ],
        ]));
    }
}
