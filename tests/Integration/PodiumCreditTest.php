<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class PodiumCreditTest
{
    /** @param list<int> $teamIds */
    public static function run(int $editionId, int $modalityId, array $teamIds): void
    {
        echo "\n  \033[1;34m[Suite 2.3: Créditos de pódio e retificação]\033[0m\n";
        $admin = new TestClient();
        $admin->login('admin', '123');
        $mesario = new TestClient();
        $mesario->login('mesario', '123');
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);

        $final = $connection->prepare(
            "SELECT j.id_jogo, p.equipes_id_equipe, p.resultado_partida
             FROM jogos j INNER JOIN partidas p ON p.jogos_id_jogo = j.id_jogo
             WHERE j.modalidades_id_modalidade = ? AND j.nome_jogo = 'MM:2:0:N'
               AND j.status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY j.id_jogo DESC, p.id_partida",
        );
        $final->bind_param('i', $modalityId);
        $final->execute();
        $rows = $final->get_result()->fetch_all(\MYSQLI_ASSOC);
        $final->close();
        if (count($rows) < 2) {
            throw new \RuntimeException('O cenário de pódio não encontrou uma final concluída.');
        }
        $gameId = (int) $rows[0]['id_jogo'];
        usort($rows, static fn (array $a, array $b): int => (int) $b['resultado_partida'] <=> (int) $a['resultado_partida']);
        $winner = (int) $rows[0]['equipes_id_equipe'];
        $runnerUp = (int) $rows[1]['equipes_id_equipe'];
        $points = self::podiumValues($connection, $editionId);
        $winnerClass = self::teamClass($connection, $winner);
        $runnerUpClass = self::teamClass($connection, $runnerUp);
        $before = self::classPoints($connection, [$winnerClass, $runnerUpClass]);

        try {
            $gateway = new \App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway($connection);
            $result = $gateway->launch($gameId, 'MM:2:0:N', $modalityId, [
                ['id_equipe' => $winner, 'gols' => 1],
                ['id_equipe' => $runnerUp, 'gols' => 5],
            ]);
            Assertions::assert('Retificação da final aceita para reconciliar o pódio', ($result['success'] ?? false) === true);
        } catch (\Throwable $exception) {
            Assertions::assert('Retificação da final aceita para reconciliar o pódio', false, $exception->getMessage() . ' @ ' . $exception->getFile() . ':' . $exception->getLine());
        }
        $after = self::classPoints($connection, [$winnerClass, $runnerUpClass]);
        $expectedWinner = $before[$winnerClass] - $points[1] + $points[2];
        $expectedRunnerUp = $before[$runnerUpClass] - $points[2] + $points[1];
        Assertions::assert('Retificação move o crédito da posição 1 para a posição 2', $after[$winnerClass] === $expectedWinner, json_encode(['antes' => $before[$winnerClass], 'depois' => $after[$winnerClass], 'esperado' => $expectedWinner]));
        Assertions::assert('Retificação move o crédito da posição 2 para a posição 1', $after[$runnerUpClass] === $expectedRunnerUp, json_encode(['antes' => $before[$runnerUpClass], 'depois' => $after[$runnerUpClass], 'esperado' => $expectedRunnerUp]));
        $credits = $connection->query('SELECT posicao, id_equipe, pontos, origem_registro FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' ORDER BY posicao')->fetch_all(\MYSQLI_ASSOC);
        Assertions::assert('Pódio mata-mata mantém uma origem única por posição e valor zero seria válido', count(array_unique(array_column($credits, 'posicao'))) === count($credits) && count($credits) >= 2 && count(array_filter($credits, static fn (array $credit): bool => $credit['origem_registro'] === 'novo')) === count($credits));
        self::runFinalConfigPreservationScenario($connection, $editionId, $modalityId, $points);
        self::runSemifinalInvalidationScenario($connection, $editionId, $modalityId);
        self::runThirdPlaceScenario($connection, $editionId, $modalityId, $points[3]);
        self::runUnconciledCorrectionScenario($connection, $editionId, $modalityId);
        self::runIndividualScenario($connection, $editionId);
        $connection->close();
    }

    private static function runIndividualScenario(\mysqli $connection, int $editionId): void
    {
        $modality = $connection->prepare("SELECT m.id_modalidade FROM modalidades m INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade WHERE m.interclasses_id_interclasse = ? AND tm.nome_tipo_modalidade = 'Individual' ORDER BY m.id_modalidade LIMIT 1");
        $modality->bind_param('i', $editionId);
        $modality->execute();
        $modalityId = (int) $modality->get_result()->fetch_column();
        $modality->close();
        $usersResult = $connection->query('SELECT id_usuario, turmas_id_turma FROM usuarios WHERE interclasses_id_interclasse = ' . $editionId . " AND nivel_usuario = '3' AND status_usuario = '1' AND turmas_id_turma IS NOT NULL ORDER BY id_usuario LIMIT 3");
        $users = $usersResult->fetch_all(\MYSQLI_ASSOC);
        if ($modalityId <= 0 || count($users) < 3) {
            throw new \RuntimeException('O cenário individual não encontrou modalidade e competidores suficientes.');
        }
        $teams = [];
        foreach ($users as $index => $user) {
            $name = 'Podio individual T14 ' . $index . ' ' . bin2hex(random_bytes(3));
            $team = $connection->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)");
            $classId = (int) $user['turmas_id_turma'];
            $team->bind_param('iis', $modalityId, $classId, $name);
            $team->execute();
            $teams[] = (int) $connection->insert_id;
            $team->close();
            $membership = $connection->prepare('INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
            $userId = (int) $user['id_usuario'];
            $membership->bind_param('ii', $teams[$index], $userId);
            $membership->execute();
            $membership->close();
        }
        $classes = array_map(static fn (array $user): int => (int) $user['turmas_id_turma'], $users);
        $before = self::classPoints($connection, $classes);
        $ranking = [
            'primeiro' => (int) $users[0]['id_usuario'],
            'segundo' => (int) $users[1]['id_usuario'],
            'terceiro' => (int) $users[2]['id_usuario'],
        ];
        \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $ranking);
        $points = self::podiumValues($connection, $editionId);
        $afterFirst = self::classPoints($connection, $classes);
        $expectedFirst = self::withClassDeltas($before, $classes, [1 => $points[1], 2 => $points[2], 3 => $points[3]]);
        Assertions::assert('Modalidade individual grava os três créditos de pódio', $afterFirst === $expectedFirst);

        $revised = ['primeiro' => $ranking['segundo'], 'segundo' => $ranking['primeiro'], 'terceiro' => $ranking['terceiro']];
        \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $revised);
        $afterRevision = self::classPoints($connection, $classes);
        $expectedRevision = self::moveClassDeltas($afterFirst, $classes, [$classes[1], $classes[0], $classes[2]], [1 => $points[1], 2 => $points[2], 3 => $points[3]]);
        Assertions::assert('Retificação individual move o crédito sem duplicá-lo', $afterRevision === $expectedRevision, json_encode(['depois' => $afterRevision, 'esperado' => $expectedRevision]));
        $count = (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId)->fetch_column();
        Assertions::assert('Modalidade individual mantém uma linha por posição', $count === 3);
    }

    private static function runSemifinalInvalidationScenario(\mysqli $connection, int $editionId, int $modalityId): void
    {
        $semi = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId}
               AND nome_jogo LIKE 'MM:4:%:N'
               AND status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($semi === null) {
            throw new \RuntimeException('O cenário de invalidação não encontrou semifinal concluída.');
        }
        $semiId = (int) $semi['id_jogo'];
        $partsStatement = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $partsStatement->bind_param('i', $semiId);
        $partsStatement->execute();
        $parts = $partsStatement->get_result()->fetch_all(\MYSQLI_ASSOC);
        $partsStatement->close();
        if (count($parts) < 2) {
            throw new \RuntimeException('A semifinal do cenário não possui dois participantes.');
        }
        usort($parts, static fn (array $a, array $b): int => (int) $b['resultado_partida'] <=> (int) $a['resultado_partida']);
        $winner = (int) $parts[0]['equipes_id_equipe'];
        $loser = (int) $parts[1]['equipes_id_equipe'];
        $oldCreditsResult = $connection->query(
            'SELECT posicao, id_turma, pontos FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId
            . ' AND id_modalidade = ' . $modalityId . ' AND ativo = 1 ORDER BY posicao',
        );
        $oldCredits = $oldCreditsResult->fetch_all(\MYSQLI_ASSOC);
        if ($oldCredits === []) {
            throw new \RuntimeException('O cenário de invalidação não possui créditos ativos.');
        }
        $classes = array_map(static fn (array $credit): int => (int) $credit['id_turma'], $oldCredits);
        $before = self::classPoints($connection, $classes);
        $expectedAfter = $before;
        foreach ($oldCredits as $credit) {
            $classId = (int) $credit['id_turma'];
            $expectedAfter[$classId] = ($expectedAfter[$classId] ?? 0) - (int) $credit['pontos'];
        }
        $gateway = new \App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway($connection);
        $gateway->launch($semiId, null, $modalityId, [
            ['id_equipe' => $winner, 'gols' => 0],
            ['id_equipe' => $loser, 'gols' => 2],
        ]);
        $activeCredits = (int) $connection->query(
            'SELECT COUNT(*) FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId
            . ' AND id_modalidade = ' . $modalityId . ' AND ativo = 1 AND posicao IN (1, 2, 3)',
        )->fetch_column();
        $after = self::classPoints($connection, $classes);
        Assertions::assert('Correção de semifinal invalida o crédito do pódio antigo', $activeCredits === 0 && $after === $expectedAfter);

        $final = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = 'MM:2:0:N'
               AND status_jogo = 'Agendado' ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($final === null) {
            throw new \RuntimeException('A reconstrução não criou uma nova final agendada.');
        }
        $finalId = (int) $final['id_jogo'];
        $finalParts = $connection->prepare('SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $finalParts->bind_param('i', $finalId);
        $finalParts->execute();
        $finalTeams = array_map(static fn (array $row): int => (int) $row['equipes_id_equipe'], $finalParts->get_result()->fetch_all(\MYSQLI_ASSOC));
        $finalParts->close();
        if (count($finalTeams) !== 2) {
            throw new \RuntimeException('A nova final não possui exatamente dois participantes.');
        }
        $newClasses = array_map(fn (int $teamId): int => self::teamClass($connection, $teamId), $finalTeams);
        $expectedFinal = self::classPoints($connection, array_merge($classes, $newClasses));
        foreach ($newClasses as $index => $classId) {
            $position = $index + 1;
            $expectedFinal[$classId] = ($expectedFinal[$classId] ?? 0) + self::podiumValues($connection, $editionId)[$position];
        }
        $gateway->launch($finalId, 'MM:2:0:N', $modalityId, [
            ['id_equipe' => $finalTeams[0], 'gols' => 2],
            ['id_equipe' => $finalTeams[1], 'gols' => 0],
        ]);
        Assertions::assert('Nova final concede o pódio sem duplicar créditos', self::classPoints($connection, array_merge($classes, $newClasses)) === $expectedFinal);
    }

    /** @param array{1:int,2:int,3:int} $originalPoints */
    private static function runFinalConfigPreservationScenario(\mysqli $connection, int $editionId, int $modalityId, array $originalPoints): void
    {
        $final = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = 'MM:2:0:N'
               AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($final === null) {
            throw new \RuntimeException('A preservação de configuração não encontrou final concluída.');
        }
        $finalId = (int) $final['id_jogo'];
        $parts = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $parts->bind_param('i', $finalId);
        $parts->execute();
        $rows = $parts->get_result()->fetch_all(\MYSQLI_ASSOC);
        $parts->close();
        usort($rows, static fn (array $a, array $b): int => (int) $b['resultado_partida'] <=> (int) $a['resultado_partida']);
        $currentWinner = (int) $rows[0]['equipes_id_equipe'];
        $currentRunner = (int) $rows[1]['equipes_id_equipe'];
        $source = $connection->query(
            'SELECT posicao, id_turma, pontos FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId
            . ' AND id_modalidade = ' . $modalityId . ' AND posicao IN (1, 2) AND ativo = 1 ORDER BY posicao',
        )->fetch_all(\MYSQLI_ASSOC);
        if (count($source) !== 2) {
            throw new \RuntimeException('A preservação de configuração não encontrou as duas fontes da final.');
        }
        $classes = [$source[0]['id_turma'] = (int) $source[0]['id_turma'], $source[1]['id_turma'] = (int) $source[1]['id_turma']];
        $before = self::classPoints($connection, $classes);
        $expected = $before;
        $expected[$classes[0]] -= (int) $source[0]['pontos'];
        $expected[$classes[1]] -= (int) $source[1]['pontos'];
        $expected[$classes[0]] += (int) $source[1]['pontos'];
        $expected[$classes[1]] += (int) $source[0]['pontos'];
        $update = $connection->prepare('UPDATE interclasses SET ponto_1_lugar = 99, ponto_2_lugar = 88 WHERE id_interclasse = ?');
        $update->bind_param('i', $editionId);
        $update->execute();
        $update->close();
        $gateway = new \App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway($connection);
        try {
            $gateway->launch($finalId, 'MM:2:0:N', $modalityId, [
                ['id_equipe' => $currentWinner, 'gols' => 0],
                ['id_equipe' => $currentRunner, 'gols' => 2],
            ]);
        } finally {
            $restore = $connection->prepare('UPDATE interclasses SET ponto_1_lugar = ?, ponto_2_lugar = ?, ponto_3_lugar = ? WHERE id_interclasse = ?');
            $restore->bind_param('iiii', $originalPoints[1], $originalPoints[2], $originalPoints[3], $editionId);
            $restore->execute();
            $restore->close();
        }
        Assertions::assert('Correção preserva os valores já concedidos após mudança de configuração', self::classPoints($connection, $classes) === $expected);
        $beforeRepeat = self::classPoints($connection, $classes);
        $gateway->launch($finalId, 'MM:2:0:N', $modalityId, [
            ['id_equipe' => $currentWinner, 'gols' => 0],
            ['id_equipe' => $currentRunner, 'gols' => 2],
        ]);
        Assertions::assert('Mesmo resultado repetido produz delta zero', self::classPoints($connection, $classes) === $beforeRepeat);
    }

    private static function runThirdPlaceScenario(\mysqli $connection, int $editionId, int $modalityId, int $points): void
    {
        $third = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo LIKE 'POS:3:%'
               AND status_jogo = 'Agendado' ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($third === null) {
            throw new \RuntimeException('A disputa de terceiro lugar não foi recriada.');
        }
        $thirdId = (int) $third['id_jogo'];
        $parts = $connection->prepare('SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $parts->bind_param('i', $thirdId);
        $parts->execute();
        $teams = array_map(static fn (array $row): int => (int) $row['equipes_id_equipe'], $parts->get_result()->fetch_all(\MYSQLI_ASSOC));
        $parts->close();
        if (count($teams) !== 2) {
            throw new \RuntimeException('A disputa de terceiro lugar não possui dois participantes.');
        }
        $gateway = new \App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway($connection);
        $before = self::classPoints($connection, array_map(fn (int $team): int => self::teamClass($connection, $team), $teams));
        $gateway->launch($thirdId, 'POS:3:0:N', $modalityId, [
            ['id_equipe' => $teams[0], 'gols' => 1],
            ['id_equipe' => $teams[1], 'gols' => 0],
        ]);
        $classFirst = self::teamClass($connection, $teams[0]);
        $classSecond = self::teamClass($connection, $teams[1]);
        $afterFirst = self::classPoints($connection, [$classFirst, $classSecond]);
        $expectedFirst = $before;
        $expectedFirst[$classFirst] = ($expectedFirst[$classFirst] ?? 0) + $points;
        Assertions::assert('Terceiro lugar concede somente o crédito da posição 3', $afterFirst === $expectedFirst);
        $gateway->launch($thirdId, 'POS:3:0:N', $modalityId, [
            ['id_equipe' => $teams[0], 'gols' => 0],
            ['id_equipe' => $teams[1], 'gols' => 1],
        ]);
        $expectedSecond = $afterFirst;
        $expectedSecond[$classFirst] -= $points;
        $expectedSecond[$classSecond] += $points;
        $source = $connection->query('SELECT id_equipe, pontos, ativo FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND posicao = 3')->fetch_assoc();
        Assertions::assert('Retificação do terceiro lugar move somente a posição 3', self::classPoints($connection, [$classFirst, $classSecond]) === $expectedSecond && $source !== null && (int) $source['id_equipe'] === $teams[1] && (int) $source['pontos'] === $points && (int) $source['ativo'] === 1);
    }

    private static function runUnconciledCorrectionScenario(\mysqli $connection, int $editionId, int $modalityId): void
    {
        $final = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = 'MM:2:0:N'
               AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($final === null) {
            throw new \RuntimeException('A validação de pódio não conciliado não encontrou final.');
        }
        $finalId = (int) $final['id_jogo'];
        $parts = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $parts->bind_param('i', $finalId);
        $parts->execute();
        $rows = $parts->get_result()->fetch_all(\MYSQLI_ASSOC);
        $parts->close();
        usort($rows, static fn (array $a, array $b): int => (int) $b['resultado_partida'] <=> (int) $a['resultado_partida']);
        $beforeScores = array_map(static fn (array $row): array => ['id_equipe' => (int) $row['equipes_id_equipe'], 'gols' => (int) $row['resultado_partida']], $rows);
        \App\Shared\Database\Transaction::begin($connection);
        $connection->query('DELETE FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND posicao IN (1, 2)');
        $rejected = false;
        try {
            (new \App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway($connection))->launch($finalId, 'MM:2:0:N', $modalityId, [
                ['id_equipe' => $beforeScores[0]['id_equipe'], 'gols' => 0],
                ['id_equipe' => $beforeScores[1]['id_equipe'], 'gols' => 2],
            ]);
        } catch (\Throwable) {
            $rejected = true;
        } finally {
            \App\Shared\Database\Transaction::rollback($connection);
        }
        $afterScores = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida');
        $afterScores->bind_param('i', $finalId);
        $afterScores->execute();
        $after = array_map(static fn (array $row): array => ['id_equipe' => (int) $row['equipes_id_equipe'], 'gols' => (int) $row['resultado_partida']], $afterScores->get_result()->fetch_all(\MYSQLI_ASSOC));
        $afterScores->close();
        $sources = (int) $connection->query('SELECT COUNT(*) FROM pontuacoes_podio WHERE id_interclasse = ' . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND posicao IN (1, 2)')->fetch_column();
        Assertions::assert('Retificação sem pódio conciliado falha sem escrita', $rejected && $after === $beforeScores && $sources === 2);
    }

    /** @return array{1:int,2:int,3:int} */
    private static function podiumValues(\mysqli $connection, int $editionId): array
    {
        $row = $connection->query('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ' . $editionId)->fetch_assoc();
        return [1 => (int) ($row['ponto_1_lugar'] ?? 0), 2 => (int) ($row['ponto_2_lugar'] ?? 0), 3 => (int) ($row['ponto_3_lugar'] ?? 0)];
    }

    private static function teamClass(\mysqli $connection, int $teamId): int
    {
        $statement = $connection->prepare('SELECT turmas_id_turma FROM equipes WHERE id_equipe = ?');
        $statement->bind_param('i', $teamId);
        $statement->execute();
        $classId = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $classId;
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

    /** @param array<int, int> $before @param list<int> $classes @param array<int, int> $deltas @return array<int, int> */
    private static function withClassDeltas(array $before, array $classes, array $deltas): array
    {
        $result = $before;
        foreach ($classes as $index => $classId) {
            $position = $index + 1;
            $result[$classId] = ($result[$classId] ?? 0) + ($deltas[$position] ?? 0);
        }
        ksort($result);
        return $result;
    }

    /** @param array<int, int> $before @param list<int> $oldClasses @param list<int> $newClasses @param array<int, int> $points @return array<int, int> */
    private static function moveClassDeltas(array $before, array $oldClasses, array $newClasses, array $points): array
    {
        $result = $before;
        foreach ($oldClasses as $index => $classId) {
            $position = $index + 1;
            $result[$classId] = ($result[$classId] ?? 0) - ($points[$position] ?? 0);
        }
        foreach ($newClasses as $index => $classId) {
            $position = $index + 1;
            $result[$classId] = ($result[$classId] ?? 0) + ($points[$position] ?? 0);
        }
        ksort($result);
        return $result;
    }
}
