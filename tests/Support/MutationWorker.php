<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap/autoload.php';
require __DIR__ . '/TestDatabase.php';

use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use SGITests\Support\TestDatabase;

$database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
TestDatabase::assertSafeDatabaseName($database);
$connection = TestDatabase::connect($database);
$store = new MysqliMutationStore($connection);
$gameId = (int) ($argv[2] ?? 0);
$identity = MutationIdentity::create((string) ($argv[1] ?? ''), 1, (string) $gameId);
try {
    $previous = $store->begin('concurrent.test', $identity);
    if ($previous !== null) {
        echo json_encode($previous['payload'], JSON_THROW_ON_ERROR);
    } else {
        usleep(250000);
        $statement = $connection->prepare('INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, num_gol) VALUES (1, ?, 1)');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $payload = ['id' => $connection->insert_id];
        $statement->close();
        $store->complete('concurrent.test', $identity, 200, $payload);
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }
} finally {
    $store->cancel();
    $connection->close();
}
