<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use App\Modules\Resultados\Application\TurmaHistoricoNaoEncontradaException;
use App\Modules\Resultados\Domain\PontuacaoRules;
use RuntimeException;
use mysqli;

final class MysqliHistoricoTurmaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @return array<string, mixed> */
    public function find(int $classId, int $editionId): array
    {
        $class = $this->one(
            'SELECT t.*, c.nome_categoria
             FROM turmas t INNER JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
             WHERE t.id_turma = ? AND t.interclasses_id_interclasse = ? LIMIT 1',
            'ii',
            [$classId, $editionId],
        );
        if ($class === null) {
            throw new TurmaHistoricoNaoEncontradaException('Turma não encontrada nesta edição.');
        }
        $edition = $this->one('SELECT * FROM interclasses WHERE id_interclasse = ? LIMIT 1', 'i', [$editionId]) ?? [];
        $donations = $this->all(
            "SELECT h.*, u.nome_usuario AS registrado_por_nome
             FROM historico_arrecadacoes h LEFT JOIN usuarios u ON u.id_usuario = h.registrado_por
             WHERE h.id_turma = ? AND h.id_interclasse = ? AND h.status_historico = '1'
             ORDER BY h.data_registro DESC, h.id_historico DESC",
            'ii',
            [$classId, $editionId],
        );
        $donationItems = 0.0;
        $donationRows = [];
        foreach ($donations as $row) {
            $donationItems += (float) $row['quantidade'];
            $donationRows[] = [
                'id' => (int) $row['id_historico'],
                'quantidade' => (float) $row['quantidade'],
                'pontos' => (int) $row['pontos_adicionados'],
                'data' => $row['data_registro'],
                'registrado_por' => $row['registrado_por_nome'] ?? 'Sistema',
            ];
        }
        $donationPoints = PontuacaoRules::doacao((string) $donationItems, (int) ($edition['valor_item_arrecadacao'] ?? 0));
        $penalties = [];
        $penaltyPoints = 0;
        foreach ($this->all(
            'SELECT titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia, pontos_descontados
             FROM ocorrencias_turmas
             WHERE turmas_id_turma = ? AND interclasses_id_interclasse = ?
             ORDER BY data_ocorrencia DESC, id_ocorrencia_turma DESC',
            'ii',
            [$classId, $editionId],
        ) as $row) {
            $points = (int) $row['pontos_descontados'];
            $penaltyPoints += $points;
            $penalties[] = ['tipo' => 'turma', 'titulo' => $row['titulo_ocorrencia'], 'descricao' => $row['descricao_ocorrencia'], 'data' => $row['data_ocorrencia'], 'aluno' => null, 'pontos' => $points];
        }
        foreach ($this->all(
            "SELECT o.titulo_ocorrencia, o.descricao_ocorrencia, o.data_ocorrencia, o.penalidade, u.nome_usuario
             FROM ocorrencias o INNER JOIN usuarios u ON u.id_usuario = o.usuarios_id_usuario
             WHERE u.turmas_id_turma = ? AND u.interclasses_id_interclasse = ? AND o.status_ocorrencia = '1'
             ORDER BY o.data_ocorrencia DESC, o.id_ocorrencia DESC",
            'ii',
            [$classId, $editionId],
        ) as $row) {
            $points = (int) $row['penalidade'];
            $penaltyPoints += $points;
            $penalties[] = ['tipo' => 'aluno', 'titulo' => $row['titulo_ocorrencia'], 'descricao' => $row['descricao_ocorrencia'], 'data' => $row['data_ocorrencia'], 'aluno' => $row['nome_usuario'], 'pontos' => $points];
        }
        $credits = $this->all(
            'SELECT p.id_modalidade, p.posicao, p.pontos, p.id_equipe, p.id_usuario, p.origem_registro,
                    m.nome_modalidade, tm.nome_tipo_modalidade, c.nome_categoria,
                    u.nome_usuario, e.nome_equipe
             FROM pontuacoes_podio p
             INNER JOIN modalidades m ON m.id_modalidade = p.id_modalidade
             INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
             INNER JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
             LEFT JOIN usuarios u ON u.id_usuario = p.id_usuario
             LEFT JOIN equipes e ON e.id_equipe = p.id_equipe
             WHERE p.id_interclasse = ? AND p.id_turma = ? AND p.ativo = 1
             ORDER BY p.id_modalidade, p.posicao, p.id_pontuacao',
            'ii',
            [$editionId, $classId],
        );
        $sportsPoints = 0;
        $modalities = [];
        foreach ($credits as $credit) {
            $modalityId = (int) $credit['id_modalidade'];
            if (!isset($modalities[$modalityId])) {
                $modalities[$modalityId] = [
                    'id_modalidade' => $modalityId,
                    'nome_modalidade' => $credit['nome_modalidade'],
                    'tipo' => $credit['nome_tipo_modalidade'],
                    'nome_categoria' => $credit['nome_categoria'],
                    'colocacao' => null,
                    'pontos' => 0,
                    'alunos' => [],
                    'itens' => [],
                    'fontes' => [],
                ];
            }
            $position = (int) $credit['posicao'];
            $points = (int) $credit['pontos'];
            $sportsPoints += $points;
            $modalities[$modalityId]['pontos'] += $points;
            $source = [
                'posicao' => $position,
                'pontos' => $points,
                'origem_registro' => (string) $credit['origem_registro'],
                'id_equipe' => $credit['id_equipe'] === null ? null : (int) $credit['id_equipe'],
                'id_usuario' => $credit['id_usuario'] === null ? null : (int) $credit['id_usuario'],
            ];
            $modalities[$modalityId]['fontes'][] = $source;
            if ($credit['nome_usuario'] !== null) {
                $modalities[$modalityId]['alunos'][] = ['nome_usuario' => $credit['nome_usuario'], 'posicao' => $position];
            }
            $modalities[$modalityId]['itens'][] = [
                'descricao' => $position . 'º lugar',
                'detalhe' => $credit['nome_usuario'] ?? $credit['nome_equipe'] ?? 'Crédito de pódio',
                'pontos' => $points,
            ];
        }
        $bruto = (int) $class['pontuacao_turma'];
        $adjustment = $bruto - $donationPoints - $sportsPoints;
        $liquid = $bruto - $penaltyPoints;
        $adjustmentInfo = [
            'pontos' => $adjustment,
            'origem' => 'Saldo sem origem detalhada',
            'pendente_origem' => $adjustment !== 0,
        ];

        return [
            'success' => true,
            'turma' => [
                'id_turma' => (int) $class['id_turma'],
                'nome_turma' => $class['nome_turma'],
                'nome_fantasia_turma' => $class['nome_fantasia_turma'],
                'turno_turma' => $class['turno_turma'],
                'nome_categoria' => $class['nome_categoria'],
                'pontuacao_turma' => $bruto,
                'pontuacao_bruta' => $bruto,
                'pontuacao_liquida' => $liquid,
                'ajuste_pontuacao' => $adjustment,
                'qtd_itens_arrecadados' => (float) $class['qtd_itens_arrecadados'],
            ],
            'interclasse' => [
                'id_interclasse' => (int) ($edition['id_interclasse'] ?? $editionId),
                'nome_interclasse' => $edition['nome_interclasse'] ?? '',
                'ponto_1_lugar' => (int) ($edition['ponto_1_lugar'] ?? 0),
                'ponto_2_lugar' => (int) ($edition['ponto_2_lugar'] ?? 0),
                'ponto_3_lugar' => (int) ($edition['ponto_3_lugar'] ?? 0),
                'valor_item_arrecadacao' => (int) ($edition['valor_item_arrecadacao'] ?? 0),
            ],
            'arrecadacao' => ['itens' => round($donationItems, 2), 'pontos' => $donationPoints, 'registros' => $donationRows],
            'esportes' => ['pontos_total' => $sportsPoints, 'modalidades' => array_values($modalities)],
            'penalidades' => ['pontos_total' => $penaltyPoints, 'ocorrencias' => $penalties],
            'ajuste' => $adjustmentInfo,
            'resumo' => [
                'arrecadacao_pontos' => $donationPoints,
                'esportes_pontos' => $sportsPoints,
                'ajuste_pontos' => $adjustment,
                'penalidades_pontos' => $penaltyPoints,
                'bruto' => $bruto,
                'liquido' => $liquid,
            ],
        ];
    }

    public function studentBelongsToClass(int $userId, int $classId, int $editionId): bool
    {
        return $this->one(
            'SELECT 1 FROM usuarios WHERE id_usuario = ? AND turmas_id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1',
            'iii',
            [$userId, $classId, $editionId],
        ) !== null;
    }

    /** @param list<int> $params @return list<array<string, mixed>> */
    private function all(string $sql, string $types, array $params): array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    /** @param list<int> $params @return array<string, mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $rows = $this->all($sql, $types, $params);
        return $rows[0] ?? null;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar histórico da turma.');
        }
        return $statement;
    }
}
