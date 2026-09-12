<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Participantes;

use App\Modules\Participantes\Domain\TurmaScopeRules;
use PHPUnit\Framework\TestCase;

final class TurmaScopeRulesTest extends TestCase
{
    public function testCategoryMustBeActiveAndBelongToTheTargetEdition(): void
    {
        self::assertTrue(TurmaScopeRules::categoryMatchesEdition(4, [
            'interclasses_id_interclasse' => 4,
            'status_categoria' => '1',
        ]));
        self::assertFalse(TurmaScopeRules::categoryMatchesEdition(4, [
            'interclasses_id_interclasse' => 5,
            'status_categoria' => '1',
        ]));
        self::assertFalse(TurmaScopeRules::categoryMatchesEdition(4, [
            'interclasses_id_interclasse' => 4,
            'status_categoria' => '0',
        ]));
        self::assertFalse(TurmaScopeRules::categoryMatchesEdition(4, null));
        self::assertFalse(TurmaScopeRules::categoryMatchesEdition(0, [
            'interclasses_id_interclasse' => 4,
            'status_categoria' => '1',
        ]));
    }

    public function testEditionTransferIsAllowedOnlyWithoutDescendants(): void
    {
        self::assertTrue(TurmaScopeRules::canTransferEdition(4, 4, true));
        self::assertTrue(TurmaScopeRules::canTransferEdition(4, 5, false));
        self::assertFalse(TurmaScopeRules::canTransferEdition(4, 5, true));
    }
}
