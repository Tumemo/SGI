<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Infrastructure;

use App\Modules\Competicoes\Application\ModalidadeNaoEncontradaException;
use App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository;
use App\Modules\Competicoes\Application\IndividualRankingService;
use App\Modules\Competicoes\Infrastructure\MysqliIndividualRankingRepository;
use App\Modules\Competicoes\Domain\TipoCompeticaoRules;
use App\Modules\Resultados\Application\PontuacaoService;
use App\Modules\Resultados\Infrastructure\MysqliPodioRepository;
use App\Shared\Application\TransactionRunner;
use App\Shared\Database\MysqliTransactionRunner;
use mysqli;
use RuntimeException;

final class MysqliChaveamentoSyncGateway
{
    private readonly TransactionRunner $transactions;
    private readonly PontuacaoService $pontuacao;
    private readonly IndividualRankingService $individual;

    public function __construct(
        private readonly mysqli $connection,
        ?TransactionRunner $transactions = null,
        ?PontuacaoService $pontuacao = null,
        ?IndividualRankingService $individual = null,
    ) {
        $this->transactions = $transactions ?? new MysqliTransactionRunner($connection);
        $this->pontuacao = $pontuacao ?? new PontuacaoService(new MysqliPodioRepository($connection));
        $this->individual = $individual ?? new IndividualRankingService(new MysqliIndividualRankingRepository($connection));
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

    public function modalityType(int $modalityId): ?string
    {
        $statement = $this->connection->prepare('SELECT m.tipos_modalidades_id_tipo_modalidade, tm.nome_tipo_modalidade FROM modalidades m LEFT JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade WHERE m.id_modalidade = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar modalidade.');
        }
        $statement->bind_param('i', $modalityId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row === null ? null : TipoCompeticaoRules::resolve($row);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function sync(int $modalityId, string $type, array $data): array
    {
        return $this->transactions->run(function () use ($modalityId, $type, $data): array {
            $actualType = $this->modalityType($modalityId);
            if ($actualType === null) {
                $edition = $this->editionOfModality($modalityId);
                if ($edition === null) {
                    throw new ModalidadeNaoEncontradaException('Modalidade não encontrada.');
                }
                throw new \InvalidArgumentException('O tipo da modalidade não está configurado.');
            }
            if ($type === 'individual' && $actualType !== TipoCompeticaoRules::INDIVIDUAL) {
                throw new \InvalidArgumentException('A modalidade informada não é individual.');
            }
            if ($type === 'mata_mata' && $actualType === TipoCompeticaoRules::INDIVIDUAL) {
                throw new \InvalidArgumentException('Modalidades individuais não aceitam sincronização de mata-mata.');
            }
            if ($type === 'individual') {
                $ranking = $data['ranking'] ?? null;
                if (!is_array($ranking) || !isset($ranking['primeiro'], $ranking['segundo'], $ranking['terceiro'])) {
                    throw new \InvalidArgumentException('Dados de ranking incompletos para modalidade individual.');
                }
                $gameId = isset($data['id_jogo']) && is_numeric($data['id_jogo']) ? (int) $data['id_jogo'] : null;
                $result = $this->individual->registrar($modalityId, [
                    'primeiro' => $ranking['primeiro'],
                    'segundo' => $ranking['segundo'],
                    'terceiro' => $ranking['terceiro'],
                ], $gameId);
                return ['success' => true, 'message' => 'Sincronização de modalidade individual concluída com sucesso.', 'detalhes' => $result];
            }
            if ($type !== 'mata_mata') {
                throw new \InvalidArgumentException('Tipo de modalidade não suportado.');
            }
            $games = $data['jogos'] ?? [];
            if (!is_array($games) || $games === []) {
                throw new \InvalidArgumentException('Nenhum jogo enviado para sincronização de mata-mata.');
            }
            $this->validateMataMataPayload($modalityId, $games);
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
                    $statement = $this->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, NULL, NULL, NULL, ?, ?, NULL)");
                    $statement->bind_param('ssi', $name, $status, $modalityId);
                }
                $statement->execute();
                if ($existing === null) {
                    $gameId = (int) $this->connection->insert_id;
                }
                $statement->close();
                MysqliChaveamentoRepository::aplicarReservaAgenda($this->connection, $modalityId, $name, $gameId);
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
                $this->pontuacao->reconciliarJogo((int) $game['id_jogo']);
                MysqliChaveamentoRepository::chaveamentoProcessarAvanco($this->connection, (int) $game['id_jogo']);
            }
            return ['success' => true, 'message' => 'Sincronização de chaveamento Mata-Mata realizada com sucesso.', 'jogos_sincronizados' => $processed];
        });
    }

    /** @param list<mixed> $games */
    private function validateMataMataPayload(int $modalityId, array $games): void
    {
        if ($this->editionOfModality($modalityId) === null) {
            throw new ModalidadeNaoEncontradaException('Modalidade não encontrada.');
        }
        $validGames = 0;
        foreach ($games as $game) {
            if (!is_array($game) || trim((string) ($game['nome_jogo'] ?? '')) === '') {
                throw new \InvalidArgumentException('Cada jogo precisa de um nome válido.');
            }
            $parts = $game['partidas'] ?? null;
            if (!is_array($parts) || $parts === []) {
                throw new \InvalidArgumentException('Cada jogo precisa informar suas partidas.');
            }
            $teamIds = [];
            foreach ($parts as $part) {
                if (!is_array($part)) {
                    throw new \InvalidArgumentException('Partida inválida na sincronização.');
                }
                $teamId = (int) ($part['id_equipe'] ?? 0);
                if ($teamId <= 0 || in_array($teamId, $teamIds, true)) {
                    throw new \InvalidArgumentException('As equipes de cada jogo devem ser válidas e distintas.');
                }
                if (array_key_exists('resultado', $part) && !is_numeric($part['resultado'])) {
                    throw new \InvalidArgumentException('O resultado da partida deve ser numérico.');
                }
                $teamIds[] = $teamId;
            }
            $this->validateTeamsForModality($modalityId, $teamIds);
            $validGames++;
        }
        if ($validGames === 0) {
            throw new \InvalidArgumentException('Nenhum jogo válido foi enviado para sincronização.');
        }
    }

    /** @param list<int> $teamIds */
    private function validateTeamsForModality(int $modalityId, array $teamIds): void
    {
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $types = 'i' . str_repeat('i', count($teamIds));
        $params = array_merge([$modalityId], $teamIds);
        $statement = $this->prepare(
            'SELECT id_equipe FROM equipes
             WHERE modalidades_id_modalidade = ?
               AND id_equipe IN (' . $placeholders . ')',
        );
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar as equipes da sincronização.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        if (count($rows) !== count($teamIds)) {
            throw new \InvalidArgumentException('Todas as equipes devem pertencer à modalidade sincronizada.');
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
