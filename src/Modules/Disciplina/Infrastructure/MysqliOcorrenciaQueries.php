<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Infrastructure;

use App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository;
use App\Modules\Competicoes\Domain\ChaveamentoRules;
use mysqli;

final class MysqliOcorrenciaQueries
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @param array<string, mixed> $filters
     * @return array<mixed>
     */
    public function list(array $filters): array
    {
        if (!empty($filters['acao']) && $filters['acao'] === 'listar_atletas') {
            $idJogo = isset($filters['id_jogo']) ? intval($filters['id_jogo']) : 0;
            $idTurma = isset($filters['id_turma']) ? intval($filters['id_turma']) : 0;
            if ($idTurma <= 0) {
                throw new \InvalidArgumentException('id_turma é obrigatório.');
            }
            if ($idJogo <= 0) {
                $sql = "SELECT DISTINCT u.id_usuario, u.nome_usuario, u.matricula_usuario\n                        FROM usuarios u\n                        INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario\n                        INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe\n                        WHERE e.turmas_id_turma = ?\n                          AND u.status_usuario = '1' AND u.nivel_usuario = '3'\n                          AND u.id_usuario NOT IN (\n                            SELECT o2.usuarios_id_usuario\n                            FROM ocorrencias o2\n                            WHERE o2.titulo_ocorrencia = 'Suspensao'\n                              AND o2.status_ocorrencia = '1'\n                          )\n                        ORDER BY u.nome_usuario ASC";
                $stmt = $this->connection->prepare($sql);
                $stmt->bind_param("i", $idTurma);
                $stmt->execute();
                $res = $stmt->get_result();
                return ["success" => true, "atletas" => $res->fetch_all(MYSQLI_ASSOC)];
            }
            $likeJogo = '%[JOGO:' . $idJogo . ']%';
            $sql = "SELECT DISTINCT u.id_usuario, u.nome_usuario, u.matricula_usuario\n                    FROM usuarios u\n                    INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario\n                    INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe\n                    INNER JOIN partidas p ON p.equipes_id_equipe = e.id_equipe\n                    WHERE p.jogos_id_jogo = ? AND e.turmas_id_turma = ?\n                      AND u.status_usuario = '1' AND u.nivel_usuario = '3'\n                      AND u.id_usuario NOT IN (\n                        SELECT o2.usuarios_id_usuario\n                        FROM ocorrencias o2\n                        WHERE o2.titulo_ocorrencia = 'Suspensao'\n                          AND o2.status_ocorrencia = '1'\n                      )\n                      AND u.id_usuario NOT IN (\n                        SELECT o3.usuarios_id_usuario\n                        FROM ocorrencias o3\n                        WHERE o3.titulo_ocorrencia = 'Vermelho'\n                          AND o3.descricao_ocorrencia LIKE ?\n                          AND o3.status_ocorrencia = '1'\n                      )\n                    ORDER BY u.nome_usuario ASC";
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("iis", $idJogo, $idTurma, $likeJogo);
            $stmt->execute();
            $res = $stmt->get_result();
            return ["success" => true, "atletas" => $res->fetch_all(MYSQLI_ASSOC)];
        }
        $filtro = \App\Shared\Database\SqlFilters::aplicarFiltrosOcorrencias($filters);
        $sql = "SELECT \n                    ocorrencias.id_ocorrencia, \n                    ocorrencias.titulo_ocorrencia, \n                    ocorrencias.descricao_ocorrencia, \n                    ocorrencias.data_ocorrencia, \n                    ocorrencias.hora_ocorrencia, \n                    ocorrencias.penalidade,\n                    ocorrencias.status_ocorrencia,\n                    usuarios.nome_usuario,\n                    usuarios.id_usuario,\n                    usuarios.turmas_id_turma\n                FROM ocorrencias \n                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario \n                WHERE 1=1" . $filtro['sql'];
        if (!empty($filters['id_jogo'])) {
            $buscaJogo = '%[JOGO:' . intval($filters['id_jogo']) . ']%';
            $sql .= " AND ocorrencias.descricao_ocorrencia LIKE ?";
            if (!empty($filtro['params'])) {
                $filtro['types'] .= 's';
                $filtro['params'][] = $buscaJogo;
            } else {
                $filtro['types'] = 's';
                $filtro['params'] = [$buscaJogo];
            }
        }
        $sql .= " ORDER BY ocorrencias.data_ocorrencia DESC, ocorrencias.hora_ocorrencia DESC";
        $stmt = $this->connection->prepare($sql);
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function resolveGame(int $id, string $tag, int $modality): int
    {
        if ($id >= 0) {
            return $id;
        }
        if (trim($tag) === '' || $modality <= 0) {
            return 0;
        }
        if ($this->editionOfModality($modality) === null || ChaveamentoRules::parse(trim($tag)) === null) {
            return 0;
        }
        $game = MysqliChaveamentoRepository::buscarJogoPorTag($this->connection, $modality, trim($tag));
        return (int) ($game['id_jogo'] ?? 0);
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

    /** @return array{gameId:int,classId:int} */
    public function referencesFromDescription(string $description): array
    {
        $gameId = 0;
        $classId = 0;
        if (preg_match('/\[JOGO:(\d+)\]/', $description, $gameMatch) === 1) {
            $gameId = (int) $gameMatch[1];
        }
        if (preg_match('/\[TURMA:(\d+)\]/', $description, $classMatch) === 1) {
            $classId = (int) $classMatch[1];
        }
        return ['gameId' => $gameId, 'classId' => $classId];
    }
}
