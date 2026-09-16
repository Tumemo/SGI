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
            $result = $gateway->launch(
                $gameId,
                'MM:2:0:N',
                $modalityId,
                [
                    ['id_equipe' => $winner, 'gols' => 1],
                    ['id_equipe' => $runnerUp, 'gols' => 5],
                ],
                array_merge(
                    self::pointAdjustments($connection, $gameId, $winner, 1),
                    self::pointAdjustments($connection, $gameId, $runnerUp, 5),
                ),
                self::operatorId($connection),
            );
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
        self::runAutomaticThirdPlaceScenario($connection, $editionId, $modalityId, $points[3]);
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
        $usersResult = $connection->query(
            'SELECT id_usuario, turmas_id_turma FROM usuarios
             WHERE interclasses_id_interclasse = ' . $editionId . "
               AND nivel_usuario = '3' AND status_usuario = '1' AND turmas_id_turma IS NOT NULL
               AND turmas_id_turma = (
                   SELECT turmas_id_turma FROM usuarios
                   WHERE interclasses_id_interclasse = " . $editionId . "
                     AND nivel_usuario = '3' AND status_usuario = '1' AND turmas_id_turma IS NOT NULL
                   GROUP BY turmas_id_turma HAVING COUNT(*) >= 3 ORDER BY turmas_id_turma LIMIT 1
               )
             ORDER BY id_usuario LIMIT 3",
        );
        $users = $usersResult->fetch_all(\MYSQLI_ASSOC);
        if ($modalityId <= 0 || count($users) < 3) {
            throw new \RuntimeException('O cenário individual não encontrou modalidade e competidores suficientes.');
        }
        // Os três colocados podem pertencer à mesma equipe; a equipe é o
        // vínculo técnico da inscrição, não uma restrição de pódio.
        $team = $connection->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)");
        $classId = (int) $users[0]['turmas_id_turma'];
        $name = 'Podio individual T14 mesma equipe ' . bin2hex(random_bytes(3));
        $team->bind_param('iis', $modalityId, $classId, $name);
        $team->execute();
        $teamId = (int) $connection->insert_id;
        $team->close();
        foreach ($users as $user) {
            $membership = $connection->prepare('INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
            $userId = (int) $user['id_usuario'];
            $membership->bind_param('ii', $teamId, $userId);
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
        $rejectedBeforePreparation = false;
        try {
            \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $ranking);
        } catch (\RuntimeException) {
            $rejectedBeforePreparation = true;
        }
        $gamesBeforePreparation = (int) $connection->query("SELECT COUNT(*) FROM jogos WHERE nome_jogo = 'IND:" . $modalityId . "'")->fetch_column();
        Assertions::assert('Ranking individual sem preparação é rejeitado sem criar jogo', $rejectedBeforePreparation && $gamesBeforePreparation === 0);
        \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogoAgenda($connection, $modalityId);
        $gameRow = $connection->query("SELECT id_jogo FROM jogos WHERE nome_jogo = 'IND:" . $modalityId . "' ORDER BY id_jogo DESC LIMIT 1")->fetch_assoc();
        $gameId = (int) ($gameRow['id_jogo'] ?? 0);
        $connection->query("UPDATE jogos SET status_jogo = 'Iniciado' WHERE id_jogo = " . $gameId);
        $savedWithExplicitGame = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $ranking, $gameId);
        Assertions::assert('Ranking individual usa o jogo explícito enviado pela tela', $savedWithExplicitGame['id_jogo'] === $gameId && $gameId > 0);
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
        $partidasBeforeReprepare = (int) $connection->query("SELECT COUNT(*) FROM partidas p INNER JOIN jogos j ON j.id_jogo = p.jogos_id_jogo WHERE j.nome_jogo = 'IND:" . $modalityId . "'")->fetch_column();
        $reprepare = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogoAgenda($connection, $modalityId);
        $partidasAfterReprepare = (int) $connection->query("SELECT COUNT(*) FROM partidas p INNER JOIN jogos j ON j.id_jogo = p.jogos_id_jogo WHERE j.nome_jogo = 'IND:" . $modalityId . "'")->fetch_column();
        Assertions::assert('Preparar novamente pódio concluído não cria placeholders nem altera partidas', $reprepare['jogos_criados'] === 0 && $partidasAfterReprepare === $partidasBeforeReprepare);

        // Uma tag de mata-mata na mesma modalidade não pode ser aceita como a
        // prova individual apenas porque o ID do jogo é válido.
        $connection->query("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES ('MM:2:0:N', NULL, NULL, NULL, 'Iniciado', {$modalityId}, NULL)");
        $wrongGameId = (int) $connection->insert_id;
        $wrongIdentityRejected = false;
        try {
            \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $ranking, $wrongGameId);
        } catch (\RuntimeException) {
            $wrongIdentityRejected = true;
        }
        $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $wrongGameId);
        Assertions::assert('Ranking individual rejeita jogo explícito com identidade de mata-mata', $wrongIdentityRejected);

        // Sem ID explícito, duas provas canônicas também são ambíguas e não
        // podem ser resolvidas por LIMIT 1.
        $connection->query("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES ('IND:{$modalityId}', NULL, NULL, NULL, 'Agendado', {$modalityId}, NULL)");
        $duplicateGameId = (int) $connection->insert_id;
        $duplicateRejected = false;
        try {
            \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($connection, $modalityId, $ranking);
        } catch (\RuntimeException) {
            $duplicateRejected = true;
        }
        $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $duplicateGameId);
        Assertions::assert('Ranking individual não escolhe uma prova entre tags duplicadas', $duplicateRejected);
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
        $gateway->launch(
            $semiId,
            null,
            $modalityId,
            [
                ['id_equipe' => $winner, 'gols' => 0],
                ['id_equipe' => $loser, 'gols' => 2],
            ],
            array_merge(
                self::pointAdjustments($connection, $semiId, $winner, 0),
                self::pointAdjustments($connection, $semiId, $loser, 2),
            ),
            self::operatorId($connection),
        );
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
        $thirdTeam = null;
        $semis = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo LIKE 'MM:4:%'
               AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo",
        )->fetch_all(\MYSQLI_ASSOC);
        foreach ($semis as $semi) {
            $semiTeams = self::scoredTeams($connection, (int) $semi['id_jogo']);
            if (($semiTeams[0]['team'] ?? null) === $finalTeams[0]) {
                $thirdTeam = $semiTeams[1]['team'] ?? null;
                break;
            }
        }
        $thirdClass = $thirdTeam === null ? null : self::teamClass($connection, $thirdTeam);
        $expectedFinal = self::classPoints($connection, array_merge($classes, $newClasses, $thirdClass === null ? [] : [$thirdClass]));
        foreach ($newClasses as $index => $classId) {
            $position = $index + 1;
            $expectedFinal[$classId] = ($expectedFinal[$classId] ?? 0) + self::podiumValues($connection, $editionId)[$position];
        }
        if ($thirdClass !== null) {
            $expectedFinal[$thirdClass] = ($expectedFinal[$thirdClass] ?? 0) + self::podiumValues($connection, $editionId)[3];
        }
        $gateway->launch(
            $finalId,
            'MM:2:0:N',
            $modalityId,
            [
                ['id_equipe' => $finalTeams[0], 'gols' => 2],
                ['id_equipe' => $finalTeams[1], 'gols' => 0],
            ],
            array_merge(
                self::pointAdjustments($connection, $finalId, $finalTeams[0], 2),
                self::pointAdjustments($connection, $finalId, $finalTeams[1], 0),
            ),
            self::operatorId($connection),
        );
        Assertions::assert('Nova final concede o pódio sem duplicar créditos', self::classPoints($connection, array_merge($classes, $newClasses, $thirdClass === null ? [] : [$thirdClass])) === $expectedFinal);
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
            $gateway->launch(
                $finalId,
                'MM:2:0:N',
                $modalityId,
                [
                    ['id_equipe' => $currentWinner, 'gols' => 0],
                    ['id_equipe' => $currentRunner, 'gols' => 2],
                ],
                array_merge(
                    self::pointAdjustments($connection, $finalId, $currentWinner, 0),
                    self::pointAdjustments($connection, $finalId, $currentRunner, 2),
                ),
                self::operatorId($connection),
            );
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

    private static function runAutomaticThirdPlaceScenario(\mysqli $connection, int $editionId, int $modalityId, int $points): void
    {
        $final = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = 'MM:2:0:N'
               AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo DESC LIMIT 1",
        )->fetch_assoc();
        if ($final === null) {
            throw new \RuntimeException('O cenário automático não encontrou a final concluída.');
        }
        $finalId = (int) $final['id_jogo'];
        $finalParts = self::scoredTeams($connection, $finalId);
        $champion = $finalParts[0]['team'];
        $thirdTeam = null;
        $semis = $connection->query(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo LIKE 'MM:4:%'
               AND status_jogo IN ('Concluido', 'Finalizado') ORDER BY id_jogo",
        )->fetch_all(\MYSQLI_ASSOC);
        foreach ($semis as $semi) {
            $teams = self::scoredTeams($connection, (int) $semi['id_jogo']);
            if (($teams[0]['team'] ?? null) === $champion) {
                $thirdTeam = $teams[1]['team'] ?? null;
                break;
            }
        }
        $source = $connection->query(
            'SELECT id_jogo, id_equipe, pontos, ativo FROM pontuacoes_podio WHERE id_interclasse = '
            . $editionId . ' AND id_modalidade = ' . $modalityId . ' AND posicao = 3',
        )->fetch_assoc();
        $posGames = (int) $connection->query(
            "SELECT COUNT(*) FROM jogos WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = 'POS:3:0:N'",
        )->fetch_column();
        Assertions::assert(
            'Terceiro lugar é atribuído automaticamente sem partida física',
            $thirdTeam !== null && $source !== null
            && (int) $source['id_jogo'] === $finalId
            && (int) $source['id_equipe'] === $thirdTeam
            && (int) $source['pontos'] === $points
            && (int) $source['ativo'] === 1
            && $posGames === 0,
        );
    }

    /** @return list<array{team:int,score:int}> */
    private static function scoredTeams(\mysqli $connection, int $gameId): array
    {
        $statement = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY resultado_partida DESC, equipes_id_equipe ASC');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(\MYSQLI_ASSOC);
        $statement->close();
        return array_map(static fn (array $row): array => [
            'team' => (int) $row['equipes_id_equipe'],
            'score' => (int) $row['resultado_partida'],
        ], $rows);
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

    private static function operatorId(\mysqli $connection): int
    {
        $statement = $connection->prepare('SELECT id_usuario FROM usuarios WHERE matricula_usuario = ? LIMIT 1');
        $registration = 'admin';
        $statement->bind_param('s', $registration);
        $statement->execute();
        $operatorId = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $operatorId;
    }

    /** @return list<array<string, mixed>> */
    private static function pointAdjustments(\mysqli $connection, int $gameId, int $teamId, int $targetScore): array
    {
        if ($targetScore < 0) {
            throw new \InvalidArgumentException('O placar de teste não pode ser negativo.');
        }
        $repository = new \App\Modules\Competicoes\Infrastructure\MysqliPontoRepository($connection);
        $activePoints = array_values(array_filter(
            $repository->listarPontos($gameId, $teamId),
            static fn (array $point): bool => ($point['status_artilheiro'] ?? '') === 'ativo'
                && (int) ($point['conta_no_placar'] ?? 0) === 1
                && (int) ($point['partidas_id_partida'] ?? 0) > 0,
        ));
        $currentScore = count($activePoints);
        $match = $connection->prepare('SELECT resultado_partida FROM partidas WHERE jogos_id_jogo = ? AND equipes_id_equipe = ? LIMIT 1');
        $match->bind_param('ii', $gameId, $teamId);
        $match->execute();
        $persistedScore = (int) $match->get_result()->fetch_column();
        $match->close();
        if ($persistedScore !== $currentScore) {
            throw new \RuntimeException('O cenário de pódio iniciou com placar divergente do livro de jogadas.');
        }
        if ($currentScore > $targetScore) {
            return array_map(
                static fn (array $point): array => [
                    'id_ponto' => (int) $point['id_artilheiro'],
                    'id_equipe' => $teamId,
                    'usuarios_id_usuario' => (int) $point['usuarios_id_usuario'],
                    'chave_jogada' => (string) $point['chave_jogada'],
                    'status_artilheiro' => 'anulado',
                ],
                array_slice($activePoints, 0, $currentScore - $targetScore),
            );
        }
        if ($currentScore === $targetScore) {
            return [];
        }
        $athletes = $repository->listarAtletas($gameId, $teamId);
        if ($athletes === []) {
            throw new \RuntimeException('O cenário de pódio não possui atleta elegível para conciliar o placar.');
        }
        $match = $connection->prepare('SELECT id_partida FROM partidas WHERE jogos_id_jogo = ? AND equipes_id_equipe = ? LIMIT 1');
        $match->bind_param('ii', $gameId, $teamId);
        $match->execute();
        $matchId = (int) $match->get_result()->fetch_column();
        $match->close();
        if ($matchId <= 0) {
            throw new \RuntimeException('O cenário de pódio não possui partida para conciliar o placar.');
        }
        $athleteId = (int) $athletes[0]['id_usuario'];
        $points = [];
        for ($index = $currentScore; $index < $targetScore; $index++) {
            $points[] = [
                'jogos_id_jogo' => $gameId,
                'partidas_id_partida' => $matchId,
                'equipes_id_equipe' => $teamId,
                'usuarios_id_usuario' => $athleteId,
                'chave_jogada' => 'podium-ledger-' . $gameId . '-' . $teamId . '-' . $index . '-' . bin2hex(random_bytes(4)),
                'status_artilheiro' => 'ativo',
                'conta_no_placar' => 1,
            ];
        }
        return $points;
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
