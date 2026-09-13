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
        $createdTeamIds = [];
        $mutationKeys = [];

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

            self::assertCollectiveSyncValidation(
                $mesario,
                $connection,
                $modalityA,
                $modalityB,
                $teamA1,
                $teamA2,
                $teamB1,
                $teamB2,
                (int) $editionA['local_id'],
                $createdGameIds,
                $createdTeamIds,
                $mutationKeys,
            );
        } finally {
            foreach (array_unique($createdGameIds) as $gameId) {
                self::deleteGame($connection, (int) $gameId);
            }
            foreach (array_unique($createdTeamIds) as $teamId) {
                $deleteTeam = $connection->prepare('DELETE FROM equipes WHERE id_equipe = ?');
                $deleteTeam->bind_param('i', $teamId);
                $deleteTeam->execute();
                $deleteTeam->close();
            }
            foreach (array_unique($mutationKeys) as $mutationKey) {
                $deleteMutation = $connection->prepare('DELETE FROM sincronizacoes_idempotentes WHERE chave_mutacao = ?');
                $deleteMutation->bind_param('s', $mutationKey);
                $deleteMutation->execute();
                $deleteMutation->close();
            }
            $connection->close();
            AuditFixtures::restoreAndRemove(
                TestDatabase::connect($database),
                $fixture,
                $previousActiveId,
            );
        }
    }

    /**
     * @param list<int> $createdGameIds
     * @param list<int> $createdTeamIds
     * @param list<string> $mutationKeys
     */
    private static function assertCollectiveSyncValidation(
        TestClient $mesario,
        \mysqli $connection,
        int $modalityA,
        int $modalityB,
        int $teamA1,
        int $teamA2,
        int $teamB1,
        int $teamB2,
        int $localId,
        array &$createdGameIds,
        array &$createdTeamIds,
        array &$mutationKeys,
    ): void {
        $endpoint = 'api/v1/sincronizacao/chaveamento';
        $base = ['id_modalidade' => $modalityA, 'tipo_modalidade' => 'mata_mata'];

        $zeroTag = 'MM:2:0:N';
        $zeroBody = $base + ['jogos' => [[
            'nome_jogo' => $zeroTag,
            'status_jogo' => 'Concluido',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]];
        $zeroKey = self::newSyncMutationKey('zero');
        $mutationKeys[] = $zeroKey;
        $zeroResponse = $mesario->postJson($endpoint, $zeroBody, ['X-SGI-Mutation-Id' => $zeroKey]);
        $zeroGame = self::findGameByName($connection, $zeroTag);
        if ($zeroGame !== null) {
            $createdGameIds[] = $zeroGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita final 0x0 antes de gravar jogo ou mutação',
            $zeroResponse['code'] === 400 && $zeroGame === null && self::syncMutationCount($connection, $zeroKey) === 0,
            json_encode($zeroResponse, JSON_UNESCAPED_UNICODE),
        );
        if ($zeroGame !== null) {
            self::deleteGame($connection, $zeroGame);
        }

        $tieTag = 'MM:2:0:N';
        $tieSetup = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $tieTag,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $tieGame = self::findGameByName($connection, $tieTag);
        if ($tieGame !== null) {
            $createdGameIds[] = $tieGame;
        }
        $tieParts = $tieGame === null ? [] : $mesario->get('api/v1/partidas?id_jogo=' . $tieGame)['json'];
        $tiePartByTeam = [];
        foreach (is_array($tieParts) ? $tieParts : [] as $tiePart) {
            $tiePartByTeam[(int) ($tiePart['equipes_id_equipe'] ?? 0)] = (int) ($tiePart['id_partida'] ?? 0);
        }
        if ($tieGame !== null) {
            $connection->query(
                "UPDATE jogos SET data_jogo = CURDATE(), inicio_jogo = '08:00:00', termino_jogo = '08:20:00', locais_id_local = {$localId} WHERE id_jogo = {$tieGame}",
            );
        }
        $tieAthleteA = self::teamAthlete($connection, $teamA1);
        $tieAthleteB = self::teamAthlete($connection, $teamA2);
        $tieStarted = $tieGame === null ? ['json' => []] : $mesario->putJson('api/v1/jogos', [
            'id_jogo' => $tieGame,
            'status_jogo' => 'Iniciado',
            'duracao_jogo' => 1200,
            'tempo_restante_jogo' => 1200,
        ]);
        $tiePointA = $mesario->postJson('api/v1/pontos', [
            'jogos_id_jogo' => $tieGame ?? 0,
            'id_partida' => $tiePartByTeam[$teamA1] ?? 0,
            'equipes_id_equipe' => $teamA1,
            'usuarios_id_usuario' => $tieAthleteA,
            'chave_jogada' => 'l08-tie-a-' . bin2hex(random_bytes(5)),
        ]);
        $tiePointB = $mesario->postJson('api/v1/pontos', [
            'jogos_id_jogo' => $tieGame ?? 0,
            'id_partida' => $tiePartByTeam[$teamA2] ?? 0,
            'equipes_id_equipe' => $teamA2,
            'usuarios_id_usuario' => $tieAthleteB,
            'chave_jogada' => 'l08-tie-b-' . bin2hex(random_bytes(5)),
        ]);
        $tieReady = $tieSetup['code'] === 200
            && $tieGame !== null
            && ($tieStarted['json']['success'] ?? false) === true
            && ($tiePointA['json']['success'] ?? false) === true
            && ($tiePointB['json']['success'] ?? false) === true;
        $tieDetails = json_encode([
            'setup' => $tieSetup,
            'jogo' => $tieGame,
            'partidas' => $tieParts,
            'atletas' => [$tieAthleteA, $tieAthleteB],
            'inicio' => $tieStarted,
            'ponto_a' => $tiePointA,
            'ponto_b' => $tiePointB,
        ], JSON_UNESCAPED_UNICODE);
        Assertions::assert('Fixture da final empatada tem duas jogadas válidas vinculadas', $tieReady, $tieDetails ?: '');
        $tieKey = self::newSyncMutationKey('tie');
        $mutationKeys[] = $tieKey;
        $tieResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $tieTag,
            'status_jogo' => 'Concluido',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 1],
                ['id_equipe' => $teamA2, 'resultado' => 1],
            ],
        ]]], ['X-SGI-Mutation-Id' => $tieKey]);
        Assertions::assert(
            'Sincronização coletiva rejeita empate em final com placar individual consistente',
            $tieReady
            && $tieResponse['code'] === 400
            && $tieGame !== null
            && self::gameSnapshot($connection, $tieGame)['status'] === 'Iniciado'
            && self::syncMutationCount($connection, $tieKey) === 0,
            json_encode(['resposta' => $tieResponse, 'fixture' => $tieDetails], JSON_UNESCAPED_UNICODE),
        );
        if ($tieGame !== null) {
            self::deleteGame($connection, $tieGame);
        }

        $fractionName = 'T08 Fraction ' . bin2hex(random_bytes(5));
        $fractionResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $fractionName,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0.5],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $fractionGame = self::findGameByName($connection, $fractionName);
        if ($fractionGame !== null) {
            $createdGameIds[] = $fractionGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita score fracionário sem truncar e gravar',
            $fractionResponse['code'] === 400 && $fractionGame === null,
            json_encode($fractionResponse, JSON_UNESCAPED_UNICODE),
        );

        $invalidTag = 'MM:2:0:X';
        $invalidTagResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $invalidTag,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $invalidTagGame = self::findGameByName($connection, $invalidTag);
        if ($invalidTagGame !== null) {
            $createdGameIds[] = $invalidTagGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita tag estrutural malformada',
            $invalidTagResponse['code'] === 400 && $invalidTagGame === null,
            json_encode($invalidTagResponse, JSON_UNESCAPED_UNICODE),
        );

        $invalidSlot = 'MM:4:2:N';
        $invalidSlotResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $invalidSlot,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $invalidSlotGame = self::findGameByName($connection, $invalidSlot);
        if ($invalidSlotGame !== null) {
            $createdGameIds[] = $invalidSlotGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita slot fora da topologia da fase',
            $invalidSlotResponse['code'] === 400 && $invalidSlotGame === null,
            json_encode($invalidSlotResponse, JSON_UNESCAPED_UNICODE),
        );

        $thirdTeam = self::createTeam($connection, $modalityA, $teamA1);
        $createdTeamIds[] = $thirdTeam;
        $extraTag = 'MM:4:0:N';
        $extraResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $extraTag,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
                ['id_equipe' => $thirdTeam, 'resultado' => 0],
            ],
        ]]]);
        $extraGame = self::findGameByName($connection, $extraTag);
        if ($extraGame !== null) {
            $createdGameIds[] = $extraGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita equipe extra em jogo normal',
            $extraResponse['code'] === 400 && $extraGame === null,
            json_encode($extraResponse, JSON_UNESCAPED_UNICODE),
        );

        $invalidIdName = 'T08 Invalid ID ' . bin2hex(random_bytes(5));
        $invalidIdResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $invalidIdName,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1 . 'suffix', 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        Assertions::assert(
            'Sincronização rejeita ID de equipe com sufixo sem truncar e materializar jogo',
            $invalidIdResponse['code'] === 400 && self::findGameByName($connection, $invalidIdName) === null,
            json_encode($invalidIdResponse, JSON_UNESCAPED_UNICODE),
        );

        $ambiguousName = 'T08 Duplicate ' . bin2hex(random_bytes(5));
        $ambiguousGameA = self::createGame($connection, $modalityA, $localId, $ambiguousName, $teamA1, $teamA2);
        $ambiguousGameB = self::createGame($connection, $modalityA, $localId, $ambiguousName, $teamA1, $teamA2);
        $createdGameIds[] = $ambiguousGameA;
        $createdGameIds[] = $ambiguousGameB;
        $ambiguousBeforeA = self::gameSnapshot($connection, $ambiguousGameA);
        $ambiguousBeforeB = self::gameSnapshot($connection, $ambiguousGameB);
        $ambiguousKey = self::newSyncMutationKey('ambiguous-tag');
        $mutationKeys[] = $ambiguousKey;
        $ambiguousResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $ambiguousName,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]], ['X-SGI-Mutation-Id' => $ambiguousKey]);
        Assertions::assert(
            'Sincronização recusa tag duplicada sem escolher jogo arbitrariamente nem confirmar mutação',
            $ambiguousResponse['code'] === 400
            && self::gameSnapshot($connection, $ambiguousGameA) === $ambiguousBeforeA
            && self::gameSnapshot($connection, $ambiguousGameB) === $ambiguousBeforeB
            && self::syncMutationCount($connection, $ambiguousKey) === 0,
            json_encode($ambiguousResponse, JSON_UNESCAPED_UNICODE),
        );

        $missingName = 'T08 Missing ' . bin2hex(random_bytes(5));
        $missingGame = self::createGame($connection, $modalityA, $localId, $missingName, $teamA1, $teamA2);
        $createdGameIds[] = $missingGame;
        $missingBefore = self::gameSnapshot($connection, $missingGame);
        $missingKey = self::newSyncMutationKey('missing');
        $mutationKeys[] = $missingKey;
        $missingResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $missingName,
            'status_jogo' => 'Iniciado',
            'partidas' => [['id_equipe' => $teamA1, 'resultado' => 0]],
        ]]], ['X-SGI-Mutation-Id' => $missingKey]);
        Assertions::assert(
            'Sincronização coletiva não permite remover participante persistido nem concluir mutação',
            $missingResponse['code'] === 400
            && self::gameSnapshot($connection, $missingGame) === $missingBefore
            && self::syncMutationCount($connection, $missingKey) === 0,
            json_encode($missingResponse, JSON_UNESCAPED_UNICODE),
        );

        $terminalName = 'T08 Terminal ' . bin2hex(random_bytes(5));
        $terminalGame = self::createGame($connection, $modalityA, $localId, $terminalName, $teamA1, $teamA2);
        $createdGameIds[] = $terminalGame;
        $connection->query("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = " . $terminalGame);
        $terminalBefore = self::gameSnapshot($connection, $terminalGame);
        $reopenResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $terminalName,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        Assertions::assert(
            'Sincronização coletiva não reabre jogo terminal',
            $reopenResponse['code'] === 400 && self::gameSnapshot($connection, $terminalGame) === $terminalBefore,
            json_encode($reopenResponse, JSON_UNESCAPED_UNICODE),
        );

        $invalidStatusName = 'T08 Invalid Status ' . bin2hex(random_bytes(5));
        $invalidStatus = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $invalidStatusName,
            'status_jogo' => 'Desconhecido',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $invalidStatusGame = self::findGameByName($connection, $invalidStatusName);
        if ($invalidStatusGame !== null) {
            $createdGameIds[] = $invalidStatusGame;
        }
        Assertions::assert(
            'Sincronização coletiva rejeita estado fora do contrato',
            $invalidStatus['code'] === 400 && $invalidStatusGame === null,
            json_encode($invalidStatus, JSON_UNESCAPED_UNICODE),
        );

        $batchFirst = 'T08 Batch First ' . bin2hex(random_bytes(5));
        $batchSecond = 'T08 Batch Second ' . bin2hex(random_bytes(5));
        $batchKey = self::newSyncMutationKey('batch');
        $mutationKeys[] = $batchKey;
        $batchResponse = $mesario->postJson($endpoint, $base + ['jogos' => [
            [
                'nome_jogo' => $batchFirst,
                'status_jogo' => 'Agendado',
                'partidas' => [
                    ['id_equipe' => $teamA1, 'resultado' => 0],
                    ['id_equipe' => $teamA2, 'resultado' => 0],
                ],
            ],
            [
                'nome_jogo' => $batchSecond,
                'status_jogo' => 'Agendado',
                'partidas' => [
                    ['id_equipe' => $teamA1, 'resultado' => -1],
                    ['id_equipe' => $teamA2, 'resultado' => 0],
                ],
            ],
        ]], ['X-SGI-Mutation-Id' => $batchKey]);
        $batchFirstGame = self::findGameByName($connection, $batchFirst);
        $batchSecondGame = self::findGameByName($connection, $batchSecond);
        foreach ([$batchFirstGame, $batchSecondGame] as $batchGame) {
            if ($batchGame !== null) {
                $createdGameIds[] = $batchGame;
            }
        }
        Assertions::assert(
            'Falha no segundo jogo reverte o primeiro e deixa a mutação sem confirmação',
            $batchResponse['code'] === 400
            && $batchFirstGame === null
            && $batchSecondGame === null
            && self::syncMutationCount($connection, $batchKey) === 0,
            json_encode($batchResponse, JSON_UNESCAPED_UNICODE),
        );

        $legacyName = 'T08 Legacy ' . bin2hex(random_bytes(5));
        $legacyResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $legacyName,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
            ],
        ]]]);
        $legacyGame = self::findGameByName($connection, $legacyName);
        if ($legacyGame !== null) {
            $createdGameIds[] = $legacyGame;
        }
        Assertions::assert(
            'Sincronização continua aceitando nome legado customizado válido',
            $legacyResponse['code'] === 200 && $legacyGame !== null && self::countParts($connection, $legacyGame) === 2,
            json_encode($legacyResponse, JSON_UNESCAPED_UNICODE),
        );

        $byeNames = ['MM:4:0:B', 'MM:4:1:B'];
        $byeBody = $base + ['jogos' => [
            [
                'nome_jogo' => $byeNames[0],
                'status_jogo' => 'Finalizado',
                'partidas' => [['id_equipe' => $teamA1, 'resultado' => 0]],
            ],
            [
                'nome_jogo' => $byeNames[1],
                'status_jogo' => 'Concluido',
                'partidas' => [['id_equipe' => $teamA2, 'resultado' => 0]],
            ],
        ]];
        $byeKey = self::newSyncMutationKey('bye');
        $mutationKeys[] = $byeKey;
        $byeResponse = $mesario->postJson($endpoint, $byeBody, ['X-SGI-Mutation-Id' => $byeKey]);
        foreach ($byeNames as $byeName) {
            $byeGame = self::findGameByName($connection, $byeName);
            if ($byeGame !== null) {
                $createdGameIds[] = $byeGame;
            }
        }
        $byeParent = self::findGameByName($connection, 'MM:2:0:N');
        if ($byeParent !== null) {
            $createdGameIds[] = $byeParent;
        }
        $byeReady = $byeResponse['code'] === 200
            && count(array_filter($byeNames, static fn (string $name): bool => self::findGameByName($connection, $name) !== null)) === 2
            && $byeParent !== null
            && self::countParts($connection, $byeParent) === 2
            && self::gameSnapshot($connection, self::findGameByName($connection, $byeNames[0]) ?? 0)['status'] === 'Concluido';
        Assertions::assert('Bye com uma equipe cada avança sem ser tratado como partida normal inválida', $byeReady, json_encode($byeResponse, JSON_UNESCAPED_UNICODE));

        $parentBefore = $byeParent === null ? null : self::gameSnapshot($connection, $byeParent);
        $topologyKey = self::newSyncMutationKey('topology');
        $mutationKeys[] = $topologyKey;
        $topologyResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => 'MM:2:0:N',
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $thirdTeam, 'resultado' => 0],
            ],
        ]]], ['X-SGI-Mutation-Id' => $topologyKey]);
        Assertions::assert(
            'Sincronização não aceita participantes diferentes dos vencedores que formaram o jogo',
            $byeParent !== null
            && $topologyResponse['code'] === 400
            && self::gameSnapshot($connection, $byeParent) === $parentBefore
            && self::syncMutationCount($connection, $topologyKey) === 0,
            json_encode($topologyResponse, JSON_UNESCAPED_UNICODE),
        );

        $otherOperator = new TestClient();
        $otherOperator->login('admin', '123');
        $otherOperatorReplay = $otherOperator->postJson($endpoint, $byeBody, ['X-SGI-Mutation-Id' => $byeKey]);
        Assertions::assert(
            'Chave de sincronização não pode ser reutilizada por outro operador',
            $otherOperatorReplay['code'] === 409 && self::syncMutationCount($connection, $byeKey) === 1,
            json_encode($otherOperatorReplay, JSON_UNESCAPED_UNICODE),
        );

        foreach (['MM:4:0:B', 'MM:4:1:B', 'MM:2:0:N'] as $byeName) {
            $byeGame = self::findGameByName($connection, $byeName);
            if ($byeGame !== null) {
                self::deleteGame($connection, $byeGame);
            }
        }

        $incrementalChild0Key = self::newSyncMutationKey('incremental-child-0');
        $mutationKeys[] = $incrementalChild0Key;
        $incrementalChild0 = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => 'MM:8:0:B',
            'status_jogo' => 'Concluido',
            'partidas' => [['id_equipe' => $teamA1, 'resultado' => 0]],
        ]]], ['X-SGI-Mutation-Id' => $incrementalChild0Key]);
        $incrementalGame0 = self::findGameByName($connection, 'MM:8:0:B');
        if ($incrementalGame0 !== null) {
            $createdGameIds[] = $incrementalGame0;
        }
        $incrementalParent = self::findGameByName($connection, 'MM:4:0:N');
        $incrementalFinal = self::findGameByName($connection, 'MM:2:0:N');
        foreach ([$incrementalParent, $incrementalFinal] as $incrementalGame) {
            if ($incrementalGame !== null) {
                $createdGameIds[] = $incrementalGame;
            }
        }
        $incrementalFirstReady = $incrementalChild0['code'] === 200
            && $incrementalParent !== null
            && self::gameSnapshot($connection, $incrementalParent)['status'] === 'Concluido'
            && self::gameTeams($connection, $incrementalParent) === [$teamA1];
        Assertions::assert(
            'Bye sem irmão materializado autoconclui o pai implícito de uma equipe',
            $incrementalFirstReady,
            json_encode([
                'resposta' => $incrementalChild0,
                'pai' => $incrementalParent === null ? null : self::gameSnapshot($connection, $incrementalParent),
                'equipes_pai' => $incrementalParent === null ? [] : self::gameTeams($connection, $incrementalParent),
                'final' => $incrementalFinal,
            ], JSON_UNESCAPED_UNICODE),
        );

        $incrementalChild1Key = self::newSyncMutationKey('incremental-child-1');
        $mutationKeys[] = $incrementalChild1Key;
        $incrementalChild1 = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => 'MM:8:1:B',
            'status_jogo' => 'Concluido',
            'partidas' => [['id_equipe' => $teamA2, 'resultado' => 0]],
        ]]], ['X-SGI-Mutation-Id' => $incrementalChild1Key]);
        $incrementalGame1 = self::findGameByName($connection, 'MM:8:1:B');
        if ($incrementalGame1 !== null) {
            $createdGameIds[] = $incrementalGame1;
        }
        $incrementalParentTeams = $incrementalParent === null ? [] : self::gameTeams($connection, $incrementalParent);
        Assertions::assert(
            'Materialização tardia do irmão reconstrói o pai com os dois vencedores e limpa descendentes',
            $incrementalFirstReady
            && $incrementalChild1['code'] === 200
            && $incrementalParent !== null
            && self::gameSnapshot($connection, $incrementalParent)['status'] === 'Agendado'
            && $incrementalParentTeams === [$teamA1, $teamA2]
            && ($incrementalFinal === null
                || (self::gameSnapshot($connection, $incrementalFinal)['status'] === 'Agendado'
                    && self::countParts($connection, $incrementalFinal) === 0)),
            json_encode(['resposta' => $incrementalChild1, 'equipes_pai' => $incrementalParentTeams], JSON_UNESCAPED_UNICODE),
        );

        $incrementalParentBefore = $incrementalParent === null ? null : self::gameSnapshot($connection, $incrementalParent);
        $incrementalInvalidKey = self::newSyncMutationKey('incremental-third-team');
        $mutationKeys[] = $incrementalInvalidKey;
        $incrementalInvalid = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => 'MM:4:0:N',
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA1, 'resultado' => 0],
                ['id_equipe' => $teamA2, 'resultado' => 0],
                ['id_equipe' => $thirdTeam, 'resultado' => 0],
            ],
        ]]], ['X-SGI-Mutation-Id' => $incrementalInvalidKey]);
        Assertions::assert(
            'Avanço incremental rejeita terceiro participante sem alterar pai nem confirmar mutação',
            $incrementalParent !== null
            && $incrementalInvalid['code'] === 400
            && self::gameSnapshot($connection, $incrementalParent) === $incrementalParentBefore
            && self::syncMutationCount($connection, $incrementalInvalidKey) === 0,
            json_encode($incrementalInvalid, JSON_UNESCAPED_UNICODE),
        );

        foreach (['MM:8:0:B', 'MM:8:1:B', 'MM:4:0:N', 'MM:2:0:N'] as $incrementalTag) {
            $incrementalId = self::findGameByName($connection, $incrementalTag);
            if ($incrementalId !== null) {
                self::deleteGame($connection, $incrementalId);
            }
        }

        self::assertAdditionalSyncRegressions(
            $mesario,
            $connection,
            $modalityA,
            $teamA1,
            $teamA2,
            $localId,
            $createdGameIds,
            $createdTeamIds,
            $mutationKeys,
        );

        $crossEditionKey = self::newSyncMutationKey('edition');
        $mutationKeys[] = $crossEditionKey;
        $beforeOtherEdition = self::gameSnapshot($connection, (int) $connection->query('SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ' . $modalityB . ' ORDER BY id_jogo LIMIT 1')->fetch_column());
        $otherEdition = $mesario->postJson($endpoint, [
            'id_modalidade' => $modalityB,
            'tipo_modalidade' => 'mata_mata',
            'jogos' => [[
                'nome_jogo' => 'T08 Other Edition ' . bin2hex(random_bytes(5)),
                'status_jogo' => 'Agendado',
                'partidas' => [
                    ['id_equipe' => $teamB1, 'resultado' => 0],
                    ['id_equipe' => $teamB2, 'resultado' => 0],
                ],
            ]],
        ], ['X-SGI-Mutation-Id' => $crossEditionKey]);
        Assertions::assert(
            'Sincronização coletiva mantém autorização da edição e não confirma operação negada',
            $otherEdition['code'] === 403
            && self::syncMutationCount($connection, $crossEditionKey) === 0
            && self::gameSnapshot($connection, (int) $connection->query('SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ' . $modalityB . ' ORDER BY id_jogo LIMIT 1')->fetch_column()) === $beforeOtherEdition,
            json_encode($otherEdition, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @param list<int> $createdGameIds
     * @param list<int> $createdTeamIds
     * @param list<string> $mutationKeys
     */
    private static function assertAdditionalSyncRegressions(
        TestClient $mesario,
        \mysqli $connection,
        int $modalityId,
        int $teamA,
        int $teamB,
        int $localId,
        array &$createdGameIds,
        array &$createdTeamIds,
        array &$mutationKeys,
    ): void {
        $endpoint = 'api/v1/sincronizacao/chaveamento';
        $base = ['id_modalidade' => $modalityId, 'tipo_modalidade' => 'mata_mata'];

        $invalidByes = [
            [
                'tag' => 'MM:8:0:B',
                'sibling_tag' => 'MM:8:1:B',
                'sibling_team' => $teamB,
                'status' => 'Agendado',
                'score' => 0,
                'assertion' => 'Bye não pode ser materializado como partida agendada',
            ],
            [
                'tag' => 'MM:8:2:B',
                'sibling_tag' => 'MM:8:3:B',
                'sibling_team' => $teamB,
                'status' => 'Concluido',
                'score' => 1,
                'assertion' => 'Bye concluído não pode possuir placar diferente de zero',
            ],
        ];
        foreach ($invalidByes as $invalidBye) {
            $key = self::newSyncMutationKey('invalid-bye');
            $mutationKeys[] = $key;
            $response = $mesario->postJson($endpoint, $base + ['jogos' => [[
                'nome_jogo' => $invalidBye['tag'],
                'status_jogo' => $invalidBye['status'],
                'partidas' => [['id_equipe' => $teamA, 'resultado' => $invalidBye['score']]],
            ], [
                'nome_jogo' => $invalidBye['sibling_tag'],
                'status_jogo' => 'Concluido',
                'partidas' => [['id_equipe' => $invalidBye['sibling_team'], 'resultado' => 0]],
            ]]], ['X-SGI-Mutation-Id' => $key]);
            $gameId = self::findGameByName($connection, $invalidBye['tag']);
            if ($gameId !== null) {
                $createdGameIds[] = $gameId;
            }
            $tagParts = explode(':', $invalidBye['tag']);
            $siblingId = self::findGameByName($connection, $invalidBye['sibling_tag']);
            if ($siblingId !== null) {
                $createdGameIds[] = $siblingId;
            }
            $parentTag = 'MM:4:' . intdiv((int) $tagParts[2], 2) . ':N';
            $parentId = self::findGameByName($connection, $parentTag);
            if ($parentId !== null) {
                $createdGameIds[] = $parentId;
            }
            Assertions::assert(
                $invalidBye['assertion'],
                $response['code'] === 400
                && $gameId === null
                && self::syncMutationCount($connection, $key) === 0,
                json_encode($response, JSON_UNESCAPED_UNICODE),
            );
            if ($gameId !== null) {
                self::deleteGame($connection, $gameId);
            }
            if ($siblingId !== null) {
                self::deleteGame($connection, $siblingId);
            }
            if ($parentId !== null) {
                self::deleteGame($connection, $parentId);
            }
        }

        $lowerStructuralTag = 'mm:16:0:n';
        $lowerStructuralKey = self::newSyncMutationKey('lower-tag');
        $mutationKeys[] = $lowerStructuralKey;
        $lowerStructuralResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $lowerStructuralTag,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA, 'resultado' => 0],
                ['id_equipe' => $teamB, 'resultado' => 0],
            ],
        ]]], ['X-SGI-Mutation-Id' => $lowerStructuralKey]);
        $lowerStructuralIds = self::gameIdsByName($connection, $lowerStructuralTag);
        array_push($createdGameIds, ...$lowerStructuralIds);
        Assertions::assert(
            'Tag estrutural em caixa baixa não pode ser aceita como nome legado',
            $lowerStructuralResponse['code'] === 400
            && $lowerStructuralIds === []
            && self::syncMutationCount($connection, $lowerStructuralKey) === 0,
            json_encode($lowerStructuralResponse, JSON_UNESCAPED_UNICODE),
        );
        foreach ($lowerStructuralIds as $gameId) {
            self::deleteGame($connection, $gameId);
        }

        $legacyName = 'T08 Legacy Case ' . bin2hex(random_bytes(5));
        $legacyGameId = self::createGame($connection, $modalityId, $localId, $legacyName, $teamA, $teamB);
        $createdGameIds[] = $legacyGameId;
        $legacyVariant = strtolower($legacyName);
        $legacyKey = self::newSyncMutationKey('legacy-case');
        $mutationKeys[] = $legacyKey;
        $legacyResponse = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => $legacyVariant,
            'status_jogo' => 'Agendado',
            'partidas' => [
                ['id_equipe' => $teamA, 'resultado' => 0],
                ['id_equipe' => $teamB, 'resultado' => 0],
            ],
        ]]], ['X-SGI-Mutation-Id' => $legacyKey]);
        $legacyMatches = self::gameIdsByName($connection, $legacyVariant);
        foreach ($legacyMatches as $gameId) {
            $createdGameIds[] = $gameId;
        }
        Assertions::assert(
            'Nomes legados usam a mesma comparação case-insensitive do MySQL sem criar duplicata',
            $legacyResponse['code'] === 200
            && $legacyMatches === [$legacyGameId]
            && self::syncMutationCount($connection, $legacyKey) === 1,
            json_encode(['response' => $legacyResponse, 'matches' => $legacyMatches], JSON_UNESCAPED_UNICODE),
        );
        foreach (array_unique($legacyMatches) as $gameId) {
            self::deleteGame($connection, $gameId);
        }

        $loserTeams = [];
        for ($index = 0; $index < 6; $index++) {
            $loserTeams[] = self::createTeam($connection, $modalityId, $teamA);
        }
        array_push($createdTeamIds, ...$loserTeams);
        [$teamC, $teamD, $teamE, $teamF, $teamG, $teamH] = $loserTeams;
        $structuralFixture = [
            ['MM:8:0:N', $teamA, 2, $teamB, 0],
            ['MM:8:1:N', $teamC, 2, $teamD, 0],
            ['MM:8:2:N', $teamE, 2, $teamF, 0],
            ['MM:8:3:N', $teamG, 2, $teamH, 0],
            ['MM:4:0:N', $teamA, 3, $teamC, 0],
            ['MM:4:1:N', $teamE, 3, $teamG, 0],
            ['MM:2:0:N', $teamA, 3, $teamE, 0],
            ['POS:3:0:N', $teamC, 3, $teamG, 0],
        ];
        $structuralIds = [];
        foreach ($structuralFixture as [$tag, $team1, $score1, $team2, $score2]) {
            $gameId = self::createCompletedStructuralGame(
                $connection,
                $modalityId,
                $localId,
                $tag,
                $team1,
                $score1,
                $team2,
                $score2,
            );
            $structuralIds[$tag] = $gameId;
            $createdGameIds[] = $gameId;
        }
        $correctionKey = self::newSyncMutationKey('downstream-loser-correction');
        $mutationKeys[] = $correctionKey;
        $correction = $mesario->postJson($endpoint, $base + ['jogos' => [[
            'nome_jogo' => 'MM:8:1:N',
            'status_jogo' => 'Concluido',
            'partidas' => [
                ['id_equipe' => $teamC, 'resultado' => 0],
                ['id_equipe' => $teamD, 'resultado' => 1],
            ],
        ]]], ['X-SGI-Mutation-Id' => $correctionKey]);
        $rebuiltSemiTeams = self::gameTeams($connection, $structuralIds['MM:4:0:N']);
        $rebuiltFinal = self::gameSnapshot($connection, $structuralIds['MM:2:0:N']);
        Assertions::assert(
            'Correção de vencedor anterior reconstrói a semifinal e remove a disputa de terceiro obsoleta',
            $correction['code'] === 200
            && $rebuiltSemiTeams === [$teamA, $teamD]
            && self::gameSnapshot($connection, $structuralIds['MM:4:0:N'])['status'] === 'Agendado'
            && $rebuiltFinal['status'] === 'Agendado'
            && self::gameTeams($connection, $structuralIds['MM:2:0:N']) === []
            && self::findGameByName($connection, 'POS:3:0:N') === null,
            json_encode([
                'response' => $correction,
                'semi_teams' => $rebuiltSemiTeams,
                'final' => $rebuiltFinal,
                'pos3' => self::findGameByName($connection, 'POS:3:0:N'),
            ], JSON_UNESCAPED_UNICODE),
        );
    }

    /** @return list<int> */
    private static function gameIdsByName(\mysqli $connection, string $name): array
    {
        $statement = $connection->prepare('SELECT id_jogo FROM jogos WHERE nome_jogo = ? ORDER BY id_jogo');
        $statement->bind_param('s', $name);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return array_map(static fn (array $row): int => (int) $row['id_jogo'], $rows);
    }

    private static function createCompletedStructuralGame(
        \mysqli $connection,
        int $modalityId,
        int $localId,
        string $name,
        int $teamA,
        int $scoreA,
        int $teamB,
        int $scoreB,
    ): int {
        $gameId = self::createGame($connection, $modalityId, $localId, $name, $teamA, $teamB);
        $game = $connection->prepare("UPDATE jogos SET status_jogo = 'Concluido', exige_vinculo_ponto = 0 WHERE id_jogo = ?");
        $game->bind_param('i', $gameId);
        $game->execute();
        $game->close();
        foreach ([[$teamA, $scoreA], [$teamB, $scoreB]] as [$teamId, $score]) {
            $part = $connection->prepare('UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?');
            $part->bind_param('iii', $score, $gameId, $teamId);
            $part->execute();
            $part->close();
        }
        return $gameId;
    }

    private static function createTeam(\mysqli $connection, int $modalityId, int $sourceTeamId): int
    {
        $statement = $connection->prepare('SELECT turmas_id_turma FROM equipes WHERE id_equipe = ?');
        $statement->bind_param('i', $sourceTeamId);
        $statement->execute();
        $classId = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $name = 'T08 Extra ' . bin2hex(random_bytes(5));
        $insert = $connection->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)");
        $insert->bind_param('iis', $modalityId, $classId, $name);
        $insert->execute();
        $teamId = (int) $connection->insert_id;
        $insert->close();
        return $teamId;
    }

    private static function teamAthlete(\mysqli $connection, int $teamId): int
    {
        $statement = $connection->prepare('SELECT usuarios_id_usuario FROM equipes_has_usuarios WHERE equipes_id_equipe = ? ORDER BY usuarios_id_usuario LIMIT 1');
        $statement->bind_param('i', $teamId);
        $statement->execute();
        $athleteId = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $athleteId;
    }

    private static function newSyncMutationKey(string $suffix): string
    {
        return 'l08-' . $suffix . '-' . bin2hex(random_bytes(8));
    }

    private static function syncMutationCount(\mysqli $connection, string $key): int
    {
        $statement = $connection->prepare("SELECT COUNT(*) FROM sincronizacoes_idempotentes WHERE rota = 'sincronizar_chaveamento' AND chave_mutacao = ?");
        $statement->bind_param('s', $key);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
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

    /** @return list<int> */
    private static function gameTeams(\mysqli $connection, int $gameId): array
    {
        $statement = $connection->prepare('SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ? ORDER BY equipes_id_equipe');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return array_map(static fn (array $row): int => (int) $row['equipes_id_equipe'], $rows);
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
        foreach ([
            'DELETE FROM artilheiros WHERE jogos_id_jogo = ?',
            'DELETE FROM pontuacoes_podio WHERE id_jogo = ?',
            'DELETE FROM agenda_reservas WHERE id_jogo = ?',
        ] as $sql) {
            $dependent = $connection->prepare($sql);
            $dependent->bind_param('i', $gameId);
            $dependent->execute();
            $dependent->close();
        }
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
