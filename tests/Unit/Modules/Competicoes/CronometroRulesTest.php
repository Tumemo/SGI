<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\CronometroRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CronometroRulesTest extends TestCase
{
    public function testCalculatesRunningBalanceFromPersistedSnapshot(): void
    {
        self::assertSame(1170, CronometroRules::saldoAtual($this->state(1200, 0, 1200, 1000), 1030));
    }

    public function testPauseFreezesBalanceAndResumePreservesIt(): void
    {
        $paused = CronometroRules::transicionar($this->state(1200, 0, 1200, 1000), 'pausar', 1030);
        self::assertSame('Pausado', $paused['status_jogo']);
        self::assertSame(1170, $paused['tempo_restante_jogo']);
        self::assertNull($paused['data_inicio_real']);
        self::assertSame(1170, CronometroRules::saldoAtual($paused, 1120));

        $resumed = CronometroRules::transicionar($paused, 'retomar', 1120);
        self::assertSame('Iniciado', $resumed['status_jogo']);
        self::assertSame(1120, $resumed['data_inicio_real']);
        self::assertSame(1160, CronometroRules::saldoAtual($resumed, 1130));
    }

    public function testRepeatedResumeDoesNotResetReference(): void
    {
        $state = $this->state(1200, 0, 1170, 1120, 'Iniciado');
        $again = CronometroRules::transicionar($state, 'retomar', 1200);

        self::assertSame(1120, $again['data_inicio_real']);
        self::assertSame(1080, CronometroRules::saldoAtual($again, 1210));
    }

    public function testAbsoluteExtraIsAppliedOnceToMaterializedBalance(): void
    {
        $state = $this->state(1200, 0, 1170, null, 'Pausado');
        $updated = CronometroRules::transicionar($state, 'acrescentar', 2000, ['tempo_extra_jogo' => 60]);

        self::assertSame(60, $updated['tempo_extra_jogo']);
        self::assertSame(1230, $updated['tempo_restante_jogo']);
        self::assertSame(1230, CronometroRules::saldoAtual($updated, 2100));

        $replayed = CronometroRules::transicionar($updated, 'acrescentar', 2100, ['tempo_extra_jogo' => 60]);
        self::assertSame(1230, $replayed['tempo_restante_jogo']);
    }

    public function testZeroRemainsZeroUntilAValidExtraIsAdded(): void
    {
        $state = $this->state(1200, 0, 0, null, 'Pausado');
        self::assertSame(0, CronometroRules::saldoAtual($state, 2000));

        $updated = CronometroRules::transicionar($state, 'acrescentar', 2000, ['tempo_extra_jogo' => 60]);
        self::assertSame(60, $updated['tempo_restante_jogo']);
    }

    public function testNullBalanceUsesDurationAndExtraButZeroDoesNot(): void
    {
        self::assertSame(1260, CronometroRules::saldoAtual($this->state(1200, 60, null, null, 'Pausado'), 2000));
        self::assertSame(0, CronometroRules::saldoAtual($this->state(1200, 60, 0, null, 'Pausado'), 2000));
    }

    public function testDurationChangeAddsOnlyTheDifferenceToTheMaterializedBalance(): void
    {
        $updated = CronometroRules::transicionar(
            $this->state(1200, 0, 1170, 1000),
            'duracao',
            1030,
            ['duracao_jogo' => 1500],
        );

        self::assertSame(1500, $updated['duracao_jogo']);
        self::assertSame(1440, $updated['tempo_restante_jogo']);
        self::assertSame(1440, CronometroRules::saldoAtual($updated, 1030));
    }

    public function testExplicitBalanceBecomesTheNewRunningReferenceAndConclusionFreezesIt(): void
    {
        $saved = CronometroRules::transicionar(
            $this->state(1200, 0, 1000, 900),
            'saldo',
            2000,
            ['tempo_restante_jogo' => 700],
        );
        self::assertSame(700, CronometroRules::saldoAtual($saved, 2000));
        self::assertSame(690, CronometroRules::saldoAtual($saved, 2010));

        $closed = CronometroRules::transicionar($saved, 'concluir', 2010);
        self::assertSame('Concluido', $closed['status_jogo']);
        self::assertSame(690, $closed['tempo_restante_jogo']);
        self::assertNull($closed['data_inicio_real']);
        self::assertSame(690, CronometroRules::saldoAtual($closed, 5000));
    }

    public function testFutureReferenceDoesNotIncreaseBalanceAndImpossibleValuesAreRejected(): void
    {
        $state = $this->state(1200, 0, 1000, 2000);
        self::assertSame(1000, CronometroRules::saldoAtual($state, 1000));

        $this->expectException(InvalidArgumentException::class);
        CronometroRules::transicionar($state, 'acrescentar', 2000, ['tempo_extra_jogo' => -1]);
    }

    /** @return array<string, int|string|null> */
    private function state(
        int $duration,
        int $extra,
        ?int $remaining,
        ?int $reference,
        string $status = 'Iniciado',
    ): array {
        return [
            'status_jogo' => $status,
            'duracao_jogo' => $duration,
            'tempo_extra_jogo' => $extra,
            'tempo_restante_jogo' => $remaining,
            'data_inicio_real' => $reference,
        ];
    }
}
