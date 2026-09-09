<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Database;

use App\Shared\Database\SqlFilters;
use PHPUnit\Framework\TestCase;

final class SqlFiltersTest extends TestCase
{
    public function testTeamFiltersKeepUserInputOutOfSql(): void
    {
        $filter = SqlFilters::aplicarFiltrosEquipes([
            'id_equipe' => '7 OR 1=1',
            'id_turma' => '3',
            'id_modalidade' => '2',
            'id_interclasse' => '4',
        ]);
        self::assertSame([7, 3, 2, 4], $filter['params']);
        self::assertSame('iiii', $filter['types']);
        self::assertSame(4, substr_count($filter['sql'], '?'));
        self::assertStringNotContainsString('OR 1=1', $filter['sql']);
    }
}
