<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class MesarioResourceScopeTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 0.1: Escopo de recurso do mesário]\033[0m\n";

        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = AuditFixtures::createAuthorizationFixture($connection);
        $editionB = $fixture['by_edition']['B'];
        $userId = (int) $editionB['atleta_ids'][0];
        $gameId = (int) $editionB['jogo_ids'][0];
        $partidaIdB = (int) $editionB['partida_ids'][0];
        $teamIdB = (int) $editionB['equipe_ids'][0];

        $activateAthlete = $connection->prepare("UPDATE usuarios SET status_usuario = '1' WHERE id_usuario = ?");
        $activateAthlete->bind_param('i', $userId);
        $activateAthlete->execute();
        $activateAthlete->close();
        $startGame = $connection->prepare("UPDATE jogos SET status_jogo = 'Iniciado' WHERE id_jogo = ?");
        $startGame->bind_param('i', $gameId);
        $startGame->execute();
        $startGame->close();

        $seedKey = 'scope-fixture-' . bin2hex(random_bytes(8));
        $seedPoint = $connection->prepare(
            "INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, partidas_id_partida,
                equipes_id_equipe, num_gol, conta_no_placar, status_artilheiro, chave_jogada)
             VALUES (?, ?, ?, ?, 1, 1, 'ativo', ?)",
        );
        $seedPoint->bind_param('iiiis', $userId, $gameId, $partidaIdB, $teamIdB, $seedKey);
        $seedPoint->execute();
        $pointIdB = (int) $seedPoint->insert_id;
        $seedPoint->close();
        $scorePoint = $connection->prepare('UPDATE partidas SET resultado_partida = 1 WHERE id_partida = ?');
        $scorePoint->bind_param('i', $partidaIdB);
        $scorePoint->execute();
        $scorePoint->close();

        $before = self::countGoals($connection, $userId, $gameId);

        try {
            $mesario = new TestClient();
            $mesario->login('mesario', '123');

            $beforeBMatch = self::partida($connection, $partidaIdB);
            $pointsB = $mesario->get('api/v1/pontos?id_jogo=' . $gameId);
            $athletesByGameB = $mesario->get('api/v1/pontos?acao=atletas&id_jogo=' . $gameId . '&id_equipe=' . $teamIdB);
            $athletesByTeamB = $mesario->get('api/v1/pontos?acao=atletas&id_equipe=' . $teamIdB);
            $createPointB = $mesario->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $gameId,
                'id_partida' => $partidaIdB,
                'equipes_id_equipe' => $teamIdB,
                'usuarios_id_usuario' => $userId,
                'chave_jogada' => 'scope-point-' . bin2hex(random_bytes(8)),
            ]);
            $cancelPointB = $mesario->putJson('api/v1/pontos', ['id_ponto' => $pointIdB]);
            $afterBMatch = self::partida($connection, $partidaIdB);

            Assertions::assert(
                'Mesário não consulta pontos da edição B quando A está ativa',
                $pointsB['code'] === 403 && ($pointsB['json']['success'] ?? true) === false,
            );
            Assertions::assert(
                'Mesário não consulta atletas de B por jogo nem por equipe',
                $athletesByGameB['code'] === 403
                && ($athletesByGameB['json']['success'] ?? true) === false
                && $athletesByTeamB['code'] === 403
                && ($athletesByTeamB['json']['success'] ?? true) === false,
            );
            Assertions::assert(
                'Mesário não registra nem anula pontos da edição B',
                $createPointB['code'] === 403
                && ($createPointB['json']['success'] ?? true) === false
                && $cancelPointB['code'] === 403
                && ($cancelPointB['json']['success'] ?? true) === false
                && self::countGoals($connection, $userId, $gameId) === $before
                && $afterBMatch === $beforeBMatch
                && self::pointStatus($connection, $pointIdB) === 'ativo',
            );

            $admin = new TestClient();
            $admin->login('admin', '123');
            $adminPointsB = $admin->get('api/v1/pontos?id_jogo=' . $gameId);
            $collaborator = new TestClient();
            $collaborator->login('colab', '123');
            $collaboratorPointsB = $collaborator->get('api/v1/pontos?id_jogo=' . $gameId);
            Assertions::assert(
                'Administrador e colaborador mantêm acesso à consulta de pontos de outra edição',
                $adminPointsB['code'] === 200
                && ($adminPointsB['json']['success'] ?? false) === true
                && $collaboratorPointsB['code'] === 200
                && ($collaboratorPointsB['json']['success'] ?? false) === true,
            );

            $gameAForMismatch = (int) $fixture['by_edition']['A']['jogo_ids'][0];
            $mismatchedPoints = $mesario->get('api/v1/pontos?id_jogo=' . $gameAForMismatch . '&id_equipe=' . $teamIdB);
            $mismatchedAthletes = $mesario->get('api/v1/pontos?acao=atletas&id_jogo=' . $gameAForMismatch . '&id_equipe=' . $teamIdB);
            Assertions::assert(
                'Combinações de jogo e equipe de edições diferentes são rejeitadas',
                $mismatchedPoints['code'] === 422
                && $mismatchedAthletes['code'] === 422,
            );

            $missingGame = max($fixture['ids']['jogos']) + 100000;
            $missingPoints = $mesario->get('api/v1/pontos?id_jogo=' . $missingGame);
            Assertions::assert(
                'Jogo inexistente tem resposta distinta do recurso proibido',
                $missingPoints['code'] === 404
                && ($missingPoints['json']['success'] ?? true) === false,
            );

            $editionAId = (int) $fixture['by_edition']['A']['interclasse_id'];
            $disableEdition = $connection->prepare("UPDATE interclasses SET status_interclasse = '0' WHERE id_interclasse = ?");
            $disableEdition->bind_param('i', $editionAId);
            $disableEdition->execute();
            $disableEdition->close();
            $noActiveEdition = $mesario->get('api/v1/pontos?id_jogo=' . $gameId);
            $restoreEdition = $connection->prepare("UPDATE interclasses SET status_interclasse = '1' WHERE id_interclasse = ?");
            $restoreEdition->bind_param('i', $editionAId);
            $restoreEdition->execute();
            $restoreEdition->close();
            Assertions::assert(
                'Mesário sem edição ativa continua sem consultar pontos',
                $noActiveEdition['code'] === 403 && ($noActiveEdition['json']['success'] ?? true) === false,
            );

            $student = new TestClient();
            $student->login('2879', '123');
            $studentPointsB = $student->get('api/v1/pontos?id_jogo=' . $gameId);
            Assertions::assert(
                'Aluno continua sem acesso ao endpoint administrativo de pontos',
                $studentPointsB['code'] === 403 && ($studentPointsB['json']['success'] ?? true) === false,
            );

            $response = $mesario->postJson('api/v1/artilheiros', [
                'usuarios_id_usuario' => $userId,
                'jogos_id_jogo' => $gameId,
                'num_gol' => 1,
            ]);

            Assertions::assert(
                'Mesário não grava artilharia da edição B quando A está ativa',
                $response['code'] === 403
                && ($response['json']['success'] ?? true) === false
                && self::countGoals($connection, $userId, $gameId) === $before,
            );

            $beforeOccurrences = self::countOccurrences($connection, $userId);
            $occurrence = $mesario->postJson('api/v1/ocorrencias', [
                'titulo_ocorrencia' => 'Amarelo',
                'descricao_ocorrencia' => 'Fixture fora da edição ativa',
                'data_ocorrencia' => '2026-09-07',
                'usuarios_id_usuario' => $userId,
                'id_jogo' => $gameId,
                'id_turma' => (int) $editionB['turma_ids'][0],
                'penalidade' => 0,
            ]);
            Assertions::assert(
                'Mesário não grava ocorrência da edição B quando A está ativa',
                $occurrence['code'] === 403
                && ($occurrence['json']['success'] ?? true) === false
                && self::countOccurrences($connection, $userId) === $beforeOccurrences,
            );

            $partidaId = (int) $editionB['partida_ids'][0];
            $gameA = (int) $fixture['by_edition']['A']['jogo_ids'][0];
            $teamB = (int) $editionB['equipe_ids'][0];
            $beforePartida = self::partida($connection, $partidaId);
            $partidaResponse = $mesario->putJson('api/v1/partidas', [
                'id_partida' => $partidaId,
                'jogos_id_jogo' => $gameA,
                'equipes_id_equipe' => $teamB,
                'resultado_partida' => 7,
            ]);
            $afterPartida = self::partida($connection, $partidaId);
            Assertions::assert(
                'Partida B rejeita jogo de A pela edição persistida sem reassociação',
                $partidaResponse['code'] === 403
                && ($partidaResponse['json']['success'] ?? true) === false
                && $afterPartida === $beforePartida,
            );

            $partidaA = (int) $fixture['by_edition']['A']['partida_ids'][0];
            $teamA2 = (int) $fixture['by_edition']['A']['equipe_ids'][1];
            $beforePartidaA = self::partida($connection, $partidaA);
            $discordant = $mesario->putJson('api/v1/partidas', [
                'id_partida' => $partidaA,
                'jogos_id_jogo' => $gameA,
                'equipes_id_equipe' => $teamA2,
                'resultado_partida' => 8,
            ]);
            Assertions::assert(
                'Partida de A rejeita equipe discordante sem reassociação',
                $discordant['code'] === 422
                && ($discordant['json']['success'] ?? true) === false
                && self::partida($connection, $partidaA) === $beforePartidaA,
            );

            $missing = $mesario->putJson('api/v1/partidas', [
                'id_partida' => max($fixture['ids']['partidas']) + 100000,
                'resultado_partida' => 2,
            ]);
            Assertions::assert(
                'Alteração direta de partida inexistente é rejeitada pelo contrato do placar',
                $missing['code'] === 422 && ($missing['json']['success'] ?? true) === false,
            );

            $inferred = $mesario->putJson('api/v1/partidas', [
                'id_partida' => $partidaA,
                'resultado_partida' => 2,
            ]);
            $afterInferred = self::partida($connection, $partidaA);
            Assertions::assert(
                'PUT de partida não altera o placar sem uma jogada vinculada',
                $inferred['code'] === 422
                && ($inferred['json']['success'] ?? true) === false
                && $afterInferred['jogos_id_jogo'] === $gameA
                && $afterInferred['equipes_id_equipe'] === (int) $fixture['by_edition']['A']['equipe_ids'][0]
                && $afterInferred['resultado_partida'] === 0,
            );

            $schedule = $connection->prepare("UPDATE jogos SET termino_jogo = '09:00:00' WHERE id_jogo = ?");
            $schedule->bind_param('i', $gameA);
            $schedule->execute();
            $schedule->close();
            $started = $mesario->putJson('api/v1/jogos', [
                'id_jogo' => $gameA,
                'status_jogo' => 'Iniciado',
                'duracao_jogo' => 1200,
                'tempo_restante_jogo' => 1200,
            ]);
            $point = $mesario->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $gameA,
                'id_partida' => $partidaA,
                'equipes_id_equipe' => (int) $fixture['by_edition']['A']['equipe_ids'][0],
                'usuarios_id_usuario' => (int) $fixture['by_edition']['A']['atleta_ids'][0],
                'chave_jogada' => 'mesario-scope-point-' . bin2hex(random_bytes(5)),
            ]);
            $finalized = $mesario->postJson('api/v1/resultados', [
                'id_jogo' => $gameA,
                'id_modalidade' => (int) $fixture['by_edition']['A']['modalidade_id'],
                'resultados' => [
                    ['id_equipe' => (int) $fixture['by_edition']['A']['equipe_ids'][0], 'gols' => 1],
                    ['id_equipe' => $teamA2, 'gols' => 0],
                ],
            ]);
            $scores = self::scores($connection, $gameA);
            Assertions::assert(
                'Ponto vinculado permite finalizar a partida pelo resultado completo',
                ($started['json']['success'] ?? false) === true
                && ($point['json']['success'] ?? false) === true
                && $finalized['code'] === 200
                && ($finalized['json']['success'] ?? false) === true
                && $scores === [
                    (int) $fixture['by_edition']['A']['equipe_ids'][0] => 1,
                    $teamA2 => 0,
                ]
                && self::gameStatus($connection, $gameA) === 'Concluido',
                json_encode([
                    'started' => $started,
                    'point' => $point,
                    'finalized' => $finalized,
                    'scores' => $scores,
                ], JSON_UNESCAPED_UNICODE),
            );
        } finally {
            $connection->close();
            AuditFixtures::restoreAndRemove(
                TestDatabase::connect($database),
                $fixture,
                $previousActiveId,
            );
        }
    }

    private static function countGoals(\mysqli $connection, int $userId, int $gameId): int
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM artilheiros WHERE usuarios_id_usuario = ? AND jogos_id_jogo = ?',
        );
        $statement->bind_param('ii', $userId, $gameId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private static function countOccurrences(\mysqli $connection, int $userId): int
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM ocorrencias WHERE usuarios_id_usuario = ?');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private static function pointStatus(\mysqli $connection, int $pointId): string
    {
        $statement = $connection->prepare('SELECT status_artilheiro FROM artilheiros WHERE id_artilheiro = ? LIMIT 1');
        $statement->bind_param('i', $pointId);
        $statement->execute();
        $status = (string) ($statement->get_result()->fetch_column() ?: '');
        $statement->close();
        return $status;
    }

    /** @return array{jogos_id_jogo:int,equipes_id_equipe:int,resultado_partida:int} */
    private static function partida(\mysqli $connection, int $partidaId): array
    {
        $statement = $connection->prepare(
            'SELECT jogos_id_jogo, equipes_id_equipe, resultado_partida FROM partidas WHERE id_partida = ? LIMIT 1',
        );
        $statement->bind_param('i', $partidaId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        return [
            'jogos_id_jogo' => (int) ($row['jogos_id_jogo'] ?? 0),
            'equipes_id_equipe' => (int) ($row['equipes_id_equipe'] ?? 0),
            'resultado_partida' => (int) ($row['resultado_partida'] ?? 0),
        ];
    }

    /** @return array<int, int> */
    private static function scores(\mysqli $connection, int $gameId): array
    {
        $statement = $connection->prepare(
            'SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida',
        );
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        $scores = [];
        foreach ($rows as $row) {
            $scores[(int) $row['equipes_id_equipe']] = (int) $row['resultado_partida'];
        }
        return $scores;
    }

    private static function gameStatus(\mysqli $connection, int $gameId): string
    {
        $statement = $connection->prepare('SELECT status_jogo FROM jogos WHERE id_jogo = ? LIMIT 1');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $status = (string) ($statement->get_result()->fetch_column() ?: '');
        $statement->close();
        return $status;
    }
}
