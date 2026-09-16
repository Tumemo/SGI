<?php

declare(strict_types=1);

namespace SGITests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class AgendamentoSequencialTest
{
    /** @param array<string,mixed> $jogos */
    public static function run(int $edition, int $modality, array $jogos): void
    {
        echo "\n  \033[1;34m[Suite 6.2: Agendamento automático segunda/quinta e dias livres]\033[0m\n";
        $admin = new TestClient();
        $admin->login('admin', '123');

        $firstDay = self::nextMonday();
        $secondDay = (new DateTimeImmutable($firstDay, new DateTimeZone('America/Sao_Paulo')))->modify('+3 days')->format('Y-m-d');
        $payload = [
            'id_interclasse' => $edition,
            'id_modalidade' => $modality,
            'todos_jogos' => true,
            'reprogramar' => true,
            'dias' => [
                ['data' => $firstDay, 'inicio' => '08:00', 'fim' => '11:30', 'local' => (int) $jogos['id_local']],
                ['data' => $secondDay, 'inicio' => '08:00', 'fim' => '11:30', 'local' => (int) $jogos['id_local']],
            ],
            'opcoes' => ['duracao_min' => 60, 'intervalo_troca_min' => 10],
        ];

        $freeDaysPayload = $payload;
        $freeDaysPayload['dias'] = [
            ['data' => (new DateTimeImmutable($firstDay, new DateTimeZone('America/Sao_Paulo')))->modify('+1 day')->format('Y-m-d'), 'inicio' => '08:00', 'fim' => '11:30', 'local' => (int) $jogos['id_local']],
            ['data' => (new DateTimeImmutable($firstDay, new DateTimeZone('America/Sao_Paulo')))->modify('+5 days')->format('Y-m-d'), 'inicio' => '08:00', 'fim' => '11:30', 'local' => (int) $jogos['id_local']],
        ];
        $freeDaysPreview = $admin->postJson('api/v1/agenda-blocos', array_merge($freeDaysPayload, ['acao' => 'simular_sequencial']));
        Assertions::assertJsonSuccess('Prévia aceita dias fora do padrão segunda/quinta', $freeDaysPreview);

        $payloadFromScreen = $payload;
        unset($payloadFromScreen['reprogramar']);
        $screenPreview = $admin->postJson('api/v1/agenda-blocos', array_merge($payloadFromScreen, ['acao' => 'simular_sequencial']));
        Assertions::assertJsonSuccess('Payload da tela calcula a prévia sem reprogramação implícita', $screenPreview);

        $preview = $admin->postJson('api/v1/agenda-blocos', array_merge($payload, ['acao' => 'simular_sequencial']));
        Assertions::assertJsonSuccess('Prévia sequencial segunda/quinta retorna sucesso', $preview);
        Assertions::assert(
            'Prévia sequencial aplica limite de 11h30 e intervalo de 10 minutos',
            ($preview['json']['intervalo_troca_min'] ?? 0) === 10
                && ($preview['json']['limite_termino_padrao'] ?? '') === '11:30'
                && ($preview['json']['pendencias'] ?? []) === [],
            json_encode($preview['json'] ?? $preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        Assertions::assert(
            'Prévia sequencial agenda as posições futuras da chave',
            ($preview['json']['resumo']['encaixados'] ?? 0) >= 4,
            json_encode($preview['json'] ?? $preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $idempotency = 'agenda-sequencial-teste-' . $modality;
        $confirm = $admin->postJson('api/v1/agenda-blocos', array_merge($payload, [
            'acao' => 'confirmar_sequencial',
            'revisao' => $preview['json']['revisao'] ?? 0,
            'idempotencia' => $idempotency,
        ]));
        Assertions::assertJsonSuccess('Confirmação sequencial grava a agenda completa', $confirm);
        $retry = $admin->postJson('api/v1/agenda-blocos', array_merge($payload, [
            'acao' => 'confirmar_sequencial',
            'revisao' => $preview['json']['revisao'] ?? 0,
            'idempotencia' => $idempotency,
        ]));
        Assertions::assert(
            'Reenvio da agenda sequencial é idempotente',
            ($retry['json']['idempotente'] ?? false) === true
                && ($retry['json']['id_bloco'] ?? 0) === ($confirm['json']['id_bloco'] ?? -1),
        );

        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $blockId = (int) ($confirm['json']['id_bloco'] ?? 0);
        $rows = $connection->query('SELECT chave_tag, data_reserva, inicio_reserva, termino_reserva, id_local, intervalo_troca_min, id_jogo FROM agenda_reservas WHERE id_bloco = ' . $blockId . ' ORDER BY data_reserva, inicio_reserva, chave_tag')->fetch_all(MYSQLI_ASSOC);
        $future = $connection->query("SELECT data_reserva, inicio_reserva, termino_reserva, id_jogo FROM agenda_reservas WHERE id_modalidade = {$modality} AND chave_tag = 'MM:2:0:N' ORDER BY id_reserva DESC LIMIT 1")->fetch_assoc() ?: [];
        $connection->close();

        Assertions::assert('Todas as reservas sequenciais usam intervalo de 10 minutos', $rows !== [] && count(array_unique(array_map(static fn (array $row): int => (int) $row['intervalo_troca_min'], $rows))) === 1 && (int) $rows[0]['intervalo_troca_min'] === 10);
        Assertions::assert('A reserva de fase futura fica disponível para materialização', ($future['data_reserva'] ?? '') !== '' && ($future['id_jogo'] ?? null) === null);

        $alreadyScheduledPreview = $admin->postJson('api/v1/agenda-blocos', array_merge($payloadFromScreen, ['acao' => 'simular_sequencial']));
        Assertions::assert(
            'Prévia sem reprogramação explica quando todos os jogos já estão agendados',
            ($alreadyScheduledPreview['code'] ?? 0) === 422
                && ($alreadyScheduledPreview['json']['success'] ?? true) === false
                && ($alreadyScheduledPreview['json']['message'] ?? '') === 'Não há jogos pendentes de agendamento nesta modalidade.',
            json_encode($alreadyScheduledPreview['json'] ?? $alreadyScheduledPreview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        self::assertNonSequentialModalityIsRejected($admin, $edition, $modality, $jogos);
    }

    /** @param array<string,mixed> $jogos */
    private static function assertNonSequentialModalityIsRejected(TestClient $admin, int $edition, int $mataModality, array $jogos): void
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $category = $connection->query('SELECT categorias_id_categoria FROM modalidades WHERE id_modalidade = ' . $mataModality . ' LIMIT 1')->fetch_assoc() ?: [];
        $type = $connection->query("SELECT id_tipo_modalidade FROM tipos_modalidades WHERE nome_tipo_modalidade = 'Individual' AND status_tipo_modalidade = '1' ORDER BY id_tipo_modalidade LIMIT 1")->fetch_assoc() ?: [];
        $name = 'SEQ-IND-' . bin2hex(random_bytes(5));
        $categoryId = (int) ($category['categorias_id_categoria'] ?? 0);
        $typeId = (int) ($type['id_tipo_modalidade'] ?? 0);
        if ($categoryId <= 0 || $typeId <= 0) {
            $connection->close();
            throw new \RuntimeException('A fixture não possui categoria e tipo Individual para a regressão do agendamento sequencial.');
        }
        $statement = $connection->prepare("INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse) VALUES (?, 'MASC', 10, 0, '1', ?, ?, ?)");
        $statement->bind_param('siii', $name, $typeId, $categoryId, $edition);
        $statement->execute();
        $modality = (int) $connection->insert_id;
        $statement->close();
        $connection->close();

        try {
            $response = $admin->postJson('api/v1/agenda-blocos', [
                'acao' => 'simular_sequencial',
                'id_interclasse' => $edition,
                'id_modalidade' => $modality,
                'todos_jogos' => true,
                'dias' => [[
                    'data' => self::nextMonday(),
                    'inicio' => '08:00',
                    'fim' => '11:30',
                    'local' => (int) $jogos['id_local'],
                ]],
                'opcoes' => ['duracao_min' => 60, 'intervalo_troca_min' => 10],
            ]);
            Assertions::assert(
                'Agendamento sequencial rejeita modalidade que não é Mata-Mata',
                ($response['code'] ?? 0) === 422
                    && ($response['json']['success'] ?? true) === false
                    && ($response['json']['message'] ?? '') === 'O agendamento automático está disponível somente para modalidades Mata-Mata.',
                json_encode($response['json'] ?? $response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        } finally {
            $connection = TestDatabase::connect($database);
            $statement = $connection->prepare('DELETE FROM modalidades WHERE id_modalidade = ? LIMIT 1');
            $statement->bind_param('i', $modality);
            $statement->execute();
            $statement->close();
            $connection->close();
        }
    }

    private static function nextMonday(): string
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
        $distance = (1 - (int) $today->format('N') + 7) % 7;
        return $today->modify('+' . $distance . ' days')->format('Y-m-d');
    }
}
