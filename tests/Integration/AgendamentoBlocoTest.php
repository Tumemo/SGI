<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class AgendamentoBlocoTest
{
    /** @param array<string,mixed> $jogos */
    public static function run(int $edition, int $modality, array $jogos): void
    {
        echo "\n  \033[1;34m[Suite 6.1: Agendamento em blocos]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $names = ['BLK:' . bin2hex(random_bytes(3)) . ':1', 'BLK:' . bin2hex(random_bytes(3)) . ':2'];
        $ids = [];
        foreach ($names as $name) {
            $statement = $connection->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, NULL, NULL, NULL, 'Agendado', ?, NULL)");
            $statement->bind_param('si', $name, $modality);
            $statement->execute();
            $ids[] = (int) $connection->insert_id;
            $statement->close();
        }
        $connection->close();

        $mesario = new TestClient();
        $mesario->login('mesario', '123');
        $operational = $mesario->get('api/v1/jogos?id_interclasse=' . $edition);
        $operationalIds = array_map(static fn (array $row): int => (int) ($row['id_jogo'] ?? 0), (array) ($operational['json'] ?? []));
        Assertions::assert('Mesário não recebe jogos sem data, horário e local', !array_intersect($ids, $operationalIds));
        $mesarioPreview = $mesario->postJson('api/v1/agenda-blocos', ['acao' => 'simular', 'id_interclasse' => $edition]);
        Assertions::assert('Mesário não pode criar programação em bloco', $mesarioPreview['code'] === 403 && ($mesarioPreview['json']['success'] ?? true) === false);

        $client = new TestClient();
        $client->login('admin', '123');
        $payload = [
            'id_interclasse' => $edition,
            'id_modalidade' => $modality,
            'jogos' => [['id_jogo' => $ids[0]], ['id_jogo' => $ids[1]]],
            'janelas' => [['data' => date('Y-m-d', strtotime('+10 days')), 'inicio' => '08:00', 'fim' => '09:30', 'locais' => [$jogos['id_local']]]],
            'opcoes' => ['duracao_min' => 30, 'intervalo_troca_min' => 5, 'descanso_min' => 0],
        ];
        $preview = $client->postJson('api/v1/agenda-blocos', array_merge($payload, ['acao' => 'simular']));
        Assertions::assertJsonSuccess('Prévia do bloco retorna sucesso', $preview);
        Assertions::assert('Prévia distribui dois jogos sem sucesso parcial', ($preview['json']['resumo']['encaixados'] ?? 0) === 2 && ($preview['json']['pendencias'] ?? []) === []);
        Assertions::assert('Prévia reserva o mesmo local em horários distintos', ($preview['json']['proposta'][0]['inicio_jogo'] ?? '') !== ($preview['json']['proposta'][1]['inicio_jogo'] ?? ''));

        $confirm = $client->postJson('api/v1/agenda-blocos', array_merge($payload, ['acao' => 'confirmar', 'revisao' => $preview['json']['revisao'] ?? 0, 'idempotencia' => 'bloco-teste-' . $ids[0]]));
        Assertions::assertJsonSuccess('Confirmação do bloco grava todos os jogos', $confirm);
        $retry = $client->postJson('api/v1/agenda-blocos', array_merge($payload, ['acao' => 'confirmar', 'revisao' => $preview['json']['revisao'] ?? 0, 'idempotencia' => 'bloco-teste-' . $ids[0]]));
        Assertions::assertJsonSuccess('Reenvio do bloco é idempotente', $retry);
        Assertions::assert('Reenvio não cria segundo bloco', ($retry['json']['idempotente'] ?? false) === true && ($retry['json']['id_bloco'] ?? 0) === ($confirm['json']['id_bloco'] ?? -1));
        $reservationsApi = $client->get('api/v1/agenda-blocos?id_interclasse=' . $edition . '&id_modalidade=' . $modality);
        Assertions::assert('Consulta de reservas retorna a programação confirmada', $reservationsApi['code'] === 200 && is_array($reservationsApi['json'] ?? null) && count($reservationsApi['json']) >= 2);

        $connection = TestDatabase::connect($database);
        $row = $connection->query('SELECT data_jogo, inicio_jogo, termino_jogo, locais_id_local FROM jogos WHERE id_jogo IN (' . $ids[0] . ',' . $ids[1] . ') ORDER BY id_jogo')->fetch_all(MYSQLI_ASSOC);
        $reservations = (int) $connection->query("SELECT COUNT(*) FROM agenda_reservas WHERE id_bloco = " . (int) ($confirm['json']['id_bloco'] ?? 0))->fetch_row()[0];
        $connection->close();
        Assertions::assert('Projeção dos jogos e reservas foi persistida', count($row) === 2 && $row[0]['data_jogo'] !== null && $row[0]['locais_id_local'] === (string) $jogos['id_local'] && $reservations === 2);

        $future = [
            'acao' => 'confirmar',
            'id_interclasse' => $edition,
            'id_modalidade' => $modality,
            'chave_tags' => [['id_modalidade' => $modality, 'chave_tag' => 'BLK:FUTURO']],
            'janelas' => [['data' => date('Y-m-d', strtotime('+11 days')), 'inicio' => '10:00', 'fim' => '11:00', 'locais' => [$jogos['id_local']]]],
            'opcoes' => ['duracao_min' => 30],
            'idempotencia' => 'bloco-futuro-' . $modality,
        ];
        $futureResult = $client->postJson('api/v1/agenda-blocos', $future);
        Assertions::assertJsonSuccess('Reserva de posição futura é confirmada sem jogo materializado', $futureResult);
        $connection = TestDatabase::connect($database);
        $futureCount = (int) $connection->query("SELECT COUNT(*) FROM agenda_reservas WHERE id_modalidade = {$modality} AND chave_tag = 'BLK:FUTURO' AND id_jogo IS NULL")->fetch_row()[0];
        $connection->close();
        Assertions::assert('Reserva futura permanece disponível para materialização', $futureCount === 1);

        $materialized = $client->postJson('api/v1/sincronizacao/chaveamento', [
            'id_modalidade' => $modality,
            'tipo_modalidade' => 'mata_mata',
            'jogos' => [[
                'nome_jogo' => 'BLK:FUTURO',
                'status_jogo' => 'Agendado',
                'partidas' => [
                    ['id_equipe' => (int) $jogos['equipes_ids'][0], 'resultado' => 0],
                    ['id_equipe' => (int) $jogos['equipes_ids'][1], 'resultado' => 0],
                ],
            ]],
        ]);
        Assertions::assertJsonSuccess('Reserva futura é aplicada quando o jogo é materializado', $materialized);
        $connection = TestDatabase::connect($database);
        $materializedRow = $connection->query("SELECT id_jogo, data_jogo, inicio_jogo, termino_jogo, locais_id_local, duracao_jogo FROM jogos WHERE modalidades_id_modalidade = {$modality} AND nome_jogo = 'BLK:FUTURO' LIMIT 1")->fetch_assoc() ?: [];
        $connection->close();
        Assertions::assert(
            'Materialização preserva data, local e duração da reserva',
            (int) ($materializedRow['id_jogo'] ?? 0) > 0
            && $materializedRow['data_jogo'] === date('Y-m-d', strtotime('+11 days'))
            && substr((string) ($materializedRow['inicio_jogo'] ?? ''), 0, 5) === '10:00'
            && (int) ($materializedRow['locais_id_local'] ?? 0) === (int) $jogos['id_local']
            && (int) ($materializedRow['duracao_jogo'] ?? 0) === 1800,
        );

        $changedRetry = $client->postJson('api/v1/agenda-blocos', array_merge($payload, [
            'acao' => 'confirmar',
            'opcoes' => ['duracao_min' => 45],
        ]));
        Assertions::assert('Reenvio com a mesma identificação e parâmetros diferentes é rejeitado', $changedRetry['code'] === 422 && ($changedRetry['json']['success'] ?? true) === false);

        $invalidLocal = $client->postJson('api/v1/agenda-blocos', [
            'acao' => 'simular',
            'id_interclasse' => $edition,
            'id_modalidade' => $modality,
            'chave_tags' => [['id_modalidade' => $modality, 'chave_tag' => 'BLK:LOCAL-INVALIDO']],
            'janelas' => [['data' => date('Y-m-d', strtotime('+12 days')), 'inicio' => '08:00', 'fim' => '09:00', 'locais' => [999999]]],
            'opcoes' => ['duracao_min' => 30],
        ]);
        Assertions::assert('Local de outra edição ou inexistente é rejeitado antes da prévia', $invalidLocal['code'] === 422 && ($invalidLocal['json']['success'] ?? true) === false);
    }
}
