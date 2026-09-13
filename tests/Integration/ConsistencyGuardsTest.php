<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class ConsistencyGuardsTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite: Guardas de consistência do placar e agenda]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $previousActive = AuditFixtures::activeEditionId($connection);
        $fixture = AuditFixtures::createAuthorizationFixture($connection);
        $edition = $fixture['by_edition']['A'];
        $otherEdition = $fixture['by_edition']['B'];
        $gameId = (int) $edition['jogo_ids'][0];
        $partidaId = (int) $edition['partida_ids'][1];
        $teamIds = array_map('intval', $edition['equipe_ids']);
        $probePrefix = 'L06:' . bin2hex(random_bytes(6));
        $probeGameNames = [];
        $alternateModalityId = 0;
        $client = new TestClient();

        try {
            $login = $client->login('admin', '123');
            Assertions::assert('Guarda de consistência autentica o operador de teste', ($login['json']['status'] ?? '') === 'sucesso');

            // A fixture genérica não agenda término para o jogo. Preenchemos
            // apenas este cenário, pois a transição para Iniciado exige uma
            // janela de agenda válida e não deve ser flexibilizada em produção.
            $connection->query("UPDATE jogos SET nome_jogo = 'MM:2:0:N', termino_jogo = '10:00:00', duracao_jogo = 1200, tempo_extra_jogo = 0 WHERE id_jogo = {$gameId}");
            $started = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'status_jogo' => 'Iniciado',
                'duracao_jogo' => 1200,
                'tempo_restante_jogo' => 1200,
            ]);
            Assertions::assert(
                'Partida da guarda pode ser iniciada para registrar jogadas',
                ($started['json']['success'] ?? false) === true,
                'HTTP ' . ($started['code'] ?? 0) . ': ' . mb_substr((string) ($started['body'] ?? ''), 0, 240),
            );
            $point1 = $client->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $gameId,
                'id_partida' => (int) $edition['partida_ids'][0],
                'equipes_id_equipe' => $teamIds[0],
                'usuarios_id_usuario' => (int) $edition['atleta_ids'][0],
                'chave_jogada' => 'consistency-point-' . bin2hex(random_bytes(5)),
            ]);
            $point1Extra = $client->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $gameId,
                'id_partida' => (int) $edition['partida_ids'][0],
                'equipes_id_equipe' => $teamIds[0],
                'usuarios_id_usuario' => (int) $edition['atleta_ids'][0],
                'chave_jogada' => 'consistency-point-' . bin2hex(random_bytes(5)),
            ]);
            $point2 = $client->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $gameId,
                'id_partida' => $partidaId,
                'equipes_id_equipe' => $teamIds[1],
                'usuarios_id_usuario' => (int) $edition['atleta_ids'][1],
                'chave_jogada' => 'consistency-point-' . bin2hex(random_bytes(5)),
            ]);
            Assertions::assert('Jogadas vinculadas deixam o placar pronto para finalização',
                ($point1['json']['success'] ?? false) === true
                && ($point1Extra['json']['success'] ?? false) === true
                && ($point2['json']['success'] ?? false) === true,
            );
            $final = $client->postJson('api/v1/resultados', [
                'id_jogo' => $gameId,
                'id_modalidade' => (int) $edition['modalidade_id'],
                'resultados' => [
                    ['id_equipe' => $teamIds[0], 'gols' => 2],
                    ['id_equipe' => $teamIds[1], 'gols' => 1],
                ],
            ]);
            Assertions::assert('Resultado inicial da guarda é aceito', $final['code'] === 200 && ($final['json']['success'] ?? false) === true);

            $before = $connection->query("SELECT resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
            $negative = $client->request('api/v1/partidas', 'PUT', [
                'id_partida' => $partidaId,
                'resultado_partida' => -5,
            ]);
            $afterNegative = $connection->query("SELECT resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
            Assertions::assert('Alteração direta de placar é rejeitada sem persistência', $negative['code'] === 422 && $afterNegative === $before);

            $closed = $client->request('api/v1/partidas', 'PUT', [
                'id_partida' => $partidaId,
                'resultado_partida' => 3,
            ]);
            $afterClosed = $connection->query("SELECT resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
            Assertions::assert('Partida encerrada exige retificação completa', $closed['code'] === 422 && $afterClosed === $before);

            $statusRow = $connection->query("SELECT status_partida, resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_assoc();
            $originalPartidaStatus = (string) $statusRow['status_partida'];
            $targetPartidaStatus = $originalPartidaStatus === '1' ? '0' : '1';
            $artilheirosBefore = $connection->query("SELECT * FROM artilheiros WHERE jogos_id_jogo = {$gameId} ORDER BY id_artilheiro")->fetch_all(MYSQLI_ASSOC);
            $legacyPointsBefore = $connection->query("SELECT * FROM pontuacoes WHERE jogos_id_jogo = {$gameId} ORDER BY id_pontuacao")->fetch_all(MYSQLI_ASSOC);
            $podiumPointsBefore = $connection->query("SELECT * FROM pontuacoes_podio WHERE id_jogo = {$gameId} ORDER BY id_pontuacao")->fetch_all(MYSQLI_ASSOC);
            try {
                $statusOnly = $client->request('api/v1/partidas', 'PUT', [
                    'id_partida' => $partidaId,
                    'status_partida' => $targetPartidaStatus,
                ]);
                $statusAfter = (string) $connection->query("SELECT status_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
                $scoreAfterStatusOnly = $connection->query("SELECT resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
                $artilheirosAfter = $connection->query("SELECT * FROM artilheiros WHERE jogos_id_jogo = {$gameId} ORDER BY id_artilheiro")->fetch_all(MYSQLI_ASSOC);
                $legacyPointsAfter = $connection->query("SELECT * FROM pontuacoes WHERE jogos_id_jogo = {$gameId} ORDER BY id_pontuacao")->fetch_all(MYSQLI_ASSOC);
                $podiumPointsAfter = $connection->query("SELECT * FROM pontuacoes_podio WHERE id_jogo = {$gameId} ORDER BY id_pontuacao")->fetch_all(MYSQLI_ASSOC);
                Assertions::assert(
                    'PUT status-only é aceito para jogo encerrado',
                    $statusOnly['code'] === 200 && ($statusOnly['json']['success'] ?? false) === true,
                    'HTTP ' . ($statusOnly['code'] ?? 0),
                );
                Assertions::assert(
                    'PUT status-only altera o status da partida',
                    $statusAfter === $targetPartidaStatus && $statusAfter !== $originalPartidaStatus,
                    'status=' . $statusAfter,
                );
                Assertions::assert(
                    'PUT status-only preserva placar e todas as linhas de pontuação',
                    $scoreAfterStatusOnly === $statusRow['resultado_partida']
                    && $artilheirosAfter === $artilheirosBefore
                    && $legacyPointsAfter === $legacyPointsBefore
                    && $podiumPointsAfter === $podiumPointsBefore,
                );
            } finally {
                $restorePartidaStatus = $connection->prepare('UPDATE partidas SET status_partida = ? WHERE id_partida = ?');
                $restorePartidaStatus->bind_param('si', $originalPartidaStatus, $partidaId);
                $restorePartidaStatus->execute();
                $restorePartidaStatus->close();
            }

            $connection->query("UPDATE jogos SET status_jogo = 'Agendado', duracao_jogo = 1200, tempo_restante_jogo = 1200 WHERE id_jogo = {$gameId}");
            $mixed = $client->request('api/v1/jogos', 'PUT', [
                'id_jogo' => $gameId,
                'nome_jogo' => 'Depois',
                'duracao_jogo' => 1500,
            ]);
            $row = $connection->query("SELECT nome_jogo, duracao_jogo FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert('Atualização mista de agenda e cronômetro é rejeitada atomicamente', $mixed['code'] === 422 && $row['nome_jogo'] === 'MM:2:0:N' && (int) $row['duracao_jogo'] === 1200);

            $agenda = $client->request('api/v1/jogos', 'PUT', [
                'id_jogo' => $gameId,
                'data_jogo' => date('Y-m-d', strtotime('+1 day')),
                'inicio_jogo' => '09:00',
                'termino_jogo' => '10:00',
                'locais_id_local' => (int) $edition['local_id'],
                'status_jogo' => 'Agendado',
            ]);
            $agendaRow = $connection->query("SELECT data_jogo, inicio_jogo, termino_jogo, status_jogo FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert('Edição da agenda com status Agendado é aceita',
                $agenda['code'] === 200
                && ($agenda['json']['success'] ?? false) === true
                && $agendaRow['data_jogo'] === date('Y-m-d', strtotime('+1 day'))
                && substr((string) $agendaRow['inicio_jogo'], 0, 5) === '09:00'
                && substr((string) $agendaRow['termino_jogo'], 0, 5) === '10:00'
                && $agendaRow['status_jogo'] === 'Agendado',
            );

            $alternateName = $probePrefix . ':alternate-modality';
            $alternate = $connection->prepare("INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse) VALUES (?, 'MASC', 10, 2, '1', 1, ?, ?)");
            $categoryId = (int) $edition['categoria_id'];
            $editionId = (int) $edition['interclasse_id'];
            $alternate->bind_param('sii', $alternateName, $categoryId, $editionId);
            $alternate->execute();
            $alternateModalityId = (int) $connection->insert_id;
            $alternate->close();

            $postCrossEditionName = $probePrefix . ':post-cross-edition';
            $probeGameNames[] = $postCrossEditionName;
            $postCrossEdition = $client->postJson('api/v1/jogos', [
                'nome_jogo' => $postCrossEditionName,
                'data_jogo' => date('Y-m-d', strtotime('+2 days')),
                'inicio_jogo' => '12:00',
                'termino_jogo' => '12:45',
                'modalidades_id_modalidade' => (int) $edition['modalidade_id'],
                'locais_id_local' => (int) $otherEdition['local_id'],
            ]);
            $postCrossEditionCount = self::gameCountByName($connection, $postCrossEditionName);
            Assertions::assert(
                'POST rejeita local de outra edição sem criar jogo',
                in_array($postCrossEdition['code'], [400, 422], true) && $postCrossEditionCount === 0,
                'HTTP ' . ($postCrossEdition['code'] ?? 0) . ', linhas=' . $postCrossEditionCount,
            );

            $crossEditionLocal = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'locais_id_local' => (int) $otherEdition['local_id'],
            ]);
            $crossEditionLocalNow = self::gameColumn($connection, $gameId, 'locais_id_local');
            Assertions::assert(
                'PUT rejeita local de outra edição e preserva o local anterior',
                $crossEditionLocal['code'] === 422 && (int) $crossEditionLocalNow === (int) $edition['local_id'],
                'HTTP ' . ($crossEditionLocal['code'] ?? 0),
            );

            $window = $connection->query("SELECT data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            $reservation = $connection->prepare('INSERT INTO agenda_reservas (id_interclasse, id_modalidade, chave_tag, id_jogo, data_reserva, inicio_reserva, termino_reserva, id_local) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $tag = 'MM:2:0:N';
            $gameModality = (int) $edition['modalidade_id'];
            $gameLocal = (int) $edition['local_id'];
            $reservation->bind_param('iisisssi', $editionId, $gameModality, $tag, $gameId, $window['data_jogo'], $window['inicio_jogo'], $window['termino_jogo'], $gameLocal);
            $reservation->execute();
            $reservation->close();

            $linkedBefore = [
                'partidas' => AuditFixtures::countRowsByIds($connection, 'partidas', 'id_partida', $edition['partida_ids']),
                'pontos' => (int) $connection->query("SELECT COUNT(*) FROM pontuacoes WHERE jogos_id_jogo = {$gameId}")->fetch_column(),
                'podio' => (int) $connection->query("SELECT COUNT(*) FROM pontuacoes_podio WHERE id_jogo = {$gameId}")->fetch_column(),
                'reservas' => (int) $connection->query("SELECT COUNT(*) FROM agenda_reservas WHERE id_jogo = {$gameId}")->fetch_column(),
            ];
            $changeModality = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'modalidades_id_modalidade' => $alternateModalityId,
            ]);
            $modalityAfter = (int) self::gameColumn($connection, $gameId, 'modalidades_id_modalidade');
            $linkedAfter = [
                'partidas' => AuditFixtures::countRowsByIds($connection, 'partidas', 'id_partida', $edition['partida_ids']),
                'pontos' => (int) $connection->query("SELECT COUNT(*) FROM pontuacoes WHERE jogos_id_jogo = {$gameId}")->fetch_column(),
                'podio' => (int) $connection->query("SELECT COUNT(*) FROM pontuacoes_podio WHERE id_jogo = {$gameId}")->fetch_column(),
                'reservas' => (int) $connection->query("SELECT COUNT(*) FROM agenda_reservas WHERE id_jogo = {$gameId}")->fetch_column(),
            ];
            Assertions::assert(
                'PUT rejeita troca de modalidade incompatível com participantes, pontos e reserva',
                $changeModality['code'] === 422
                && $modalityAfter === $gameModality
                && $linkedAfter === $linkedBefore,
                'HTTP ' . ($changeModality['code'] ?? 0) . ', modalidade=' . $modalityAfter,
            );
            // Repare o fixture depois de observar o defeito no baseline para
            // que os demais casos continuem independentes na execução vermelha.
            $connection->query("UPDATE jogos SET modalidades_id_modalidade = {$gameModality} WHERE id_jogo = {$gameId}");

            $reservedWindowChange = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'inicio_jogo' => '11:00',
                'termino_jogo' => '12:00',
            ]);
            $afterReservedWindow = $connection->query("SELECT data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert(
                'PUT não diverge jogo da janela confirmada na reserva',
                $reservedWindowChange['code'] === 422 && $afterReservedWindow === $window,
                'HTTP ' . ($reservedWindowChange['code'] ?? 0),
            );

            foreach (['MM:2:0:N', 'POS:3:0:N'] as $structuralTag) {
                $connection->query("UPDATE jogos SET nome_jogo = '" . $connection->real_escape_string($structuralTag) . "' WHERE id_jogo = {$gameId}");
                $renamedTag = $client->putJson('api/v1/jogos', [
                    'id_jogo' => $gameId,
                    'nome_jogo' => 'Renomeado pelo formulário',
                ]);
                $storedTag = self::gameColumn($connection, $gameId, 'nome_jogo');
                Assertions::assert(
                    'PUT preserva identidade estrutural ' . $structuralTag,
                    $renamedTag['code'] === 422 && $storedTag === $structuralTag,
                    'HTTP ' . ($renamedTag['code'] ?? 0) . ', nome=' . (string) $storedTag,
                );
                $connection->query("UPDATE jogos SET nome_jogo = '" . $connection->real_escape_string('MM:2:0:N') . "' WHERE id_jogo = {$gameId}");
            }

            $beforeInvalidWindow = $connection->query("SELECT nome_jogo, data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            $invertedWindow = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'nome_jogo' => 'Janela inválida',
                'inicio_jogo' => '09:00',
                'termino_jogo' => '08:00',
            ]);
            $afterInvertedWindow = $connection->query("SELECT nome_jogo, data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert(
                'PUT rejeita término anterior ao início sem alteração parcial',
                $invertedWindow['code'] === 422 && $afterInvertedWindow === $beforeInvalidWindow,
                'HTTP ' . ($invertedWindow['code'] ?? 0),
            );

            $invalidDate = $client->putJson('api/v1/jogos', [
                'id_jogo' => $gameId,
                'nome_jogo' => 'Data inválida',
                'data_jogo' => '2026-02-30',
            ]);
            $afterInvalidDate = $connection->query("SELECT nome_jogo, data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert(
                'PUT rejeita data impossível sem alteração parcial',
                $invalidDate['code'] === 422 && $afterInvalidDate === $beforeInvalidWindow,
                'HTTP ' . ($invalidDate['code'] ?? 0),
            );

            foreach ([
                ['disponivel_local' => '0', 'status_local' => '1', 'label' => 'indisponível', 'days' => 3],
                ['disponivel_local' => '1', 'status_local' => '0', 'label' => 'inativo', 'days' => 5],
            ] as $localState) {
                $available = $localState['disponivel_local'];
                $status = $localState['status_local'];
                $label = $localState['label'];
                $connection->query("UPDATE locais SET disponivel_local = '{$available}', status_local = '{$status}' WHERE id_local = " . (int) $edition['local_id']);
                try {
                    $invalidLocalName = $probePrefix . ':post-' . $label;
                    $probeGameNames[] = $invalidLocalName;
                    $invalidLocalPost = $client->postJson('api/v1/jogos', [
                        'nome_jogo' => $invalidLocalName,
                        'data_jogo' => date('Y-m-d', strtotime('+' . (int) $localState['days'] . ' days')),
                        'inicio_jogo' => '12:00',
                        'termino_jogo' => '12:45',
                        'modalidades_id_modalidade' => $gameModality,
                        'locais_id_local' => (int) $edition['local_id'],
                    ]);
                    $invalidLocalCount = self::gameCountByName($connection, $invalidLocalName);
                    Assertions::assert(
                        'POST rejeita local ' . $label . ' sem criar jogo',
                        in_array($invalidLocalPost['code'], [400, 422], true) && $invalidLocalCount === 0,
                        'HTTP ' . ($invalidLocalPost['code'] ?? 0) . ', linhas=' . $invalidLocalCount,
                    );

                    $invalidLocalPut = $client->putJson('api/v1/jogos', [
                        'id_jogo' => $gameId,
                        'locais_id_local' => (int) $edition['local_id'],
                    ]);
                    $storedLocal = (int) self::gameColumn($connection, $gameId, 'locais_id_local');
                    Assertions::assert(
                        'PUT rejeita local ' . $label . ' e preserva a agenda',
                        $invalidLocalPut['code'] === 422 && $storedLocal === (int) $edition['local_id'],
                        'HTTP ' . ($invalidLocalPut['code'] ?? 0),
                    );
                } finally {
                    $connection->query("UPDATE locais SET disponivel_local = '1', status_local = '1' WHERE id_local = " . (int) $edition['local_id']);
                }
            }

            $pendingGameName = $probePrefix . ':pending';
            $probeGameNames[] = $pendingGameName;
            $pendingGame = $client->postJson('api/v1/jogos', [
                'nome_jogo' => $pendingGameName,
                'data_jogo' => date('Y-m-d', strtotime('+4 days')),
                'inicio_jogo' => '13:00',
                'termino_jogo' => '13:45',
                'modalidades_id_modalidade' => $gameModality,
                'locais_id_local' => (int) $edition['local_id'],
            ]);
            $pendingGameId = (int) ($pendingGame['json']['id_jogo'] ?? 0);
            if ($pendingGameId > 0) {
                $clearPendingSchedule = $client->putJson('api/v1/jogos', [
                    'id_jogo' => $pendingGameId,
                    'data_jogo' => null,
                    'inicio_jogo' => null,
                    'termino_jogo' => null,
                    'locais_id_local' => null,
                ]);
                $pending = $connection->query("SELECT data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo = {$pendingGameId}")->fetch_assoc();
                Assertions::assert(
                    'PUT preserva campos nulos permitidos para jogo pendente',
                    $clearPendingSchedule['code'] === 200
                    && $pending['data_jogo'] === null
                    && $pending['inicio_jogo'] === null
                    && $pending['termino_jogo'] === null
                    && $pending['locais_id_local'] === null,
                    'HTTP ' . ($clearPendingSchedule['code'] ?? 0),
                );
            } else {
                Assertions::assert('PUT preserva campos nulos permitidos para jogo pendente', false, 'POST do jogo pendente não foi aceito.');
            }
        } finally {
            $connection->query("UPDATE locais SET disponivel_local = '1', status_local = '1' WHERE id_local = " . (int) $edition['local_id']);
            $connection->query("DELETE FROM agenda_reservas WHERE id_jogo = {$gameId}");
            if ($probeGameNames !== []) {
                $quotedNames = array_map(static fn (string $name): string => "'" . $connection->real_escape_string($name) . "'", $probeGameNames);
                $nameList = implode(', ', $quotedNames);
                $probeIdsResult = $connection->query("SELECT id_jogo FROM jogos WHERE nome_jogo IN ({$nameList})");
                $probeIds = array_map('intval', array_column($probeIdsResult->fetch_all(MYSQLI_ASSOC), 'id_jogo'));
                if ($probeIds !== []) {
                    $probeIdList = implode(', ', $probeIds);
                    $connection->query("DELETE FROM agenda_reservas WHERE id_jogo IN ({$probeIdList})");
                    $connection->query("DELETE FROM artilheiros WHERE jogos_id_jogo IN ({$probeIdList})");
                    $connection->query("DELETE FROM pontuacoes WHERE jogos_id_jogo IN ({$probeIdList})");
                    $connection->query("DELETE FROM pontuacoes_podio WHERE id_jogo IN ({$probeIdList})");
                    $connection->query("DELETE FROM partidas WHERE jogos_id_jogo IN ({$probeIdList})");
                    $connection->query("DELETE FROM jogos WHERE id_jogo IN ({$probeIdList})");
                }
            }
            if ($alternateModalityId > 0) {
                $connection->query('DELETE FROM modalidades WHERE id_modalidade = ' . $alternateModalityId);
            }
            $connection->query('DELETE FROM pontuacoes_podio WHERE id_interclasse = ' . (int) $edition['interclasse_id']);
            AuditFixtures::restoreAndRemove($connection, $fixture, $previousActive);
            $connection->close();
        }
    }

    private static function gameCountByName(\mysqli $connection, string $name): int
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM jogos WHERE nome_jogo = ?');
        $statement->bind_param('s', $name);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    private static function gameColumn(\mysqli $connection, int $gameId, string $column): mixed
    {
        if (!in_array($column, ['locais_id_local', 'modalidades_id_modalidade', 'nome_jogo'], true)) {
            throw new \InvalidArgumentException('Coluna de jogo desconhecida.');
        }
        $statement = $connection->prepare('SELECT ' . $column . ' FROM jogos WHERE id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return $value;
    }
}
