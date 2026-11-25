<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use App\Modules\Resultados\Domain\RelatorioFinalRepository;
use mysqli;
use RuntimeException;

final class MysqliRelatorioFinalRepository implements RelatorioFinalRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function loadEdition(int $editionId): ?array
    {
        $edition = $this->one(
            'SELECT id_interclasse, nome_interclasse, ano_interclasse, status_interclasse,
                    ponto_1_lugar, ponto_2_lugar, ponto_3_lugar, valor_item_arrecadacao
             FROM interclasses WHERE id_interclasse = ? LIMIT 1',
            'i',
            [$editionId],
        );
        if ($edition === null) {
            return null;
        }

        return [
            'edicao' => [
                'id_interclasse' => (int) $edition['id_interclasse'],
                'nome_interclasse' => (string) $edition['nome_interclasse'],
                'ano' => substr((string) $edition['ano_interclasse'], 0, 4),
                'status_interclasse' => (string) $edition['status_interclasse'],
                'pontos_1_lugar' => (int) $edition['ponto_1_lugar'],
                'pontos_2_lugar' => (int) $edition['ponto_2_lugar'],
                'pontos_3_lugar' => (int) $edition['ponto_3_lugar'],
                'valor_item_arrecadacao' => (int) $edition['valor_item_arrecadacao'],
            ],
            'categorias' => $this->all(
                'SELECT id_categoria, nome_categoria
                 FROM categorias WHERE interclasses_id_interclasse = ?
                 ORDER BY nome_categoria ASC, id_categoria ASC',
                'i',
                [$editionId],
            ),
            'turmas' => $this->all(
                'SELECT t.id_turma, t.nome_turma, t.nome_fantasia_turma, t.turno_turma,
                        t.pontuacao_turma AS pontuacao_bruta,
                        t.qtd_itens_arrecadados AS doacoes_kg,
                        c.nome_categoria,
                        COALESCE((
                            SELECT SUM(p.pontos) FROM pontuacoes_podio p
                            WHERE p.id_interclasse = t.interclasses_id_interclasse
                              AND p.id_turma = t.id_turma AND p.ativo = 1
                        ), 0) AS pontuacao_esportes
                 FROM turmas t
                 INNER JOIN categorias c
                    ON c.id_categoria = t.categorias_id_categoria
                   AND c.interclasses_id_interclasse = t.interclasses_id_interclasse
                 WHERE t.interclasses_id_interclasse = ?
                 ORDER BY c.nome_categoria ASC, t.nome_turma ASC, t.id_turma ASC',
                'i',
                [$editionId],
            ),
            'modalidades' => $this->all(
                'SELECT m.id_modalidade, m.nome_modalidade, m.genero_modalidade,
                        m.status_modalidade, m.contabiliza_artilharia,
                        c.nome_categoria, tm.nome_tipo_modalidade
                 FROM modalidades m
                 INNER JOIN categorias c
                    ON c.id_categoria = m.categorias_id_categoria
                   AND c.interclasses_id_interclasse = m.interclasses_id_interclasse
                 INNER JOIN tipos_modalidades tm
                    ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
                 WHERE m.interclasses_id_interclasse = ?
                 ORDER BY c.nome_categoria ASC, m.nome_modalidade ASC, m.id_modalidade ASC',
                'i',
                [$editionId],
            ),
            'penalidades' => $this->penalties($editionId),
            'podios' => $this->all(
                'SELECT p.id_modalidade, p.posicao, p.id_turma, p.pontos,
                        p.id_equipe, p.id_usuario, m.nome_modalidade,
                        c.nome_categoria, t.nome_turma,
                        COALESCE(u.nome_usuario, e.nome_equipe, t.nome_turma) AS destaque
                 FROM pontuacoes_podio p
                 INNER JOIN modalidades m
                    ON m.id_modalidade = p.id_modalidade
                   AND m.interclasses_id_interclasse = p.id_interclasse
                 INNER JOIN categorias c
                    ON c.id_categoria = m.categorias_id_categoria
                   AND c.interclasses_id_interclasse = p.id_interclasse
                 INNER JOIN turmas t
                    ON t.id_turma = p.id_turma
                   AND t.interclasses_id_interclasse = p.id_interclasse
                 LEFT JOIN equipes e
                    ON e.id_equipe = p.id_equipe
                   AND e.modalidades_id_modalidade = p.id_modalidade
                   AND e.turmas_id_turma = p.id_turma
                 LEFT JOIN usuarios u
                    ON u.id_usuario = p.id_usuario
                   AND u.interclasses_id_interclasse = p.id_interclasse
                   AND u.turmas_id_turma = p.id_turma
                 WHERE p.id_interclasse = ? AND p.ativo = 1
                 ORDER BY c.nome_categoria ASC, m.nome_modalidade ASC, p.posicao ASC',
                'i',
                [$editionId],
            ),
            'artilheiros' => $this->all(
                "SELECT m.id_modalidade, m.nome_modalidade, c.nome_categoria,
                        u.id_usuario, u.nome_usuario, t.nome_turma,
                        SUM(a.num_gol) AS gols
                 FROM artilheiros a
                 INNER JOIN jogos j ON j.id_jogo = a.jogos_id_jogo
                 INNER JOIN modalidades m
                    ON m.id_modalidade = j.modalidades_id_modalidade
                   AND m.interclasses_id_interclasse = ?
                   AND m.contabiliza_artilharia = 1
                 INNER JOIN categorias c
                    ON c.id_categoria = m.categorias_id_categoria
                   AND c.interclasses_id_interclasse = m.interclasses_id_interclasse
                 INNER JOIN usuarios u
                    ON u.id_usuario = a.usuarios_id_usuario
                   AND u.interclasses_id_interclasse = m.interclasses_id_interclasse
                 INNER JOIN turmas t
                    ON t.id_turma = u.turmas_id_turma
                   AND t.interclasses_id_interclasse = m.interclasses_id_interclasse
                 WHERE a.status_artilheiro = 'ativo' AND a.conta_no_placar = 1
                 GROUP BY m.id_modalidade, m.nome_modalidade, c.nome_categoria,
                          u.id_usuario, u.nome_usuario, t.nome_turma
                 ORDER BY c.nome_categoria ASC, m.nome_modalidade ASC, gols DESC, u.nome_usuario ASC",
                'i',
                [$editionId],
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function penalties(int $editionId): array
    {
        $classPenalties = $this->all(
            "SELECT ot.turmas_id_turma AS id_turma, 'turma' AS tipo,
                    ot.titulo_ocorrencia AS titulo, ot.descricao_ocorrencia AS descricao,
                    ot.data_ocorrencia AS data, NULL AS aluno, ot.pontos_descontados AS pontos
             FROM ocorrencias_turmas ot
             INNER JOIN turmas t ON t.id_turma = ot.turmas_id_turma
             WHERE ot.interclasses_id_interclasse = ?
               AND t.interclasses_id_interclasse = ?
             ORDER BY ot.data_ocorrencia ASC, ot.id_ocorrencia_turma ASC",
            'ii',
            [$editionId, $editionId],
        );
        $studentPenalties = $this->all(
            "SELECT u.turmas_id_turma AS id_turma, 'aluno' AS tipo,
                    o.titulo_ocorrencia AS titulo, o.descricao_ocorrencia AS descricao,
                    o.data_ocorrencia AS data, u.nome_usuario AS aluno,
                    COALESCE(o.penalidade, 0) AS pontos
             FROM ocorrencias o
             INNER JOIN usuarios u ON u.id_usuario = o.usuarios_id_usuario
             INNER JOIN turmas t
                ON t.id_turma = u.turmas_id_turma
               AND t.interclasses_id_interclasse = u.interclasses_id_interclasse
             WHERE u.interclasses_id_interclasse = ? AND t.interclasses_id_interclasse = ?
               AND o.status_ocorrencia = '1'
             ORDER BY o.data_ocorrencia ASC, o.id_ocorrencia ASC",
            'ii',
            [$editionId, $editionId],
        );

        return [...$classPenalties, ...$studentPenalties];
    }

    /** @param list<mixed> $parameters @return array<string, mixed>|null */
    private function one(string $sql, string $types, array $parameters): ?array
    {
        $rows = $this->query($sql, $types, $parameters);
        return $rows[0] ?? null;
    }

    /** @param list<mixed> $parameters @return list<array<string, mixed>> */
    private function all(string $sql, string $types, array $parameters): array
    {
        return $this->query($sql, $types, $parameters);
    }

    /** @param list<mixed> $parameters @return list<array<string, mixed>> */
    private function query(string $sql, string $types, array $parameters): array
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar os dados do relatório final.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$parameters);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar os dados do relatório final.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }
}
