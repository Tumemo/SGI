<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\EquipeCapacityRules;
use PHPUnit\Framework\TestCase;

final class EquipeCapacityRulesTest extends TestCase
{
    public function testUnlimitedAndAvailableCapacitiesAreAccepted(): void
    {
        self::assertTrue(EquipeCapacityRules::podeAtivar(null, 100));
        self::assertTrue(EquipeCapacityRules::podeAtivar(0, 100));
        self::assertTrue(EquipeCapacityRules::podeAtivar(2, 1));
    }

    public function testCapacityRejectsOnlyWhenAConfiguredLimitIsReached(): void
    {
        self::assertFalse(EquipeCapacityRules::podeAtivar(2, 2));
        self::assertTrue(EquipeCapacityRules::podeAtivar(2, 1));
    }
}
