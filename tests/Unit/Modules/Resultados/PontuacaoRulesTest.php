<?php

declare(strict_types=1);

namespace SGITests\Unit\Modules\Resultados;

use App\Modules\Resultados\Domain\PontuacaoRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PontuacaoRulesTest extends TestCase
{
    public function testCalculaDoacaoComValorInteiro(): void
    {
        self::assertSame(20, PontuacaoRules::doacao('10', 2));
        self::assertSame(30, PontuacaoRules::doacao('10.00', 3));
    }

    public function testRevalorizaSemApagarEsporteOuAjuste(): void
    {
        self::assertSame(40, PontuacaoRules::revalorizar(30, '10', 2, 3));
        self::assertSame(45, PontuacaoRules::revalorizar(35, '10', 2, 3));
        self::assertSame(30, PontuacaoRules::revalorizar(40, '10', 3, 2));
    }

    public function testQuantidadeZeroPreservaOBruto(): void
    {
        self::assertSame(17, PontuacaoRules::revalorizar(17, '0', 2, 99));
    }

    public function testFracaoUsaArredondamentoInteiroUnico(): void
    {
        self::assertSame(3, PontuacaoRules::doacao('1.25', 2));
        self::assertSame(2, PontuacaoRules::doacao('1.24', 2));
        self::assertSame(6, PontuacaoRules::revalorizar(5, '1.25', 2, 3));
    }

    public function testFechaAComposicaoDoHistoricoSemMisturarPenalidadeNoEsporte(): void
    {
        $casos = [
            [80, 80, 0, 10, 0, 70],
            [40, 30, 10, 3, 0, 37],
            [45, 30, 10, 3, 5, 42],
            [17, 0, 17, 0, 0, 17],
        ];
        foreach ($casos as [$bruto, $doacao, $esportes, $penalidades, $ajuste, $liquido]) {
            self::assertSame($ajuste, PontuacaoRules::ajuste($bruto, $doacao, $esportes));
            self::assertSame($liquido, PontuacaoRules::liquido($bruto, $penalidades));
        }
    }

    public function testRejeitaQuantidadeComMaisDeDuasCasasOuValorNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PontuacaoRules::doacao('1.001', 2);
    }
}
