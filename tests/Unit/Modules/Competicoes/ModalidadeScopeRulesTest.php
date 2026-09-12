<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\ModalidadeScopeRules;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

final class ModalidadeScopeRulesTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testAcceptsAnActiveCategoryInTheSameEdition(): void
    {
        ModalidadeScopeRules::assertCategoryMatchesEdition(12, 12, '1');
    }

    public function testRejectsCategoryFromAnotherEdition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ModalidadeScopeRules::assertCategoryMatchesEdition(22, 12, '1');
    }

    public function testRejectsInactiveCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ModalidadeScopeRules::assertCategoryMatchesEdition(12, 12, '0');
    }

    #[DoesNotPerformAssertions]
    public function testAllowsEditionTransferOnlyWhenNoRelatedDataExists(): void
    {
        ModalidadeScopeRules::assertEditionTransferAllowed(12, 22, false);
        ModalidadeScopeRules::assertEditionTransferAllowed(12, 12, true);
    }

    public function testRejectsEditionTransferWithRelatedData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ModalidadeScopeRules::assertEditionTransferAllowed(12, 22, true);
    }
}
