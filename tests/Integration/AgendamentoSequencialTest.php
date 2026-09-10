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
        echo "\n  \033[1;34m[Suite 6.2: Agendamento automático terça/quinta]\033[0m\n";
        $admin = new TestClient();
        $admin->login('admin', '123');

        $firstDay = self::nextTuesday();
        $secondDay = (new DateTimeImmutable($firstDay, new DateTimeZone('America/Sao_Paulo')))->modify('+2 days')->format('Y-m-d');
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

        $preview = $admin->postJson('api/v1/agenda-blocos', array_merge($payload, ['acao' => 'simular_sequencial']));
        Assertions::assertJsonSuccess('Prévia sequencial terça/quinta retorna sucesso', $preview);
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
    }

    private static function nextTuesday(): string
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
        $distance = (2 - (int) $today->format('N') + 7) % 7;
        return $today->modify('+' . $distance . ' days')->format('Y-m-d');
    }
}
