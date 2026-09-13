<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use mysqli;

final class MysqliEquipeGateway
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @param array<string, mixed> $filters
     * @return array<mixed>
     */
    public function list(array $filters): array
    {
        if (!empty($filters['id_equipe']) && empty($filters['id_turma'])) {
            $id_equipe = intval($filters['id_equipe']);
            $sql = "SELECT u.id_usuario, u.nome_usuario, u.matricula_usuario
                    FROM usuarios u
                    INNER JOIN equipes_has_usuarios eu ON eu.usuarios_id_usuario = u.id_usuario
                    INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe
                    INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
                    WHERE eu.equipes_id_equipe = ? AND u.status_usuario = '1'";
            $types = 'i';
            $params = [$id_equipe];
            if ((int) ($filters['id_interclasse'] ?? 0) > 0) {
                $sql .= ' AND t.interclasses_id_interclasse = ?';
                $types .= 'i';
                $params[] = (int) $filters['id_interclasse'];
            }
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res->fetch_all(MYSQLI_ASSOC);
        }
        $filtro = \App\Shared\Database\SqlFilters::aplicarFiltrosEquipes($filters);
        $sql = "SELECT\n                    equipes.id_equipe,\n                    equipes.nome_equipe,\n                    equipes.status_equipe,\n                    equipes.modalidades_id_modalidade,\n                    equipes.turmas_id_turma,\n                    modalidades.nome_modalidade,\n                    modalidades.max_inscrito_modalidade AS limite_maximo,\n                    turmas.nome_turma,\n                    interclasses.nome_interclasse,\n                    (SELECT COUNT(*) FROM equipes_has_usuarios eu WHERE eu.equipes_id_equipe = equipes.id_equipe) AS total_alunos,\n                    (SELECT COUNT(*) FROM equipes_has_usuarios eu2 WHERE eu2.equipes_id_equipe = equipes.id_equipe) AS qtd_membros\n                FROM equipes\n                INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade\n                INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma\n                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse\n                WHERE 1=1" . $filtro['sql'] . "\n                ORDER BY equipes.id_equipe ASC";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return ["success" => false, "message" => "Erro ao preparar consulta: " . $this->connection->error];
        }
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }
        if (!$stmt->execute()) {
            return ["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error];
        }
        $res = $stmt->get_result();
        if (!$res) {
            return ["success" => false, "message" => "Erro ao obter resultados."];
        }
        $equipes = $res->fetch_all(MYSQLI_ASSOC);
        // RF05/RF03: expõe total de inscritos, limite da modalidade e a flag excedeu_limite.
        foreach ($equipes as &$equipe) {
            $total = (int) ($equipe['total_alunos'] ?? 0);
            $limite = (int) ($equipe['limite_maximo'] ?? 0);
            $equipe['total_alunos'] = $total;
            $equipe['limite_maximo'] = $limite;
            $equipe['excedeu_limite'] = $limite > 0 && $total > $limite;
        }
        unset($equipe);
        return $equipes;
    }

    /** @return array<string, mixed> */
    public function redistribute(int $modality, int $class): array
    {
        return MysqliEquipePadraoRepository::redistribuirEquipe($this->connection, $modality, $class);
    }

    /** @return array<string, mixed> */
    public function generate(int $edition): array
    {
        return MysqliEquipePadraoRepository::gerarEquipesPadraoInterclasse($this->connection, $edition);
    }
}
