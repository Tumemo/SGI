<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Sincronizacao\Infrastructure\MysqliChaveamentoSyncGateway;
use App\Shared\Database\Transaction;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class IndividualSyncCreditTest
{
    public static function run(int $editionId, int $mataModalityId): void
    {
        echo "\n  \033[1;34m[Suite 2.4: Individual e lote de sincronização]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        self::assertIndividualSyncIsIdempotent($connection, $editionId);
        self::assertMataMataSyncRestoresCredit($connection, $editionId, $mataModalityId);
        self::assertThirdPlaceSyncRestoresCredit($connection, $editionId, $mataModalityId);
        $connection->close();
    }

    private static function assertIndividualSyncIsIdempotent(\mysqli $connection, int $editionId): void
    {
        $modality = $connection->prepare(
            "SELECT m.id_modalidade
             FROM modalidades m INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
             WHERE m.interclasses_id_interclasse = ? AND tm.nome_tipo_modalidade = 'Individual'
             ORDER BY m.id_modalidade LIMIT 1",
        );
        $modality->bind_param('i', $editionId);
        $modality->execute();
        $modalityId = (int) $modality->get_result()->fetch_column();
        $modality->close();
        $game = $connection->prepare('SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = ? LIMIT 1');
        $tag = 'IND:' . $modalityId;
        $game->bind_param('is', $modalityId, $tag);
        $game->execute();
        $gameId = (int) $game->get_result()->fetch_column();
        $game->close();
        if ($modalityId <= 0 || $gameId <= 0) {
            throw new \RuntimeException('O cenário individual não encontrou o jogo de ranking.');
        }
        $rows = $connection->query('SELECT usuarios_id_usuario, resultado_partida FROM partidas WHERE jogos_id_jogo = ' . $gameId . ' ORDER BY resultado_partida')->fetch_all(\MYSQLI_ASSOC);
        if (count($rows) !== 3) {
            throw new \RuntimeException('O cenário individual não encontrou três posições persistidas.');
        }
        $ranking = [
            'primeiro' => (int) $rows[0]['usuarios_id_usuario'],
            'segundo' => (int) $rows[1]['usuarios_id_usuario'],
            'terceiro' => (int) $rows[2]['usuarios_id_usuario'],
        ];
        $source = $connection->query(
            'SELECT id_turma, pontos FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId
            . ' AND id_modalidade = ' . $modalityId . ' AND ativo = 1 ORDER BY posicao',
        )->fetch_all(\MYSQLI_ASSOC);
        $classes = array_map(static fn (array $row): int => (int) $row['id_turma'], $source);
        $before = self::classPoints($connection, $classes);
        $sync = new MysqliChaveamentoSyncGateway($connection);
        $sync->sync($modalityId, 'individual', ['ranking' => $ranking]);
        Assertions::assert('Lote individual repetido produz delta zero', self::classPoints($connection, $classes) === $before && count($connection->query('SELECT id_pontuacao FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId)->fetch_all(\MYSQLI_ASSOC)) === 3);

        $swapped = ['primeiro' => $ranking['segundo'], 'segundo' => $ranking['primeiro'], 'terceiro' => $ranking['terceiro']];
        $sync->sync($modalityId, 'individual', ['ranking' => $swapped]);
        Assertions::assert('Lote individual troca 1º/2º sem duplicar pontos', self::classPoints($connection, $classes) === self::moveClassDeltas($before, $classes, [2, 1, 3], array_map(static fn (array $row): int => (int) $row['pontos'], $source)));

        $invalidBefore = self::classPoints($connection, $classes);
        $invalid = false;
        try {
            $sync->sync($modalityId, 'individual', ['ranking' => ['primeiro' => $ranking['primeiro'], 'segundo' => $ranking['primeiro'], 'terceiro' => $ranking['terceiro']]]);
        } catch (\Throwable) {
            $invalid = true;
        }
        Assertions::assert('Lote individual inválido reverte sem escrita parcial', $invalid && self::classPoints($connection, $classes) === $invalidBefore);

        $invalidGameId = false;
        try {
            $sync->sync($modalityId, 'individual', [
                'id_jogo' => '20abc',
                'ranking' => $ranking,
            ]);
        } catch (\InvalidArgumentException) {
            $invalidGameId = true;
        }
        Assertions::assert('Sincronização rejeita ID de jogo malformado sem alterar pontos', $invalidGameId && self::classPoints($connection, $classes) === $invalidBefore);
    }

    private static function assertMataMataSyncRestoresCredit(\mysqli $connection, int $editionId, int $modalityId): void
    {
        $forcedIndividual = false;
        try {
            (new MysqliChaveamentoSyncGateway($connection))->sync($modalityId, 'individual', [
                'ranking' => ['primeiro' => 1, 'segundo' => 2, 'terceiro' => 3],
            ]);
        } catch (\InvalidArgumentException) {
            $forcedIndividual = true;
        }
        Assertions::assert('Sincronização individual não pode forçar modalidade coletiva', $forcedIndividual);
        $final = $connection->query(
            "SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = {$modalityId}
             AND nome_jogo = 'MM:2:0:N' AND status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($final === null) {
            throw new \RuntimeException('O lote mata-mata não encontrou final concluída.');
        }
        $gameId = (int) $final['id_jogo'];
        $parts = $connection->query('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ' . $gameId . ' ORDER BY id_partida')->fetch_all(\MYSQLI_ASSOC);
        $credits = $connection->query(
            'SELECT id_pontuacao, id_turma, pontos FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId
            . ' AND id_modalidade = ' . $modalityId . ' AND id_jogo = ' . $gameId . ' AND posicao IN (1, 2) AND ativo = 1 ORDER BY posicao',
        )->fetch_all(\MYSQLI_ASSOC);
        if (count($parts) !== 2 || count($credits) !== 2) {
            throw new \RuntimeException('O lote mata-mata não encontrou final e créditos ativos.');
        }
        $classes = array_map(static fn (array $credit): int => (int) $credit['id_turma'], $credits);
        $before = self::classPoints($connection, $classes);
        Transaction::begin($connection);
        try {
            foreach ($credits as $credit) {
                $classId = (int) $credit['id_turma'];
                $points = (int) $credit['pontos'];
                $update = $connection->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma - ? WHERE id_turma = ?');
                $update->bind_param('ii', $points, $classId);
                $update->execute();
                $update->close();
            }
            $connection->query('UPDATE pontuacoes_podio SET ativo = 0 WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND id_jogo = ' . $gameId . ' AND posicao IN (1, 2)');
            $payload = [[
                'nome_jogo' => 'MM:2:0:N',
                'status_jogo' => 'Concluido',
                'partidas' => array_map(static fn (array $part): array => ['id_equipe' => (int) $part['equipes_id_equipe'], 'resultado' => (int) $part['resultado_partida']], $parts),
            ]];
            (new MysqliChaveamentoSyncGateway($connection))->sync($modalityId, 'mata_mata', ['jogos' => $payload]);
            $restored = self::classPoints($connection, $classes);
            $active = (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND posicao IN (1, 2) AND ativo = 1')->fetch_column();
            Assertions::assert('Lote mata-mata reconcilia o pódio sem reaplicar crédito', $restored === $before && $active === 2);
        } finally {
            Transaction::rollback($connection);
        }
    }

    private static function assertThirdPlaceSyncRestoresCredit(\mysqli $connection, int $editionId, int $modalityId): void
    {
        $gameStatement = $connection->prepare(
            "SELECT id_jogo, nome_jogo FROM jogos
             WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'POS:3:%'
               AND status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY id_jogo DESC LIMIT 1",
        );
        $gameStatement->bind_param('i', $modalityId);
        $gameStatement->execute();
        $game = $gameStatement->get_result()->fetch_assoc();
        $gameStatement->close();
        if ($game === null) {
            throw new \RuntimeException('O lote não encontrou disputa de terceiro lugar concluída.');
        }
        $gameId = (int) $game['id_jogo'];
        $partsStatement = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $partsStatement->bind_param('i', $gameId);
        $partsStatement->execute();
        $parts = $partsStatement->get_result()->fetch_all(\MYSQLI_ASSOC);
        $partsStatement->close();
        $creditStatement = $connection->prepare(
            'SELECT id_pontuacao, id_turma, pontos FROM pontuacoes_podio
             WHERE id_interclasse = ? AND id_modalidade = ? AND id_jogo = ? AND posicao = 3 AND ativo = 1',
        );
        $creditStatement->bind_param('iii', $editionId, $modalityId, $gameId);
        $creditStatement->execute();
        $credit = $creditStatement->get_result()->fetch_assoc();
        $creditStatement->close();
        if (count($parts) !== 2 || $credit === null) {
            throw new \RuntimeException('A disputa de terceiro lugar não possui duas equipes e crédito ativo.');
        }

        $classId = (int) $credit['id_turma'];
        $points = (int) $credit['pontos'];
        $before = self::classPoints($connection, [$classId]);
        Transaction::begin($connection);
        try {
            $subtract = $connection->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma - ? WHERE id_turma = ?');
            $subtract->bind_param('ii', $points, $classId);
            $subtract->execute();
            $subtract->close();
            $disable = $connection->prepare('UPDATE pontuacoes_podio SET ativo = 0 WHERE id_pontuacao = ?');
            $creditId = (int) $credit['id_pontuacao'];
            $disable->bind_param('i', $creditId);
            $disable->execute();
            $disable->close();

            $payload = [[
                'nome_jogo' => (string) $game['nome_jogo'],
                'status_jogo' => 'Concluido',
                'partidas' => array_map(static fn (array $part): array => [
                    'id_equipe' => (int) $part['equipes_id_equipe'],
                    'resultado' => (int) $part['resultado_partida'],
                ], $parts),
            ]];
            (new MysqliChaveamentoSyncGateway($connection))->sync($modalityId, 'mata_mata', ['jogos' => $payload]);
            $restored = self::classPoints($connection, [$classId]);
            $active = (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio WHERE id_pontuacao = ' . $creditId . ' AND ativo = 1')->fetch_column();
            Assertions::assert(
                'Lote coletivo reconcilia crédito da disputa de terceiro lugar sem reaplicar pontos',
                $restored === $before && $active === 1,
                json_encode(['antes' => $before, 'depois' => $restored, 'crédito_ativo' => $active], JSON_UNESCAPED_UNICODE),
            );
        } finally {
            Transaction::rollback($connection);
        }
    }

    /** @param list<int> $classIds @return array<int, int> */
    private static function classPoints(\mysqli $connection, array $classIds): array
    {
        $result = [];
        foreach (array_unique($classIds) as $classId) {
            $statement = $connection->prepare('SELECT pontuacao_turma FROM turmas WHERE id_turma = ?');
            $statement->bind_param('i', $classId);
            $statement->execute();
            $result[$classId] = (int) $statement->get_result()->fetch_column();
            $statement->close();
        }
        ksort($result);
        return $result;
    }

    /** @param array<int, int> $before @param list<int> $oldClasses @param list<int> $positions @param list<int> $points @return array<int, int> */
    private static function moveClassDeltas(array $before, array $oldClasses, array $positions, array $points): array
    {
        $result = $before;
        foreach ($oldClasses as $index => $classId) {
            $position = $index + 1;
            $result[$classId] = ($result[$classId] ?? 0) - ($points[$position - 1] ?? 0);
        }
        foreach ($positions as $index => $oldIndex) {
            $classId = $oldClasses[$oldIndex - 1];
            $result[$classId] = ($result[$classId] ?? 0) + ($points[$index] ?? 0);
        }
        ksort($result);
        return $result;
    }
}
