<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class TemporaryResolutionScopeTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 0.2: Resolução segura de jogos temporários]\033[0m\n";

        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = AuditFixtures::createAuthorizationFixture($connection);
        $createdGameIds = [];

        try {
            $mesario = new TestClient();
            $mesario->login('mesario', '123');
            $editionA = $fixture['by_edition']['A'];
            $editionB = $fixture['by_edition']['B'];
            $gameB = (int) $editionB['jogo_ids'][0];
            $gameA = (int) $editionA['jogo_ids'][0];
            $modalityA = (int) $editionA['modalidade_id'];
            $modalityB = (int) $editionB['modalidade_id'];
            $teamA1 = (int) $editionA['equipe_ids'][0];
            $teamA2 = (int) $editionA['equipe_ids'][1];
            $teamB1 = (int) $editionB['equipe_ids'][0];
            $teamB2 = (int) $editionB['equipe_ids'][1];

            $beforeB = self::gameSnapshot($connection, $gameB);
            $resultB = $mesario->postJson('api/v1/resultados', [
                'id_jogo' => -501,
                'nome_jogo' => 'MM:2:0:N',
                'id_modalidade' => $modalityB,
                'resultados' => [
                    ['id_equipe' => $teamB1, 'gols' => 2],
                    ['id_equipe' => $teamB2, 'gols' => 0],
                ],
            ]);
            $afterB = self::gameSnapshot($connection, $gameB);
            Assertions::assert(
                'Resultado temporário da edição B é rejeitado sem escrita',
                $resultB['code'] === 403
                && ($resultB['json']['success'] ?? true) === false
                && $afterB === $beforeB,
            );

            $beforeCrossEdition = self::gameSnapshot($connection, $gameB);
            $crossTag = 'MM:2:91:N';
            $crossEdition = $mesario->postJson('api/v1/resultados', [
                'id_jogo' => -502,
                'nome_jogo' => $crossTag,
                'id_modalidade' => $modalityA,
                'resultados' => [
                    ['id_equipe' => $teamB1, 'gols' => 3],
                    ['id_equipe' => $teamB2, 'gols' => 1],
                ],
            ]);
            $afterCrossEdition = self::gameSnapshot($connection, $gameB);
            Assertions::assert(
                'Modalidade A rejeita equipes B sem criar jogo auxiliar',
                $crossEdition['code'] === 422
                && ($crossEdition['json']['success'] ?? true) === false
                && $afterCrossEdition === $beforeCrossEdition
                && self::findGameByName($connection, $crossTag) === null,
            );

            $ambiguousGame = self::createGame(
                $connection,
                (int) $editionA['modalidade_id'],
                (int) $editionA['local_id'],
                'T05 Ambiguous ' . bin2hex(random_bytes(5)),
                $teamA1,
                $teamA2,
            );
            $createdGameIds[] = $ambiguousGame;
            $beforeAmbiguous = self::gameSnapshot($connection, $ambiguousGame);
            $ambiguousTag = 'MM:2:92:N';
            $mutation = ['X-SGI-Mutation-Id' => 't05-ambiguous-' . bin2hex(random_bytes(5))];
            $ambiguous = $mesario->postJson('api/v1/resultados', [
                'id_jogo' => -503,
                'nome_jogo' => $ambiguousTag,
                'id_modalidade' => $modalityA,
                'resultados' => [
                    ['id_equipe' => $teamA1, 'gols' => 4],
                    ['id_equipe' => $teamA2, 'gols' => 1],
                ],
            ], $mutation);
            $afterAmbiguous = self::gameSnapshot($connection, $ambiguousGame);
            Assertions::assert(
                'Fallback ambíguo devolve 409 sem alterar nenhum jogo',
                $ambiguous['code'] === 409
                && ($ambiguous['json']['success'] ?? true) === false
                && $afterAmbiguous === $beforeAmbiguous,
            );
            self::deleteGame($connection, $ambiguousGame);
            $operatorId = self::userId($connection, 'mesario');
            $forgedAuthorId = self::userId($connection, 'admin');
            $offlinePoints = [
                ...self::offlinePoints($teamA1, (int) $editionA['atleta_ids'][0], 4, 'retry-point-a'),
                ...self::offlinePoints($teamA2, (int) $editionA['atleta_ids'][1], 1, 'retry-point-b'),
            ];
            $offlinePoints[0]['registrado_por'] = $forgedAuthorId;
            $offlinePoints[1]['registrado_por'] = 0;
            $offlinePoints[2]['registrado_por'] = null;
            $pointsBeforeFailedRetry = self::pointCount($connection, $gameA);
            $gameBeforeFailedRetry = self::gameSnapshot($connection, $gameA);
            $invalidRetry = $mesario->postJson('api/v1/resultados', [
                'id_jogo' => -503,
                'nome_jogo' => $ambiguousTag,
                'id_modalidade' => $modalityA,
                'resultados' => [
                    ['id_equipe' => $teamA1, 'gols' => 5],
                    ['id_equipe' => $teamA2, 'gols' => 1],
                ],
                'pontos' => $offlinePoints,
            ], $mutation);
            Assertions::assertStatus('Placar incompatível rejeita a sincronização de pontos offline', $invalidRetry, 422);
            Assertions::assert(
                'Falha da sincronização desfaz placar e eventos e deixa a mutação disponível',
                self::gameSnapshot($connection, $gameA) === $gameBeforeFailedRetry
                && self::pointCount($connection, $gameA) === $pointsBeforeFailedRetry
                && self::mutationCount($connection, (string) $mutation['X-SGI-Mutation-Id']) === 0,
            );

            $retryBody = [
                'id_jogo' => -503,
                'nome_jogo' => $ambiguousTag,
                'id_modalidade' => $modalityA,
                'resultados' => [
                    ['id_equipe' => $teamA1, 'gols' => 4],
                    ['id_equipe' => $teamA2, 'gols' => 1],
                ],
                'pontos' => $offlinePoints,
            ];
            $retryAmbiguous = $mesario->postJson('api/v1/resultados', $retryBody, $mutation);
            $retrySnapshot = self::gameSnapshot($connection, $gameA);
            Assertions::assert(
                'Retry do fallback ambíguo permanece disponível na fila',
                ($retryAmbiguous['json']['success'] ?? false) === true
                && ($retrySnapshot['scores'][$teamA1] ?? null) === 4,
                json_encode(['response' => $retryAmbiguous, 'snapshot' => $retrySnapshot], JSON_UNESCAPED_UNICODE),
            );
            $authors = self::pointAuthors($connection, $gameA, 'retry-point-');
            Assertions::assert(
                'Pontos sincronizados ignoram autoria forjada, zero, nula ou ausente',
                count($authors) === 5
                && array_column($authors, 'registrado_por') === array_fill(0, 5, $operatorId)
                && $forgedAuthorId !== $operatorId,
                json_encode($authors, JSON_UNESCAPED_UNICODE),
            );
            $retryReplay = $mesario->postJson('api/v1/resultados', $retryBody, $mutation);
            Assertions::assert(
                'Replay do resultado conserva resposta, pontos e placar sem duplicação',
                $retryReplay['json'] === $retryAmbiguous['json']
                && self::pointCount($connection, $gameA) === 5
                && self::mutationCount($connection, (string) $mutation['X-SGI-Mutation-Id']) === 1
                && self::gameSnapshot($connection, $gameA) === $retrySnapshot,
            );

            $syncName = 'T05 Sync ' . bin2hex(random_bytes(5));
            $sync = $mesario->postJson('api/v1/sincronizacao/chaveamento', [
                'id_modalidade' => $modalityA,
                'tipo_modalidade' => 'mata_mata',
                'jogos' => [[
                    'nome_jogo' => $syncName,
                    'status_jogo' => 'Agendado',
                    'partidas' => [
                        ['id_equipe' => $teamA1, 'resultado' => 0],
                        ['id_equipe' => $teamB1, 'resultado' => 0],
                    ],
                ]],
            ]);
            $syncGame = self::findGameByName($connection, $syncName);
            if ($syncGame !== null) {
                $createdGameIds[] = $syncGame;
            }
            Assertions::assert(
                'Sincronização rejeita equipe fora da modalidade sem criação auxiliar',
                $sync['code'] === 400
                && ($sync['json']['success'] ?? true) === false
                && $syncGame === null,
            );
        } finally {
            foreach (array_unique($createdGameIds) as $gameId) {
                self::deleteGame($connection, (int) $gameId);
            }
            $connection->close();
            AuditFixtures::restoreAndRemove(
                TestDatabase::connect($database),
                $fixture,
                $previousActiveId,
            );
        }
    }

    /** @return array{status:string,scores:array<int,int>} */
    private static function gameSnapshot(\mysqli $connection, int $gameId): array
    {
        $statusStatement = $connection->prepare('SELECT status_jogo FROM jogos WHERE id_jogo = ? LIMIT 1');
        $statusStatement->bind_param('i', $gameId);
        $statusStatement->execute();
        $status = (string) ($statusStatement->get_result()->fetch_column() ?: '');
        $statusStatement->close();

        $scoreStatement = $connection->prepare(
            'SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY id_partida',
        );
        $scoreStatement->bind_param('i', $gameId);
        $scoreStatement->execute();
        $rows = $scoreStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $scoreStatement->close();
        $scores = [];
        foreach ($rows as $row) {
            $scores[(int) $row['equipes_id_equipe']] = (int) $row['resultado_partida'];
        }
        return ['status' => $status, 'scores' => $scores];
    }

    private static function createGame(\mysqli $connection, int $modalityId, int $localId, string $name, int $teamA, int $teamB): int
    {
        $statement = $connection->prepare(
            "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)",
        );
        $statement->bind_param('sii', $name, $modalityId, $localId);
        $statement->execute();
        $gameId = (int) $connection->insert_id;
        $statement->close();
        foreach ([$teamA, $teamB] as $teamId) {
            $partida = $connection->prepare(
                "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')",
            );
            $partida->bind_param('ii', $gameId, $teamId);
            $partida->execute();
            $partida->close();
        }
        return $gameId;
    }

    private static function findGameByName(\mysqli $connection, string $name): ?int
    {
        $statement = $connection->prepare('SELECT id_jogo FROM jogos WHERE nome_jogo = ? ORDER BY id_jogo DESC LIMIT 1');
        $statement->bind_param('s', $name);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return $value === null || $value === false ? null : (int) $value;
    }

    private static function countParts(\mysqli $connection, int $gameId): int
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM partidas WHERE jogos_id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private static function userId(\mysqli $connection, string $registration): int
    {
        $statement = $connection->prepare('SELECT id_usuario FROM usuarios WHERE matricula_usuario = ? LIMIT 1');
        $statement->bind_param('s', $registration);
        $statement->execute();
        $id = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $id;
    }

    private static function pointCount(\mysqli $connection, int $gameId): int
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM artilheiros WHERE jogos_id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    /** @return list<array{chave_jogada:string,registrado_por:?int,anulado_por:?int}> */
    private static function pointAuthors(\mysqli $connection, int $gameId, string $prefix): array
    {
        $statement = $connection->prepare(
            'SELECT chave_jogada, registrado_por, anulado_por
             FROM artilheiros
             WHERE jogos_id_jogo = ? AND chave_jogada LIKE ?
             ORDER BY chave_jogada',
        );
        $like = $prefix . '%';
        $statement->bind_param('is', $gameId, $like);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return array_map(static fn (array $row): array => [
            'chave_jogada' => (string) $row['chave_jogada'],
            'registrado_por' => $row['registrado_por'] === null ? null : (int) $row['registrado_por'],
            'anulado_por' => $row['anulado_por'] === null ? null : (int) $row['anulado_por'],
        ], $rows);
    }

    private static function mutationCount(\mysqli $connection, string $key): int
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM sincronizacoes_idempotentes WHERE rota = \'lancar_resultado\' AND chave_mutacao = ?');
        $statement->bind_param('s', $key);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private static function deleteGame(\mysqli $connection, int $gameId): void
    {
        $partidas = $connection->prepare('DELETE FROM partidas WHERE jogos_id_jogo = ?');
        $partidas->bind_param('i', $gameId);
        $partidas->execute();
        $partidas->close();
        $jogo = $connection->prepare('DELETE FROM jogos WHERE id_jogo = ?');
        $jogo->bind_param('i', $gameId);
        $jogo->execute();
        $jogo->close();
    }

    /** @return list<array<string, int|string>> */
    private static function offlinePoints(int $teamId, int $athleteId, int $quantity, string $prefix): array
    {
        $points = [];
        for ($index = 1; $index <= $quantity; $index++) {
            $points[] = [
                'id_equipe' => $teamId,
                'usuarios_id_usuario' => $athleteId,
                'chave_jogada' => $prefix . '-' . $index,
                'status_artilheiro' => 'ativo',
            ];
        }
        return $points;
    }
}
