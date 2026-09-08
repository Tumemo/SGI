<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\ChaveamentoRules;

final class MysqliArtilheiroQueries
{
    public function __construct(private readonly \mysqli $connection)
    {
    }
    public function resolveGame(object $data): int
    {
        return self::sgi_resolver_jogo_temporario_artilharia($this->connection, $data);
    }

    public function editionOfModality(int $modalityId): ?int
    {
        $statement = $this->connection->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        if ($statement === false) {
            return null;
        }
        $statement->bind_param('i', $modalityId);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return $value === null || $value === false ? null : (int) $value;
    }
    private static function revelarDestaquesPorModalidade($conn, $idInterclasse = \null)
    {
        $where = $idInterclasse ? " AND i.id_interclasse = ?" : " AND i.status_interclasse = '1'";
        $sql = "SELECT \n                m.id_modalidade,\n                m.nome_modalidade,\n                c.id_categoria,\n                c.nome_categoria,\n                u.id_usuario,\n                u.nome_usuario,\n                u.foto_usuario,\n                t.id_turma,\n                t.nome_turma,\n                t.nome_fantasia_turma,\n                SUM(a.num_gol) AS total_gols\n            FROM artilheiros a\n            INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario\n            INNER JOIN turmas t ON u.turmas_id_turma = t.id_turma\n            INNER JOIN jogos j ON a.jogos_id_jogo = j.id_jogo\n            INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade\n            INNER JOIN categorias c ON m.categorias_id_categoria = c.id_categoria\n            INNER JOIN interclasses i ON c.interclasses_id_interclasse = i.id_interclasse\n            WHERE 1=1" . $where . "\n            GROUP BY m.id_modalidade, u.id_usuario\n            HAVING total_gols = (\n                SELECT MAX(sub_total)\n                FROM (\n                    SELECT SUM(a2.num_gol) AS sub_total\n                    FROM artilheiros a2\n                    INNER JOIN jogos j2 ON a2.jogos_id_jogo = j2.id_jogo\n                    INNER JOIN modalidades m2 ON j2.modalidades_id_modalidade = m2.id_modalidade\n                    WHERE m2.id_modalidade = m.id_modalidade\n                    GROUP BY a2.usuarios_id_usuario\n                ) AS sub\n            )\n            ORDER BY c.nome_categoria ASC, m.nome_modalidade ASC, total_gols DESC";
        $stmt = $conn->prepare($sql);
        if ($idInterclasse) {
            $stmt->bind_param("i", $idInterclasse);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(\MYSQLI_ASSOC);
    }
    private static function revelarDestaque($conn)
    {
        $sql = "SELECT \n                c.id_categoria,\n                c.nome_categoria,\n                u.id_usuario,\n                u.nome_usuario,\n                u.foto_usuario,\n                t.nome_turma,\n                t.nome_fantasia_turma,\n                SUM(a.num_gol) AS total_gols\n            FROM artilheiros a\n            INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario\n            INNER JOIN turmas t ON u.turmas_id_turma = t.id_turma\n            INNER JOIN jogos j ON a.jogos_id_jogo = j.id_jogo\n            INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade\n            INNER JOIN categorias c ON m.categorias_id_categoria = c.id_categoria\n            INNER JOIN interclasses i ON c.interclasses_id_interclasse = i.id_interclasse\n            WHERE i.status_interclasse = '1'\n            GROUP BY c.id_categoria, u.id_usuario\n            HAVING total_gols = (\n                SELECT MAX(sub_total)\n                FROM (\n                    SELECT SUM(a2.num_gol) AS sub_total\n                    FROM artilheiros a2\n                    INNER JOIN jogos j2 ON a2.jogos_id_jogo = j2.id_jogo\n                    INNER JOIN modalidades m2 ON j2.modalidades_id_modalidade = m2.id_modalidade\n                    WHERE m2.categorias_id_categoria = c.id_categoria\n                    GROUP BY a2.usuarios_id_usuario\n                ) AS sub\n            )\n            ORDER BY c.nome_categoria ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(\MYSQLI_ASSOC);
    }
    private static function sgi_resolver_jogo_temporario_artilharia(\mysqli $conn, object $data): int
    {
        $idJogo = (int) ($data->jogos_id_jogo ?? 0);
        if ($idJogo >= 0) {
            return $idJogo;
        }
        $nomeJogo = \trim((string) ($data->nome_jogo ?? ''));
        $idModalidade = (int) ($data->id_modalidade ?? 0);
        if ($nomeJogo === '' || $idModalidade <= 0) {
            return 0;
        }
        $queries = new self($conn);
        if ($queries->editionOfModality($idModalidade) === null || ChaveamentoRules::parse($nomeJogo) === null) {
            return 0;
        }
        $jogo = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, $nomeJogo);
        return $jogo ? (int) $jogo['id_jogo'] : 0;
    }
    public function list(array $filters): array
    {
        $conn = $this->connection;
        // Verifica se a requisição é específica para listar os destaques por modalidade
        if (isset($filters['acao']) && $filters['acao'] === 'destaques_modalidades') {
            $idInterclasse = !empty($filters['id_interclasse']) ? \intval($filters['id_interclasse']) : \null;
            $destaques = self::revelarDestaquesPorModalidade($conn, $idInterclasse);
            return ["success" => \true, "data" => $destaques];
        }
        // Verifica se a requisição é específica para revelar o destaque
        if (isset($filters['acao']) && $filters['acao'] === 'destaques') {
            $destaques = self::revelarDestaque($conn);
            return ["success" => \true, "data" => $destaques];
        }
        // Fluxo normal da artilharia
        $filtro = \App\Shared\Database\SqlFilters::aplicarFiltrosArtilharia($filters);
        $sql = "SELECT \n                    usuarios.id_usuario,\n                    usuarios.nome_usuario, \n                    usuarios.foto_usuario,\n                    SUM(artilheiros.num_gol) AS total_gols, \n                    modalidades.nome_modalidade,\n                    turmas.nome_turma,\n                    turmas.nome_fantasia_turma\n                FROM artilheiros\n                INNER JOIN usuarios ON artilheiros.usuarios_id_usuario = usuarios.id_usuario\n                INNER JOIN turmas ON usuarios.turmas_id_turma = turmas.id_turma\n                INNER JOIN jogos ON artilheiros.jogos_id_jogo = jogos.id_jogo\n                INNER JOIN modalidades ON jogos.modalidades_id_modalidade = modalidades.id_modalidade\n                INNER JOIN categorias ON modalidades.categorias_id_categoria = categorias.id_categoria\n                WHERE 1=1" . $filtro['sql'];
        $sql .= " GROUP BY usuarios.id_usuario, modalidades.id_modalidade \n                  ORDER BY total_gols DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $artilharia = $res->fetch_all(\MYSQLI_ASSOC);
        return $artilharia;
    }
}
