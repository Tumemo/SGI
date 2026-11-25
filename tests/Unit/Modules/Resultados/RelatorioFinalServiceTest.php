<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Resultados;

use App\Modules\Resultados\Application\RelatorioFinalNaoEncontradoException;
use App\Modules\Resultados\Application\RelatorioFinalService;
use App\Modules\Resultados\Domain\RelatorioFinalRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RelatorioFinalServiceTest extends TestCase
{
    public function testBuildsEditionScopedSummaryAndSkipsScorersWhenArtilleryIsDisabled(): void
    {
        $service = new RelatorioFinalService(new InMemoryRelatorioFinalRepository());

        $report = $service->obter(17);

        self::assertSame('Copa Lua', $report['edicao']['nome_interclasse']);
        self::assertSame(2, $report['metricas']['turmas_total']);
        self::assertSame(3.5, $report['doacoes']['total_kg']);
        self::assertSame(45, $report['ranking_geral'][0]['pontuacao_liquida']);
        self::assertSame(1, $report['ranking_geral'][0]['posicao']);
        self::assertSame(5, $report['ranking_geral'][0]['penalidades_pontos']);
        self::assertSame(1, $report['categorias'][0]['ranking'][0]['posicao']);
        self::assertSame(1, $report['modalidades'][0]['artilheiros'][0]['posicao']);
        self::assertSame([], $report['modalidades'][1]['artilheiros']);
        self::assertCount(1, $report['modalidades'][1]['podios']);
    }

    public function testRejectsInvalidEditionId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RelatorioFinalService(new InMemoryRelatorioFinalRepository()))->obter(0);
    }

    public function testRaisesNotFoundForUnknownEdition(): void
    {
        $repository = new InMemoryRelatorioFinalRepository();
        $repository->exists = false;
        $this->expectException(RelatorioFinalNaoEncontradoException::class);
        (new RelatorioFinalService($repository))->obter(999);
    }
}

final class InMemoryRelatorioFinalRepository implements RelatorioFinalRepository
{
    public bool $exists = true;

    public function loadEdition(int $editionId): ?array
    {
        if (!$this->exists || $editionId !== 17) {
            return null;
        }

        return [
            'edicao' => ['nome_interclasse' => 'Copa Lua', 'ano' => '2026', 'valor_item_arrecadacao' => 2],
            'categorias' => [
                ['id_categoria' => 1, 'nome_categoria' => 'Categoria A'],
                ['id_categoria' => 2, 'nome_categoria' => 'Categoria B'],
            ],
            'turmas' => [
                ['id_turma' => 11, 'nome_turma' => 'Turma 1', 'nome_fantasia_turma' => 'Feras', 'nome_categoria' => 'Categoria A', 'turno_turma' => 'manha', 'pontuacao_bruta' => 50, 'doacoes_kg' => 2.5, 'pontuacao_esportes' => 10],
                ['id_turma' => 12, 'nome_turma' => 'Turma 2', 'nome_fantasia_turma' => '', 'nome_categoria' => 'Categoria B', 'turno_turma' => 'tarde', 'pontuacao_bruta' => 30, 'doacoes_kg' => 1, 'pontuacao_esportes' => 5],
            ],
            'modalidades' => [
                ['id_modalidade' => 31, 'nome_modalidade' => 'Futsal', 'nome_categoria' => 'Categoria A', 'nome_tipo_modalidade' => 'Coletiva', 'genero_modalidade' => 'MISTO', 'contabiliza_artilharia' => 1, 'status_modalidade' => '1'],
                ['id_modalidade' => 32, 'nome_modalidade' => 'Queimada', 'nome_categoria' => 'Categoria B', 'nome_tipo_modalidade' => 'Coletiva', 'genero_modalidade' => 'MISTO', 'contabiliza_artilharia' => 0, 'status_modalidade' => '1'],
            ],
            'penalidades' => [
                ['id_turma' => 11, 'tipo' => 'turma', 'titulo' => 'Atraso', 'descricao' => 'Chegada tardia', 'data' => '2026-09-10', 'aluno' => null, 'pontos' => 5],
            ],
            'podios' => [
                ['id_modalidade' => 31, 'posicao' => 1, 'id_turma' => 11, 'pontos' => 10, 'destaque' => 'Atleta A'],
                ['id_modalidade' => 32, 'posicao' => 2, 'id_turma' => 12, 'pontos' => 7, 'destaque' => 'Turma 2'],
            ],
            'artilheiros' => [
                ['id_modalidade' => 31, 'nome_usuario' => 'Atleta A', 'nome_turma' => 'Turma 1', 'gols' => 3],
                ['id_modalidade' => 32, 'nome_usuario' => 'Atleta B', 'nome_turma' => 'Turma 2', 'gols' => 8],
            ],
        ];
    }
}
