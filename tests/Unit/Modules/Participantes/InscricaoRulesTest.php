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
}
