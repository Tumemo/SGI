<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\AgendamentoSequencialScheduler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AgendamentoSequencialSchedulerTest extends TestCase
{
    public function testSplitsGamesAtDailyCutoffUsingMondayAndThursday(): void
    {
        $scheduler = new AgendamentoSequencialScheduler();
        $result = $scheduler->simulate(
            [
                ['chave_tag' => 'MM:4:0:N'],
                ['chave_tag' => 'MM:4:1:N'],
                ['chave_tag' => 'MM:2:0:N'],
            ],
            [
                ['data' => '2026-09-21', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1],
                ['data' => '2026-09-24', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1],
            ],
            [],
            ['duracao_min' => 60],
        );

        self::assertSame(10, $result['intervalo_troca_min']);
        self::assertSame(3, $result['resumo']['encaixados']);
        self::assertSame(0, $result['resumo']['pendentes']);
        self::assertSame('08:00:00', $result['proposta'][0]['inicio_jogo']);
        self::assertSame('09:10:00', $result['proposta'][1]['inicio_jogo']);
        self::assertSame('2026-09-21', $result['proposta'][2]['data_jogo']);
        self::assertSame('11:30', $result['limite_termino_padrao']);
    }

    public function testReportsNextDefaultSessionWhenOnlyFirstMondayIsProvided(): void
    {
        $result = (new AgendamentoSequencialScheduler())->simulate(
            [
                ['chave_tag' => 'MM:4:0:N'],
                ['chave_tag' => 'MM:4:1:N'],
                ['chave_tag' => 'MM:2:0:N'],
            ],
            [['data' => '2026-09-21', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1]],
            [],
            ['duracao_min' => 70],
        );

        self::assertSame(2, $result['resumo']['encaixados']);
        self::assertCount(1, $result['pendencias']);
        self::assertSame('2026-09-24', $result['proximo_dia_sugerido']);
        self::assertSame('08:00:00', $result['proximo_inicio_sugerido']);
        self::assertSame('11:30', $result['proximo_termino_sugerido']);
    }

    public function testKeepsDependencyAfterTheLastChildAndTenMinuteBuffer(): void
    {
        $result = (new AgendamentoSequencialScheduler())->simulate(
            [
                ['chave_tag' => 'MM:4:0:N'],
                ['chave_tag' => 'MM:4:1:N'],
                [
                    'chave_tag' => 'MM:2:0:N',
                    'dependencias' => ['MM:4:0:N', 'MM:4:1:N'],
                ],
            ],
            [['data' => '2026-09-15', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1]],
            [],
            ['duracao_min' => 60],
        );

        self::assertSame('10:20:00', $result['proposta'][2]['inicio_jogo']);
        self::assertSame('11:20:00', $result['proposta'][2]['termino_jogo']);
    }

    public function testAcceptsArbitraryWeekdays(): void
    {
        $result = (new AgendamentoSequencialScheduler())->simulate(
            [
                ['chave_tag' => 'MM:4:0:N'],
                ['chave_tag' => 'MM:4:1:N'],
            ],
            [
                ['data' => '2026-09-16', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1],
                ['data' => '2026-09-19', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1],
            ],
            [],
            ['duracao_min' => 120],
        );

        self::assertSame(2, $result['resumo']['encaixados']);
        self::assertSame('2026-09-16', $result['proposta'][0]['data_jogo']);
        self::assertSame('2026-09-19', $result['proposta'][1]['data_jogo']);
    }

    public function testRejectsIntervalSmallerThanTenMinutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AgendamentoSequencialScheduler())->simulate(
            [['chave_tag' => 'MM:2:0:N']],
            [['data' => '2026-09-15', 'inicio' => '08:00', 'fim' => '11:30', 'local' => 1]],
            [],
            ['intervalo_troca_min' => 5],
        );
    }

    public function testCalculatesDefaultMondayThursdaySuggestions(): void
    {
        $scheduler = new AgendamentoSequencialScheduler();

        self::assertSame('2026-09-24', $scheduler->nextSessionDate('2026-09-21'));
        self::assertSame('2026-09-28', $scheduler->nextSessionDate('2026-09-24'));
        self::assertSame('2026-09-14', $scheduler->firstSessionDate('2026-09-09'));
    }
}
