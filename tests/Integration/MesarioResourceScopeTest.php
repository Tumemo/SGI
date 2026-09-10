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
        $before = self::countGoals($connection, $userId, $gameId);

        try {
            $mesario = new TestClient();
            $mesario->login('mesario', '123');
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
