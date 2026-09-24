<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\CronogramaBracketPlanner;
use PHPUnit\Framework\TestCase;

final class CronogramaBracketPlannerTest extends TestCase
{
    /** @dataProvider bracketSizes */
    public function testCreatesExactNormalAndByeNodes(int $teamCount, int $normalCount, int $byeCount): void
    {
        $nodes = CronogramaBracketPlanner::plan(7, 11, range(1, $teamCount));
        $normals = array_values(array_filter($nodes, static fn (array $node): bool => $node['tipo_no'] === 'normal'));
        $byes = array_values(array_filter($nodes, static fn (array $node): bool => $node['tipo_no'] === 'bye'));

        self::assertCount($normalCount, $normals);
        self::assertCount($byeCount, $byes);
        self::assertCount($normalCount + $byeCount, $nodes);
        self::assertSame(count($nodes), count(array_unique(array_column($nodes, 'chave_tag'))));
        foreach ($nodes as $node) {
            if ($node['origem_a_tag'] !== null) {
                self::assertNotSame($node['chave_tag'], $node['origem_a_tag']);
            }
            if ($node['tipo_no'] === 'normal') {
                self::assertGreaterThanOrEqual(2, count($node['equipe_ids']));
            }
        }
    }

    /** @return iterable<string,array{int,int,int}> */
    public static function bracketSizes(): iterable
    {
        yield 'three teams' => [3, 2, 1];
        yield 'four teams' => [4, 3, 0];
        yield 'six teams' => [6, 5, 1];
        yield 'eight teams' => [8, 7, 0];
    }

    public function testCrossClassBracketUsesModalidadeIdentityAndAllowsNullTurma(): void
    {
        $nodes = CronogramaBracketPlanner::plan(9, null, [11, 22, 33]);

        self::assertNotEmpty($nodes);
        self::assertTrue(array_reduce($nodes, static fn (bool $valid, array $node): bool => $valid && str_starts_with((string) $node['chave_tag'], 'PL:9:'), true));
        self::assertSame([
            'PL:9:0:MM:4:0:N',
            'PL:9:0:MM:4:1:B',
            'PL:9:0:MM:2:0:N',
        ], array_column($nodes, 'chave_tag'));
    }

    public function testSixTeamsKeepThePublishedIntermediateByeAndCanonicalOrigins(): void
    {
        $nodes = CronogramaBracketPlanner::plan(7, null, [6, 5, 4, 3, 2, 1]);
        $opening = array_values(array_filter($nodes, static fn (array $node): bool => $node['fase_largura'] === 8));
        $middle = array_values(array_filter($nodes, static fn (array $node): bool => $node['fase_largura'] === 4));
        $final = array_values(array_filter($nodes, static fn (array $node): bool => $node['fase_largura'] === 2));
        $bye = array_values(array_filter($middle, static fn (array $node): bool => $node['tipo_no'] === 'bye'));

        self::assertCount(3, $opening);
        self::assertSame(['N', 'N', 'N'], array_map(static fn (array $node): string => substr((string) $node['chave_tag'], -1), $opening));
        self::assertCount(2, $middle);
        self::assertCount(1, $bye);
        self::assertCount(1, $final);
        self::assertCount(2, $bye[0]['equipe_ids']);
        self::assertSame('PL:7:0:MM:8:2:N', $bye[0]['origem_a_tag']);
        self::assertNull($bye[0]['origem_b_tag']);
        self::assertSame('PL:7:0:MM:4:0:N', $final[0]['origem_a_tag']);
        self::assertSame('PL:7:0:MM:4:1:B', $final[0]['origem_b_tag']);
    }
}
