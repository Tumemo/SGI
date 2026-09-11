<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\TipoCompeticaoRules;
use PHPUnit\Framework\TestCase;

final class TipoCompeticaoRulesTest extends TestCase
{
    public function testTipoIndividualComIdDiferenteDeDoisContinuaIndividual(): void
    {
        self::assertSame(TipoCompeticaoRules::INDIVIDUAL, TipoCompeticaoRules::resolve([
            'tipos_modalidades_id_tipo_modalidade' => 37,
            'nome_tipo_modalidade' => 'Individual',
        ]));
    }

    public function testTipoMataMataNaoDependeDoNomeDoEsporte(): void
    {
        self::assertSame(TipoCompeticaoRules::MATA_MATA, TipoCompeticaoRules::resolve([
            'tipos_modalidades_id_tipo_modalidade' => 91,
            'nome_modalidade' => 'Corrida',
            'nome_tipo_modalidade' => 'Mata-Mata',
        ]));
    }

    public function testTipoDesconhecidoNaoCaiSilenciosamenteNoColetivo(): void
    {
        self::assertNull(TipoCompeticaoRules::resolve([
            'tipos_modalidades_id_tipo_modalidade' => 37,
            'nome_tipo_modalidade' => 'Formato futuro',
        ]));
    }

    public function testIdHistoricoSemNomeNaoDefineOFormato(): void
    {
        self::assertNull(TipoCompeticaoRules::resolve([
            'tipos_modalidades_id_tipo_modalidade' => 2,
        ]));
    }
}
