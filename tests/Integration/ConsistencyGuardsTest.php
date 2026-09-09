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
        $gameId = (int) $edition['jogo_ids'][0];
        $partidaId = (int) $edition['partida_ids'][1];
        $teamIds = array_map('intval', $edition['equipe_ids']);
        $client = new TestClient();

        try {
            $login = $client->login('admin', '123');
            Assertions::assert('Guarda de consistência autentica o operador de teste', ($login['json']['status'] ?? '') === 'sucesso');

            $connection->query("UPDATE jogos SET nome_jogo = 'MM:2:0:N', duracao_jogo = 1200, tempo_extra_jogo = 0 WHERE id_jogo = {$gameId}");
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
            Assertions::assert('Placar negativo é rejeitado sem persistência', in_array($negative['code'], [400, 422], true) && $afterNegative === $before);

            $closed = $client->request('api/v1/partidas', 'PUT', [
                'id_partida' => $partidaId,
                'resultado_partida' => 3,
            ]);
            $afterClosed = $connection->query("SELECT resultado_partida FROM partidas WHERE id_partida = {$partidaId}")->fetch_column();
            Assertions::assert('Partida encerrada exige retificação completa', $closed['code'] === 422 && $afterClosed === $before);

            $connection->query("UPDATE jogos SET status_jogo = 'Agendado', nome_jogo = 'Antes', duracao_jogo = 1200, tempo_restante_jogo = 1200 WHERE id_jogo = {$gameId}");
            $mixed = $client->request('api/v1/jogos', 'PUT', [
                'id_jogo' => $gameId,
                'nome_jogo' => 'Depois',
                'duracao_jogo' => 1500,
            ]);
            $row = $connection->query("SELECT nome_jogo, duracao_jogo FROM jogos WHERE id_jogo = {$gameId}")->fetch_assoc();
            Assertions::assert('Atualização mista de agenda e cronômetro é rejeitada atomicamente', $mixed['code'] === 422 && $row['nome_jogo'] === 'Antes' && (int) $row['duracao_jogo'] === 1200);

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
        } finally {
            $connection->query('DELETE FROM pontuacoes_podio WHERE id_interclasse = ' . (int) $edition['interclasse_id']);
            AuditFixtures::restoreAndRemove($connection, $fixture, $previousActive);
            $connection->close();
        }
    }
}
