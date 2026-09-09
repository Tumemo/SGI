<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\AgendamentoBlocoScheduler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AgendamentoBlocoSchedulerTest extends TestCase
{
    public function testSchedulesDependenciesWithChangeoverAndRest(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [
                ['chave_tag' => 'MM:4:0:N', 'dependencias' => [], 'participantes' => ['equipe:1', 'equipe:2']],
                ['chave_tag' => 'MM:4:1:N', 'dependencias' => [], 'participantes' => ['equipe:3', 'equipe:4']],
                ['chave_tag' => 'MM:2:0:N', 'dependencias' => ['MM:4:0:N', 'MM:4:1:N'], 'participantes' => []],
            ],
            [['data' => '2026-09-10', 'inicio' => '08:00', 'fim' => '10:00', 'locais' => [1]]],
            [],
            ['duracao_min' => 20, 'intervalo_troca_min' => 5, 'descanso_min' => 15],
        );

        self::assertCount(3, $result['proposta']);
        self::assertSame('08:00:00', $result['proposta'][0]['inicio_jogo']);
        self::assertSame('08:25:00', $result['proposta'][1]['inicio_jogo']);
        self::assertSame('09:00:00', $result['proposta'][2]['inicio_jogo']);
        self::assertSame(0, $result['resumo']['pendentes']);
    }

    public function testUsesDifferentLocationsAtTheSameTimeAndBlocksSameLocation(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [
                ['chave_tag' => 'MM:4:0:N'],
                ['chave_tag' => 'MM:4:1:N'],
            ],
            [['data' => '2026-09-10', 'inicio' => '08:00', 'fim' => '09:00', 'locais' => [1, 2]]],
            [],
            ['duracao_min' => 30, 'intervalo_troca_min' => 0],
        );

        self::assertSame('08:00:00', $result['proposta'][0]['inicio_jogo']);
        self::assertSame('08:00:00', $result['proposta'][1]['inicio_jogo']);
        self::assertNotSame($result['proposta'][0]['locais_id_local'], $result['proposta'][1]['locais_id_local']);
    }

    public function testReportsUnscheduledDependencyWithoutPartialSuccess(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [['chave_tag' => 'MM:2:0:N', 'dependencias' => ['MM:4:0:N', 'MM:4:1:N']]],
            [['data' => '2026-09-10', 'inicio' => '08:00', 'fim' => '09:00', 'locais' => [1]]],
            [],
        );

        self::assertCount(0, $result['proposta']);
        self::assertSame('Dependência ainda sem programação.', $result['pendencias'][0]['motivo']);
        self::assertSame(['MM:4:0:N', 'MM:4:1:N'], $result['pendencias'][0]['dependencias']);
    }

    public function testRespectsExistingReservation(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [['chave_tag' => 'MM:4:0:N']],
            [['data' => '2026-09-10', 'inicio' => '08:00', 'fim' => '10:00', 'locais' => [1]]],
            [['data_reserva' => '2026-09-10', 'inicio_reserva' => '08:00:00', 'termino_reserva' => '08:30:00', 'id_local' => 1]],
            ['duracao_min' => 20],
        );

        self::assertSame('08:30:00', $result['proposta'][0]['inicio_jogo']);
    }

    public function testRespectsParticipantConflictEvenWhenLocationsDiffer(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [['chave_tag' => 'MM:4:0:N', 'participantes' => ['equipe:1']]],
            [['data' => '2026-09-10', 'inicio' => '08:00', 'fim' => '09:00', 'locais' => [2]]],
            [[
                'data_reserva' => '2026-09-10',
                'inicio_reserva' => '08:00:00',
                'termino_reserva' => '08:30:00',
                'id_local' => 1,
                'participantes' => ['equipe:1'],
            ]],
            ['duracao_min' => 20],
        );

        self::assertSame('08:30:00', $result['proposta'][0]['inicio_jogo']);
    }

    public function testDoesNotPlaceDependentMatchBeforeKnownPreviousDate(): void
    {
        $result = (new AgendamentoBlocoScheduler())->simulate(
            [[
                'chave_tag' => 'MM:2:0:N',
                'dependencias' => ['MM:4:0:N'],
                'dependencias_terminos' => [
                    'MM:4:0:N' => ['data' => '2026-09-11', 'end' => 600],
                ],
            ]],
            [
                ['data' => '2026-09-10', 'inicio' => '12:00', 'fim' => '13:00', 'locais' => [1]],
                ['data' => '2026-09-11', 'inicio' => '08:00', 'fim' => '12:00', 'locais' => [1]],
            ],
            [],
            ['duracao_min' => 30],
        );

        self::assertSame('2026-09-11', $result['proposta'][0]['data_jogo']);
        self::assertSame('10:00:00', $result['proposta'][0]['inicio_jogo']);
    }

    public function testRejectsInvalidWindow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AgendamentoBlocoScheduler())->simulate(
            [['chave_tag' => 'MM:4:0:N']],
            [['data' => '2026-09-10', 'inicio' => '09:00', 'fim' => '08:00', 'locais' => [1]]],
            [],
        );
    }

    public function testRejectsInvalidCalendarDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AgendamentoBlocoScheduler())->simulate(
            [['chave_tag' => 'MM:4:0:N']],
            [['data' => '2026-02-30', 'inicio' => '08:00', 'fim' => '09:00', 'locais' => [1]]],
            [],
        );
    }
}
