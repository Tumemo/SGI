<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap/autoload.php';
require __DIR__ . '/TestDatabase.php';

use App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepositoryAdapter;
use App\Modules\Competicoes\Infrastructure\MysqliEquipeRepository;
use App\Modules\Eventos\Infrastructure\MysqliEdicaoRepository;
use App\Modules\Participantes\Infrastructure\MysqliInscricaoRepository;
use App\Modules\Resultados\Infrastructure\MysqliArrecadacaoRepository;
use SGITests\Support\TestDatabase;

$database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
TestDatabase::assertSafeDatabaseName($database);
$connection = TestDatabase::connect($database);

/*
 * T13 already used this worker with (historyId, editionId). Keep that
 * invocation intact while allowing T18 to coordinate distinct scenarios.
 */
if (count($argv) <= 3 || !in_array((string) ($argv[1] ?? ''), ['arrecadacao', 'inscricao', 'equipe', 'edicao'], true)) {
    $historyId = (int) ($argv[1] ?? 0);
    $editionId = (int) ($argv[2] ?? 0);

    try {
        $result = (new MysqliArrecadacaoRepository($connection))->remove($historyId, $editionId);
        echo json_encode(['result' => $result], JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage());
        exit(1);
    } finally {
        $connection->close();
    }
    exit(0);
}

$scenario = (string) $argv[1];
$barrier = (string) ($argv[2] ?? '');
$workerId = (string) ($argv[3] ?? '');
if ($barrier === '' || $workerId === '') {
    fwrite(STDERR, 'Barreira concorrente ausente.');
    exit(1);
}

$ready = $barrier . DIRECTORY_SEPARATOR . 'ready-' . $workerId;
$release = $barrier . DIRECTORY_SEPARATOR . 'release';
if (@file_put_contents($ready, json_encode(['pid' => getmypid(), 'worker' => $workerId], JSON_THROW_ON_ERROR), LOCK_EX) === false) {
    fwrite(STDERR, 'Não foi possível anunciar o worker concorrente.');
    exit(1);
}

$deadline = microtime(true) + 10.0;
while (!is_file($release) && microtime(true) < $deadline) {
    usleep(10000);
}
if (!is_file($release)) {
    fwrite(STDERR, 'O coordenador não liberou o início do worker.');
    exit(1);
}

try {
    $result = match ($scenario) {
        'arrecadacao' => remove($connection, (int) ($argv[4] ?? 0), (int) ($argv[5] ?? 0)),
        'inscricao' => subscribe($connection, (int) ($argv[4] ?? 0), (int) ($argv[5] ?? 0), (int) ($argv[6] ?? 0)),
        'equipe' => createTeam($connection, (int) ($argv[4] ?? 0), (int) ($argv[5] ?? 0), (string) ($argv[6] ?? '')),
        'edicao' => edition($connection, (string) ($argv[4] ?? ''), array_slice($argv, 5)),
        default => throw new RuntimeException('Cenário concorrente desconhecido.'),
    };
    echo json_encode(['result' => $result], JSON_THROW_ON_ERROR);
} catch (mysqli_sql_exception $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
} catch (RuntimeException $exception) {
    // Rejeições de regra são resultados normais do cenário; falhas de processo
    // continuam sendo representadas por erro e exit 1 no bloco externo.
    echo json_encode(['result' => 'rejected', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
} finally {
    $connection->close();
}

function remove(mysqli $connection, int $historyId, int $editionId): string
{
    return (new MysqliArrecadacaoRepository($connection))->remove($historyId, $editionId);
}

function subscribe(mysqli $connection, int $userId, int $editionId, int $teamId): string
{
    $result = (new MysqliInscricaoRepository(
        $connection,
        new MysqliEquipePadraoRepositoryAdapter($connection),
    ))->subscribe($userId, $editionId, [$teamId]);
    return ($result['insercoes'] ?? 0) > 0 ? 'accepted' : 'existing';
}

function createTeam(mysqli $connection, int $modalityId, int $classId, string $name): string
{
    (new MysqliEquipeRepository($connection))->create([
        'modalidades_id_modalidade' => $modalityId,
        'turmas_id_turma' => $classId,
        'status_equipe' => '1',
        'nome_equipe' => $name === '' ? null : $name,
    ]);
    return 'accepted';
}

/** @param list<string> $arguments @return array<string,mixed>|string */
function edition(mysqli $connection, string $operation, array $arguments): array|string
{
    $repository = new MysqliEdicaoRepository(
        $connection,
        new MysqliEquipePadraoRepositoryAdapter($connection),
    );
    if ($operation === 'activate') {
        $editionId = (int) ($arguments[0] ?? 0);
        $repository->update($editionId, ['status_interclasse' => '1']);
        return 'accepted';
    }
    if ($operation === 'create') {
        $result = $repository->create([
            'nome_interclasse' => (string) ($arguments[0] ?? ''),
            'ano_interclasse' => (string) ($arguments[1] ?? ''),
        ]);
        return ['status' => 'accepted', ...$result];
    }
    throw new RuntimeException('Operação de edição desconhecida.');
}
