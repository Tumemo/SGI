<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Disciplina;

use App\Modules\Disciplina\Domain\OcorrenciaDescricao;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OcorrenciaDescricaoTest extends TestCase
{
    public function testParsesCanonicalLegacyReferencesAndText(): void
    {
        self::assertSame(
            ['gameId' => 9, 'classId' => 3, 'text' => 'Cartão amarelo'],
            OcorrenciaDescricao::parseSubmitted('[JOGO:9][TURMA:3] Cartão amarelo', true),
        );
    }

    public function testPreservesNegativeTemporaryGameIdUntilTheControllerResolvesIt(): void
    {
        self::assertSame(
            ['gameId' => -5, 'classId' => 3, 'text' => 'Cartão temporário'],
            OcorrenciaDescricao::parseSubmitted('[JOGO:-5][TURMA:3] Cartão temporário', true),
        );
    }

    public function testRejectsDuplicateReorderedAndEmbeddedMarkers(): void
    {
        foreach ([
            '[JOGO:9][JOGO:10]Duplicado',
            '[TURMA:3][JOGO:9]Ordem divergente',
            'Texto [JOGO:9] marcador embutido',
        ] as $description) {
            try {
                OcorrenciaDescricao::parseSubmitted($description, true);
                self::fail('O texto com marcadores ambíguos deveria ser rejeitado: ' . $description);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testStoredLegacyParserFindsReferencesWithoutChangingTheirIds(): void
    {
        self::assertSame(
            ['gameId' => -5, 'classId' => 3],
            OcorrenciaDescricao::fromStored('Texto antigo [JOGO:-5][TURMA:3]'),
        );
    }
}
