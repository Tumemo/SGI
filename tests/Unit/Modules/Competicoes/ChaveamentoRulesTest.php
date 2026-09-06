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
}
