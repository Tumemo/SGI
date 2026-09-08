<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use App\Shared\Database\MysqliTransactionRunner;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class AtomicMutationTest
{
    public static function run(int $gameId): void
    {
        $name = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($name);
        $connection = TestDatabase::connect($name);
        $before = (int) $connection->query('SELECT COUNT(*) FROM artilheiros')->fetch_row()[0];
        $store = new MysqliMutationStore($connection);
        $identity = MutationIdentity::create('atomic-rollback-' . bin2hex(random_bytes(8)), 1, '{}');
        $store->begin('atomic.test', $identity);
        (new MysqliTransactionRunner($connection))->run(function () use ($connection, $gameId): void {
            $connection->query('INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, num_gol) VALUES (1, ' . $gameId . ', 1)');
        });
        try {
            $store->complete('atomic.test', $identity, 200, ['invalid_utf8' => "\xB1\x31"]);
            Assertions::assert('Falha ao persistir resposta impede confirmação parcial', false);
        } catch (\JsonException) {
            $store->cancel();
            $after = (int) $connection->query('SELECT COUNT(*) FROM artilheiros')->fetch_row()[0];
            Assertions::assert('Falha ao persistir resposta desfaz também a gravação aninhada', $before === $after);
        }
        Assertions::assert('Operação cancelada libera a chave para nova tentativa', $store->begin('atomic.test', $identity) === null);
        $store->cancel();
        $key = 'concurrent-' . bin2hex(random_bytes(8));
        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $pipes = [];
            $process = proc_open([
                PHP_BINARY, '-d', 'extension=mysqli', '-d', 'display_startup_errors=0',
                dirname(__DIR__) . '/Support/MutationWorker.php', $key, (string) $gameId,
            ], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('Não foi possível iniciar processo concorrente de teste.');
            }
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0) {
                throw new \RuntimeException('Falha no processo concorrente: ' . $errors);
            }
            $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        }
        Assertions::assert('Dois processos concorrentes recebem o mesmo registro', $results[0] === $results[1] && isset($results[0]['id']));
        $after = (int) $connection->query('SELECT COUNT(*) FROM artilheiros')->fetch_row()[0];
        Assertions::assert('Concorrência grava o gol apenas uma vez', $after === $before + 1);
        $connection->close();
    }
}
