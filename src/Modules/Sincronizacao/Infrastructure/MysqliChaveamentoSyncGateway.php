<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Infrastructure;

use App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository;
use App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository;
use App\Shared\Database\Transaction;
use mysqli;
use RuntimeException;

final class MysqliChaveamentoSyncGateway
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function editionOfModality(int $modalityId): ?int
    {
        $statement = $this->connection->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar modalidade.');
        }
        $statement->bind_param('i', $modalityId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row === null ? null : (int) $row['interclasses_id_interclasse'];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function sync(int $modalityId, string $type, array $data): array
    {
        Transaction::begin($this->connection);
        try {
            if ($type === 'individual') {
                $ranking = $data['ranking'] ?? null;
                if (!is_array($ranking) || !isset($ranking['primeiro'], $ranking['segundo'], $ranking['terceiro'])) {
                    throw new RuntimeException('Dados de ranking incompletos para modalidade individual.');
                }
                $result = MysqliIndividualRepository::salvarRanking($this->connection, $modalityId, [
                    'primeiro' => (int) $ranking['primeiro'],
                    'segundo' => (int) $ranking['segundo'],
                    'terceiro' => (int) $ranking['terceiro'],
                ]);
                Transaction::commit($this->connection);
                return ['success' => true, 'message' => 'Sincronização de modalidade individual concluída com sucesso.', 'detalhes' => $result];
            }
            if ($type !== 'mata_mata') {
                throw new RuntimeException('Tipo de modalidade não suportado.');
            }
            $games = $data['jogos'] ?? [];
            if (!is_array($games) || $games === []) {
                throw new RuntimeException('Nenhum jogo enviado para sincronização de mata-mata.');
            }
            $local = MysqliChaveamentoRepository::resolverIdLocal($this->connection);
            $processed = 0;
            foreach ($games as $game) {
                if (!is_array($game) || trim((string) ($game['nome_jogo'] ?? '')) === '') {
                    continue;
                }
                $name = (string) $game['nome_jogo'];
                $status = (string) ($game['status_jogo'] ?? 'Agendado');
                $existing = MysqliChaveamentoRepository::buscarJogoPorTag($this->connection, $modalityId, $name);
                if ($existing !== null) {
                    $gameId = (int) $existing['id_jogo'];
                    $statement = $this->prepare('UPDATE jogos SET status_jogo = ? WHERE id_jogo = ?');
                    $statement->bind_param('si', $status, $gameId);
                } else {
                    $statement = $this->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '08:00:00', ?, ?, ?)");
                    $statement->bind_param('ssii', $name, $status, $modalityId, $local);
                }
                $statement->execute();
                if ($existing === null) {
                    $gameId = (int) $this->connection->insert_id;
                }
                $statement->close();
                foreach ((array) ($game['partidas'] ?? []) as $part) {
                    if (!is_array($part)) {
                        continue;
                    }
                    $teamId = (int) ($part['id_equipe'] ?? 0);
                    if ($teamId <= 0) {
                        continue;
                    }
                    $score = (int) ($part['resultado'] ?? 0);
                    $current = $this->one('SELECT id_partida FROM partidas WHERE jogos_id_jogo = ? AND equipes_id_equipe = ? LIMIT 1', 'ii', [$gameId, $teamId]);
                    if ($current !== null) {
                        $statement = $this->prepare('UPDATE partidas SET resultado_partida = ? WHERE id_partida = ?');
                        $partId = (int) $current['id_partida'];
                        $statement->bind_param('ii', $score, $partId);
                    } else {
                        $statement = $this->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, ?, '1')");
                        $statement->bind_param('iii', $gameId, $teamId, $score);
                    }
                    $statement->execute();
                    $statement->close();
                }
                $processed++;
            }
            $statement = $this->prepare("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:%' AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo ASC");
            $statement->bind_param('i', $modalityId);
            $statement->execute();
            $completed = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
            foreach ($completed as $game) {
                MysqliChaveamentoRepository::chaveamentoProcessarAvanco($this->connection, (int) $game['id_jogo']);
            }
            Transaction::commit($this->connection);
            return ['success' => true, 'message' => 'Sincronização de chaveamento Mata-Mata realizada com sucesso.', 'jogos_sincronizados' => $processed];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
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
            throw new RuntimeException('Não foi possível preparar sincronização.');
        }
        return $statement;
    }
}
