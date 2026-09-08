<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;
use App\Modules\Competicoes\Application\CronometroService;
use App\Modules\Competicoes\Infrastructure\MysqliCronometroRepository;
use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use JsonException;

final class CronometroPersistenceTest
{
    public static function run(int $modalityId, int $gameId): void
    {
        echo "\n  \033[1;34m[Suite 6.1: Persistência e replay do cronômetro]\033[0m\n";

        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($name);
        $connection = TestDatabase::connect($name);
        $localId = (int) $connection->query(
            'SELECT locais_id_local FROM jogos WHERE id_jogo = ' . $gameId . ' LIMIT 1',
        )->fetch_row()[0];
        $timerId = self::createGame($connection, $modalityId, $localId);
        $connection->close();

        $mesario = new TestClient();
        $mesario->login('mesario', '123');

        self::setState($timerId, $modalityId, 'Iniciado', 1200, 0, 1200, 'past');
        $paused = $mesario->putJson('api/jogos.php', [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
        ]);
        $pausedState = self::readState($timerId);
        $pausedRemaining = (int) (($paused['json']['cronometro']['saldo_segundos'] ?? -1));
        Assertions::assert('Pausa materializa o saldo de 1.170 segundos e limpa a referência',
            ($paused['json']['success'] ?? false) === true
            && $pausedRemaining >= 1169
            && $pausedRemaining <= 1170
            && (int) $pausedState['tempo_restante_jogo'] === $pausedRemaining
            && $pausedState['data_inicio_real'] === null,
        );

        self::setState($timerId, $modalityId, 'Pausado', 1200, 0, 1200, null);
        $snapshot = $mesario->putJson('api/jogos.php', [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
            'cronometro' => [
                'versao' => 2,
                'saldo_segundos' => 1170,
                'referencia_epoch_ms' => (int) ((time() - 30) * 1000),
            ],
        ]);
        $snapshotState = self::readState($timerId);
        Assertions::assert('Snapshot v2 atrasado preserva o saldo congelado',
            ($snapshot['json']['success'] ?? false) === true
            && (int) $snapshotState['tempo_restante_jogo'] === 1170
            && $snapshotState['data_inicio_real'] === null,
        );

        self::setState($timerId, $modalityId, 'Iniciado', 1200, 0, 1170, 'past-10');
        $listed = $mesario->get('api/jogos.php?id_jogo=' . $timerId);
        $listedRemaining = (int) (($listed['json'][0]['tempo_restante_calculado'] ?? -1));
        $listedReference = self::readReferenceEpoch($timerId);
        $listedNow = (int) floor(((int) ($listed['json'][0]['servidor_epoch_ms'] ?? (time() * 1000))) / 1000);
        $listedExpected = max(0, 1170 - max(0, $listedNow - $listedReference));
        Assertions::assert('Consulta usa o saldo persistido antes de descontar 10 segundos',
            $listedRemaining === $listedExpected
            && $listedRemaining < 1200
            && $listedRemaining >= 1159,
        );

        $pausedAgain = $mesario->putJson('api/jogos.php', [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
        ]);
        $pausedAgainState = self::readState($timerId);
        $pausedAgainRemaining = (int) (($pausedAgain['json']['cronometro']['saldo_segundos'] ?? -1));
        Assertions::assert('Retomar e pausar novamente não recupera a duração original',
            ($pausedAgain['json']['success'] ?? false) === true
            && (int) $pausedAgainState['tempo_restante_jogo'] === $pausedAgainRemaining
            && $pausedAgainRemaining <= $listedRemaining
            && $pausedAgainRemaining >= $listedRemaining - 1,
        );

        self::setState($timerId, $modalityId, 'Pausado', 1200, 0, 1170, null);
        $mutation = 'cronometro-replay-' . bin2hex(random_bytes(8));
        $body = [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
            'tempo_extra_jogo' => 60,
            'tempo_restante_jogo' => 1230,
        ];
        $first = $mesario->putJson('api/jogos.php', $body, ['X-SGI-Mutation-Id' => $mutation]);
        $retry = $mesario->putJson('api/jogos.php', $body, ['X-SGI-Mutation-Id' => $mutation]);
        $replayedState = self::readState($timerId);
        $stored = self::countMutations('jogos.put', $mutation);
        Assertions::assert('Reenvio idempotente não reaplica extra nem transição',
            ($first['json']['success'] ?? false) === true
            && ($retry['json'] ?? null) === ($first['json'] ?? null)
            && (int) $replayedState['tempo_extra_jogo'] === 60
            && (int) $replayedState['tempo_restante_jogo'] === 1230
            && $stored === 1,
        );

        $conflict = $mesario->putJson('api/jogos.php', [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
            'tempo_extra_jogo' => 120,
            'tempo_restante_jogo' => 1290,
        ], ['X-SGI-Mutation-Id' => $mutation]);
        Assertions::assert('Chave do cronômetro com corpo diferente é rejeitada', $conflict['code'] === 409);

        $beforeInvalid = self::readState($timerId);
        $invalid = $mesario->putJson('api/jogos.php', [
            'id_jogo' => $timerId,
            'status_jogo' => 'Pausado',
            'cronometro' => [
                'versao' => 2,
                'saldo_segundos' => -1,
                'referencia_epoch_ms' => (int) (time() * 1000),
            ],
        ]);
        $afterInvalid = self::readState($timerId);
        Assertions::assert('Snapshot inválido é rejeitado sem alterar o jogo',
            $invalid['code'] >= 400
            && $beforeInvalid === $afterInvalid,
        );

        self::setState($timerId, $modalityId, 'Pausado', 1200, 0, 1170, null);
        $beforeRollback = self::readState($timerId);
        $connection = TestDatabase::connect($name);
        $store = new MysqliMutationStore($connection);
        $identity = MutationIdentity::create('cronometro-rollback-' . bin2hex(random_bytes(8)), 2, '{}');
        $store->begin('jogos.put', $identity);
        $service = new CronometroService(new MysqliCronometroRepository($connection));
        $service->atualizar($timerId, ['status_jogo' => 'Iniciado']);
        try {
            $store->complete('jogos.put', $identity, 200, ['invalid_utf8' => "\xB1\x31"]);
            Assertions::assert('Falha na confirmação idempotente deveria abortar', false);
        } catch (JsonException) {
            $store->cancel();
            $afterRollback = self::readState($timerId);
            Assertions::assert('Falha na confirmação reverte também o snapshot do cronômetro', $beforeRollback === $afterRollback);
        }
        $connection->close();

        $connection = TestDatabase::connect($name);
        $statement = $connection->prepare('UPDATE jogos SET status_jogo = \'Agendado\', tempo_restante_jogo = NULL, tempo_extra_jogo = 0, data_inicio_real = NULL WHERE id_jogo = ?');
        $statement->bind_param('i', $timerId);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    private static function createGame(\mysqli $connection, int $modalityId, int $localId): int
    {
        $name = 'T08-CRON-' . bin2hex(random_bytes(5));
        $statement = $connection->prepare(
            "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, tempo_restante_jogo, duracao_jogo, tempo_extra_jogo, modalidades_id_modalidade, locais_id_local)\n             VALUES (?, CURDATE(), '00:00:00', '00:01:00', 'Agendado', NULL, 1200, 0, ?, ?)",
        );
        $statement->bind_param('sii', $name, $modalityId, $localId);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        return $id;
    }

    private static function setState(int $gameId, int $modalityId, string $status, int $duration, int $extra, int $remaining, ?string $reference): void
    {
        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($name);
        if ($reference === 'past') {
            $sql = 'UPDATE jogos SET status_jogo = ?, duracao_jogo = ?, tempo_extra_jogo = ?, tempo_restante_jogo = ?, data_inicio_real = DATE_SUB(NOW(), INTERVAL 30 SECOND), modalidades_id_modalidade = ? WHERE id_jogo = ?';
        } elseif ($reference === 'past-10') {
            $sql = 'UPDATE jogos SET status_jogo = ?, duracao_jogo = ?, tempo_extra_jogo = ?, tempo_restante_jogo = ?, data_inicio_real = DATE_SUB(NOW(), INTERVAL 10 SECOND), modalidades_id_modalidade = ? WHERE id_jogo = ?';
        } else {
            $sql = 'UPDATE jogos SET status_jogo = ?, duracao_jogo = ?, tempo_extra_jogo = ?, tempo_restante_jogo = ?, data_inicio_real = NULL, modalidades_id_modalidade = ? WHERE id_jogo = ?';
        }
        $statement = $connection->prepare($sql);
        $statement->bind_param('siiiii', $status, $duration, $extra, $remaining, $modalityId, $gameId);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    /** @return array<string, mixed> */
    private static function readState(int $gameId): array
    {
        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($name);
        $statement = $connection->prepare('SELECT status_jogo, duracao_jogo, tempo_extra_jogo, tempo_restante_jogo, data_inicio_real FROM jogos WHERE id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $state = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        $connection->close();
        return $state;
    }

    private static function readReferenceEpoch(int $gameId): int
    {
        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($name);
        $statement = $connection->prepare('SELECT UNIX_TIMESTAMP(data_inicio_real) FROM jogos WHERE id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $reference = (int) ($statement->get_result()->fetch_row()[0] ?? 0);
        $statement->close();
        $connection->close();
        return $reference;
    }

    private static function countMutations(string $route, string $key): int
    {
        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($name);
        $statement = $connection->prepare('SELECT COUNT(*) FROM sincronizacoes_idempotentes WHERE rota = ? AND chave_mutacao = ?');
        $statement->bind_param('ss', $route, $key);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_row()[0];
        $statement->close();
        $connection->close();
        return $count;
    }
}
