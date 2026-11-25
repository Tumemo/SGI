<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Application;

use App\Modules\Resultados\Domain\RelatorioFinalRepository;

final class RelatorioFinalService
{
    public function __construct(private readonly RelatorioFinalRepository $repository)
    {
    }

    /** @return array<string, mixed> */
    public function obter(int $editionId): array
    {
        if ($editionId <= 0) {
            throw new \InvalidArgumentException('Selecione um interclasse válido.');
        }

        $data = $this->repository->loadEdition($editionId);
        if ($data === null) {
            throw new RelatorioFinalNaoEncontradoException('Interclasse não encontrado.');
        }

        $classes = $data['turmas'] ?? [];
        $penaltiesByClass = [];
        foreach ($data['penalidades'] ?? [] as $penalty) {
            $classId = (int) ($penalty['id_turma'] ?? 0);
            if ($classId <= 0) {
                continue;
            }
            $penaltiesByClass[$classId][] = [
                'tipo' => (string) ($penalty['tipo'] ?? 'turma'),
                'titulo' => (string) ($penalty['titulo'] ?? 'Ocorrência'),
                'descricao' => (string) ($penalty['descricao'] ?? ''),
                'data' => (string) ($penalty['data'] ?? ''),
                'aluno' => isset($penalty['aluno']) ? (string) $penalty['aluno'] : null,
                'pontos' => max(0, (int) ($penalty['pontos'] ?? 0)),
            ];
        }

        $ranking = [];
        foreach ($classes as $class) {
            $classId = (int) $class['id_turma'];
            $penalties = $penaltiesByClass[$classId] ?? [];
            $penaltyPoints = array_sum(array_column($penalties, 'pontos'));
            $grossPoints = (int) $class['pontuacao_bruta'];
            $ranking[] = [
                'id_turma' => $classId,
                'nome_turma' => (string) $class['nome_turma'],
                'nome_fantasia_turma' => (string) ($class['nome_fantasia_turma'] ?? ''),
                'nome_categoria' => (string) $class['nome_categoria'],
                'turno_turma' => (string) ($class['turno_turma'] ?? ''),
                'doacoes_kg' => (float) $class['doacoes_kg'],
                'pontuacao_bruta' => $grossPoints,
                'pontuacao_esportes' => (int) ($class['pontuacao_esportes'] ?? 0),
                'penalidades_pontos' => $penaltyPoints,
                'pontuacao_liquida' => $grossPoints - $penaltyPoints,
                'penalidades' => $penalties,
            ];
        }

        self::orderAndRank($ranking);
        $rankingByCategory = [];
        foreach ($ranking as $entry) {
            $rankingByCategory[$entry['nome_categoria']][] = $entry;
        }

        $categories = [];
        foreach ($data['categorias'] ?? [] as $category) {
            $name = (string) $category['nome_categoria'];
            $categoryRanking = $rankingByCategory[$name] ?? [];
            self::orderAndRank($categoryRanking);
            $categories[] = [
                'id_categoria' => (int) $category['id_categoria'],
                'nome_categoria' => $name,
                'total_turmas' => count($categoryRanking),
                'pontuacao_total' => array_sum(array_column($categoryRanking, 'pontuacao_liquida')),
                'lider' => $categoryRanking[0] ?? null,
                'ranking' => $categoryRanking,
            ];
        }

        $donations = array_map(static fn (array $entry): array => [
            'id_turma' => $entry['id_turma'],
            'nome_turma' => $entry['nome_turma'],
            'nome_categoria' => $entry['nome_categoria'],
            'quantidade_kg' => $entry['doacoes_kg'],
            'pontuacao' => (int) round($entry['doacoes_kg'] * (int) ($data['edicao']['valor_item_arrecadacao'] ?? 0)),
        ], $ranking);
        usort($donations, static fn (array $a, array $b): int => ($b['quantidade_kg'] <=> $a['quantidade_kg']) ?: strcmp($a['nome_turma'], $b['nome_turma']));

        $modalities = [];
        foreach ($data['modalidades'] ?? [] as $modality) {
            $modalityId = (int) $modality['id_modalidade'];
            $podiums = array_values(array_filter(
                $data['podios'] ?? [],
                static fn (array $podium): bool => (int) $podium['id_modalidade'] === $modalityId,
            ));
            $scorers = [];
            if ((bool) $modality['contabiliza_artilharia']) {
                $scorers = array_values(array_filter(
                    $data['artilheiros'] ?? [],
                    static fn (array $scorer): bool => (int) $scorer['id_modalidade'] === $modalityId,
                ));
                self::orderAndRankScorers($scorers);
            }
            $modalities[] = [
                'id_modalidade' => $modalityId,
                'nome_modalidade' => (string) $modality['nome_modalidade'],
                'nome_categoria' => (string) $modality['nome_categoria'],
                'nome_tipo_modalidade' => (string) $modality['nome_tipo_modalidade'],
                'genero_modalidade' => (string) $modality['genero_modalidade'],
                'contabiliza_artilharia' => (bool) $modality['contabiliza_artilharia'],
                'status_modalidade' => (string) $modality['status_modalidade'],
                'podios' => $podiums,
                'artilheiros' => $scorers,
            ];
        }

        $totalDonations = array_sum(array_column($donations, 'quantidade_kg'));
        $metrics = [
            'turmas_total' => count($ranking),
            'categorias_total' => count($categories),
            'turmas_por_categoria' => array_map(static fn (array $category): array => [
                'nome_categoria' => $category['nome_categoria'],
                'total_turmas' => $category['total_turmas'],
            ], $categories),
            'doacoes_total_kg' => (float) $totalDonations,
            'lider_doacoes' => $donations[0] ?? null,
            'modalidades_total' => count($modalities),
            'lider_absoluta' => $ranking[0] ?? null,
        ];

        return [
            'edicao' => $data['edicao'],
            'metricas' => $metrics,
            'ranking_geral' => $ranking,
            'categorias' => $categories,
            'modalidades' => $modalities,
            'doacoes' => [
                'total_kg' => (float) $totalDonations,
                'por_turma' => $donations,
            ],
            'observacao' => 'Classificação líquida calculada pela pontuação registrada da turma menos as penalidades ativas. As doações usam os totais consolidados da edição.',
            'gerado_em' => gmdate('c'),
        ];
    }

    /** @param list<array<string, mixed>> $entries */
    private static function orderAndRank(array &$entries): void
    {
        usort($entries, static fn (array $a, array $b): int => ($b['pontuacao_liquida'] <=> $a['pontuacao_liquida']) ?: strcmp($a['nome_turma'], $b['nome_turma']));
        $rank = 0;
        $previousScore = null;
        foreach ($entries as $index => &$entry) {
            $score = (int) $entry['pontuacao_liquida'];
            if ($previousScore === null || $score !== $previousScore) {
                $rank = $index + 1;
                $previousScore = $score;
            }
            $entry['posicao'] = $rank;
        }
        unset($entry);
    }

    /** @param list<array<string, mixed>> $entries */
    private static function orderAndRankScorers(array &$entries): void
    {
        usort($entries, static fn (array $a, array $b): int => ($b['gols'] <=> $a['gols']) ?: strcmp($a['nome_usuario'], $b['nome_usuario']));
        $rank = 0;
        $previousGoals = null;
        foreach ($entries as $index => &$entry) {
            $goals = (int) $entry['gols'];
            if ($previousGoals === null || $goals !== $previousGoals) {
                $rank = $index + 1;
                $previousGoals = $goals;
            }
            $entry['posicao'] = $rank;
        }
        unset($entry);
    }
}
