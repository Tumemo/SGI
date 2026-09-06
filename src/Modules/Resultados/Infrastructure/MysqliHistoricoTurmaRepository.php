<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

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
            throw new RuntimeException('Turma não encontrada nesta edição.');
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
        $donationPoints = 0;
        $donationRows = [];
        foreach ($donations as $row) {
            $donationItems += (float) $row['quantidade'];
            $donationPoints += (int) $row['pontos_adicionados'];
            $donationRows[] = [
                'id' => (int) $row['id_historico'],
                'quantidade' => (float) $row['quantidade'],
                'pontos' => (int) $row['pontos_adicionados'],
                'data' => $row['data_registro'],
                'registrado_por' => $row['registrado_por_nome'] ?? 'Sistema',
            ];
        }
        $penalties = [];
        $penaltyPoints = 0;
        foreach ($this->all(
            'SELECT titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia, pontos_descontados
             FROM ocorrencias_turmas WHERE turmas_id_turma = ? ORDER BY data_ocorrencia DESC, id_ocorrencia_turma DESC',
            'i',
            [$classId],
        ) as $row) {
            $points = (int) $row['pontos_descontados'];
            $penaltyPoints += $points;
            $penalties[] = ['tipo' => 'turma', 'titulo' => $row['titulo_ocorrencia'], 'descricao' => $row['descricao_ocorrencia'], 'data' => $row['data_ocorrencia'], 'aluno' => null, 'pontos' => $points];
        }
        foreach ($this->all(
            "SELECT o.titulo_ocorrencia, o.descricao_ocorrencia, o.data_ocorrencia, o.penalidade, u.nome_usuario
             FROM ocorrencias o INNER JOIN usuarios u ON u.id_usuario = o.usuarios_id_usuario
             WHERE u.turmas_id_turma = ? AND o.status_ocorrencia = '1'
             ORDER BY o.data_ocorrencia DESC, o.id_ocorrencia DESC",
            'i',
            [$classId],
        ) as $row) {
            $points = (int) $row['penalidade'];
            $penaltyPoints += $points;
            $penalties[] = ['tipo' => 'aluno', 'titulo' => $row['titulo_ocorrencia'], 'descricao' => $row['descricao_ocorrencia'], 'data' => $row['data_ocorrencia'], 'aluno' => $row['nome_usuario'], 'pontos' => $points];
        }
        $sportsPoints = max(0, (int) $class['pontuacao_turma'] - $donationPoints + $penaltyPoints);

        return [
            'success' => true,
            'turma' => [
                'id_turma' => (int) $class['id_turma'],
                'nome_turma' => $class['nome_turma'],
                'nome_fantasia_turma' => $class['nome_fantasia_turma'],
                'turno_turma' => $class['turno_turma'],
                'nome_categoria' => $class['nome_categoria'],
                'pontuacao_turma' => (int) $class['pontuacao_turma'],
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
            'esportes' => ['pontos_total' => $sportsPoints, 'modalidades' => []],
            'penalidades' => ['pontos_total' => $penaltyPoints, 'ocorrencias' => $penalties],
            'resumo' => ['arrecadacao_pontos' => $donationPoints, 'esportes_pontos' => $sportsPoints, 'penalidades_pontos' => $penaltyPoints],
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
