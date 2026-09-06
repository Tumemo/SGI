<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Application\PlacarService;
use App\Modules\Competicoes\Domain\ChaveamentoRules;
use App\Shared\Database\Transaction;
use App\Shared\Database\SqlFilters;
use mysqli;
use RuntimeException;

final class MysqliPartidaGateway
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function list(array $filters): array
    {
        $filter = SqlFilters::aplicarFiltrosPartidas($filters);
        $sql = "SELECT p.id_partida, p.equipes_id_equipe, p.resultado_partida,
                       j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo,
                       j.inicio_jogo, j.termino_jogo,
                       j.modalidades_id_modalidade, t.id_turma, t.nome_turma,
                       t.nome_fantasia_turma, m.nome_modalidade,
                       m.categorias_id_categoria, c.nome_categoria, l.nome_local
                FROM partidas p
                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade
                INNER JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
                LEFT JOIN locais l ON l.id_local = j.locais_id_local
                WHERE 1=1" . $filter['sql'];
        $statement = $this->prepare($sql);
        if ($filter['params'] !== []) {
            $statement->bind_param($filter['types'], ...$filter['params']);
        }
        if (!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            throw new RuntimeException($message !== '' ? $message : 'Não foi possível consultar partidas.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    public function editionOfGame(int $id): ?int
    {
        $row = $this->one('SELECT m.interclasses_id_interclasse AS edition_id FROM jogos j INNER JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade WHERE j.id_jogo = ? LIMIT 1', 'i', [$id]);
        return $row === null ? null : (int) $row['edition_id'];
    }

    public function editionOfPartida(int $id): ?int
    {
        $row = $this->one('SELECT m.interclasses_id_interclasse AS edition_id FROM partidas p INNER JOIN jogos j ON j.id_jogo = p.jogos_id_jogo INNER JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade WHERE p.id_partida = ? LIMIT 1', 'i', [$id]);
        return $row === null ? null : (int) $row['edition_id'];
    }

    /** @param list<array<string, mixed>> $results */
    public function launch(int $gameId, ?string $gameTag, int $modalityId, array $results): array
    {
        Transaction::begin($this->connection);
        try {
            $gameId = $this->resolveGame($gameId, $gameTag, $modalityId, $results);
            if ($gameId <= 0) {
                throw new RuntimeException('Não foi possível identificar o jogo no servidor.');
            }
            $statusRow = $this->one('SELECT status_jogo, nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1', 'i', [$gameId]);
            if ($statusRow === null) {
                throw new RuntimeException('Jogo não encontrado.');
            }
            $closed = in_array((string) $statusRow['status_jogo'], ['Concluido', 'Finalizado'], true);
            $oldWinner = $closed ? ChaveamentoRules::vencedorDePartidas(MysqliChaveamentoRepository::carregarPartidasJogo($this->connection, $gameId)) : null;
            foreach ($results as $result) {
                $teamId = (int) ($result['id_equipe'] ?? 0);
                if ($teamId <= 0) {
                    continue;
                }
                MysqliChaveamentoRepository::garantirPartidaEquipe($this->connection, $gameId, $teamId);
                $statement = $this->prepare('UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?');
                $score = (int) ($result['gols'] ?? 0);
                $statement->bind_param('iii', $score, $gameId, $teamId);
                if (!$statement->execute()) {
                    $statement->close();
                    throw new RuntimeException('Não foi possível registrar o placar.');
                }
                $statement->close();
            }
            $scores = array_map(static fn (array $row): int => (int) ($row['gols'] ?? 0), $results);
            $placar = new PlacarService();
            if ($closed) {
                $placar->validarAlteracao($scores);
            } else {
                $placar->validarFinalizacao($scores);
                $statement = $this->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
                $statement->bind_param('i', $gameId);
                $statement->execute();
                $statement->close();
                MysqliChaveamentoRepository::chaveamentoProcessarAvanco($this->connection, $gameId);
                $this->applyPodiumPoints($gameId);
            }
            if ($closed) {
                $newWinner = ChaveamentoRules::vencedorDePartidas(MysqliChaveamentoRepository::carregarPartidasJogo($this->connection, $gameId));
                if ($oldWinner !== null && $newWinner !== null && $oldWinner !== $newWinner) {
                    $meta = ChaveamentoRules::parse((string) $statusRow['nome_jogo']);
                    if ($meta !== null && $meta['largura'] > 1) {
                        MysqliChaveamentoRepository::chaveamentoRebuildFromRound($this->connection, (int) $statusRow['modalidades_id_modalidade'], $meta['largura']);
                    }
                }
            }
            Transaction::commit($this->connection);
            return ['success' => true, 'message' => 'Resultado lançado!', 'id_jogo' => $gameId];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    private function resolveGame(int $gameId, ?string $tag, int $modalityId, array $results): int
    {
        if ($gameId > 0) {
            return $gameId;
        }
        if ($tag !== null && $modalityId > 0) {
            $row = MysqliChaveamentoRepository::buscarJogoPorTag($this->connection, $modalityId, $tag);
            if ($row !== null) {
                return (int) $row['id_jogo'];
            }
        }
        $teamIds = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['id_equipe'] ?? 0), $results), static fn (int $id): bool => $id > 0));
        if (count($teamIds) >= 2) {
            $row = $this->one(
                'SELECT p1.jogos_id_jogo FROM partidas p1
                 INNER JOIN partidas p2 ON p1.jogos_id_jogo = p2.jogos_id_jogo
                 WHERE p1.equipes_id_equipe = ? AND p2.equipes_id_equipe = ?
                 ORDER BY p1.jogos_id_jogo DESC LIMIT 1',
                'ii',
                [$teamIds[0], $teamIds[1]],
            );
            if ($row !== null) {
                return (int) $row['jogos_id_jogo'];
            }
        }
        if ($tag !== null && $tag !== '' && $modalityId > 0) {
            $local = MysqliChaveamentoRepository::resolverIdLocal($this->connection);
            $statement = $this->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
            $statement->bind_param('sii', $tag, $modalityId, $local);
            $statement->execute();
            $id = (int) $this->connection->insert_id;
            $statement->close();
            return $id;
        }
        return 0;
    }

    private function applyPodiumPoints(int $gameId): void
    {
        $game = $this->one('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1', 'i', [$gameId]);
        if ($game === null) {
            return;
        }
        $meta = ChaveamentoRules::parse((string) $game['nome_jogo']);
        if ($meta === null || (!in_array($meta['largura'], [2], true) && ($meta['posicao'] ?? null) !== 3)) {
            return;
        }
        $modality = (int) $game['modalidades_id_modalidade'];
        $edition = $this->one('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1', 'i', [$modality]);
        if ($edition === null) {
            return;
        }
        $points = $this->one('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ? LIMIT 1', 'i', [(int) $edition['interclasses_id_interclasse']]);
        if ($points === null) {
            return;
        }
        $parts = MysqliChaveamentoRepository::carregarPartidasJogo($this->connection, $gameId);
        usort($parts, static fn (array $a, array $b): int => $b['resultado_partida'] <=> $a['resultado_partida']);
        $awards = ($meta['posicao'] ?? null) === 3 ? [[3, 0]] : [[1, 0], [2, 1]];
        foreach ($awards as [$position, $index]) {
            $team = (int) ($parts[$index]['equipes_id_equipe'] ?? 0);
            $score = (int) ($points['ponto_' . $position . '_lugar'] ?? 0);
            if ($team <= 0 || $score <= 0) {
                continue;
            }
            $statement = $this->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma + ? WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1');
            $statement->bind_param('ii', $score, $team);
            $statement->execute();
            $statement->close();
        }
    }

    /** @param list<int> $params @return array<string, mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar operação de partida.');
        }
        return $statement;
    }
}
