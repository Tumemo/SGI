<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Participantes;

use App\Modules\Participantes\Domain\InscricaoRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InscricaoRulesTest extends TestCase
{
    public function testBuildsTheUnionOfAlreadyRegisteredAndRequestedModalities(): void
    {
        self::assertSame([1, 2, 4], InscricaoRules::uniaoModalidades([1, 2], [4, 2, 1]));
    }

    public function testRejectsUnionAboveTheThreeModalityLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InscricaoRules::uniaoModalidades([1, 2], [3, 4]);
    }

    public function testNormalizesPositiveIdsWithoutChangingTheirMeaning(): void
    {
        self::assertSame([8, 3], InscricaoRules::normalizarIds(['8', 8, 0, -1, 3]));
    }

    public function testChecksGenderAndCategoryEligibility(): void
    {
        self::assertTrue(InscricaoRules::modalidadeCompativel('MASC', 'MASC', 4, 4));
        self::assertTrue(InscricaoRules::modalidadeCompativel('FEM', 'FEM', 4, 4));
        self::assertTrue(InscricaoRules::modalidadeCompativel('MASC', 'MISTO', 4, 4));
        self::assertTrue(InscricaoRules::modalidadeCompativel('FEM', 'MISTO', 4, 4));
        self::assertFalse(InscricaoRules::modalidadeCompativel('MASC', 'FEM', 4, 4));
        self::assertFalse(InscricaoRules::modalidadeCompativel('FEM', 'MASC', 4, 4));
        self::assertFalse(InscricaoRules::modalidadeCompativel('MASC', 'MASC', 4, 5));
        self::assertFalse(InscricaoRules::modalidadeCompativel('OUTRO', 'MISTO', 4, 4));
        self::assertFalse(InscricaoRules::modalidadeCompativel('MASC', 'MASC', 0, 0));
    }
}
