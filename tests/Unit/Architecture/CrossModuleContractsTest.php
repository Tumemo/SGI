<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class CrossModuleContractsTest extends TestCase
{
    public function testParticipantesAndEventosDoNotImportTeamInfrastructure(): void
    {
        foreach ([
            __DIR__ . '/../../../src/Modules/Participantes/Infrastructure/MysqliInscricaoRepository.php',
            __DIR__ . '/../../../src/Modules/Eventos/Infrastructure/MysqliEdicaoRepository.php',
        ] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame([], self::importsFromOtherModuleInfrastructure($source, 'Competicoes'), $file);
            self::assertStringNotContainsString('MysqliEquipePadraoRepository::', $source, $file);
        }
    }

    /** @return list<string> */
    private static function importsFromOtherModuleInfrastructure(string $source, string $module): array
    {
        preg_match_all(
            '/^use\\s+([^;]+);/m',
            preg_replace('/^\\s*\/\\/.*$/m', '', $source) ?? $source,
            $matches,
        );

        $prefix = 'App\\Modules\\' . $module . '\\Infrastructure\\';

        return array_values(array_filter(
            array_map('trim', $matches[1] ?? []),
            static fn (string $import): bool => str_starts_with($import, $prefix),
        ));
    }
}
