<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Competicoes\Application\EquipeLimiteException;
use App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository;
use App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepositoryAdapter;
use App\Modules\Competicoes\Infrastructure\MysqliEquipeRepository;
use App\Modules\Resultados\Infrastructure\MysqliArrecadacaoRepository;
use mysqli;
use SGITests\Support\Assertions;
use SGITests\Support\ProcessExitCode;
use SGITests\Support\TestDatabase;
use RuntimeException;

final class ConcurrentInvariantsTest
{
    /**
     * @param int $editionId edição confirmada pela fixture HTTP
     * @param int $classId turma confirmada pela fixture HTTP
     */
    public static function run(int $editionId, int $classId): void
    {
        echo "\n  \033[1;34m[Suite 2.6: Concorrência determinística de invariantes]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $fixture = self::createFixture($connection, $editionId, $classId);

        try {
            $fixture['occurrence_game_id'] = self::createOccurrenceGame($connection, $fixture);
            $fixture['history_ids'][] = self::runEstorno($connection, $fixture);
            self::runAutomaticRedConcurrency($connection, $fixture);
            self::runInscricao($connection, $fixture);
            self::runEquipes($connection, $fixture);
            self::runAdditionalTeamCases($connection, $fixture);
            self::runRosterSignupCompetition($connection, $fixture);
            self::runTeamTransferSnapshotRace($connection, $editionId, $classId);
            self::runTeamTransferGameInsertRace($connection, $editionId, $classId);
            self::runEditionConcurrency($connection, $editionId);
        } finally {
            self::restoreFixture($connection, $fixture);
            $connection->close();
        }
    }

    /** @param array<string, mixed> $fixture */
    private static function runEstorno(mysqli $connection, array $fixture): int
    {
        $editionId = (int) $fixture['edition_id'];
        $classId = (int) $fixture['class_id'];
        $historyId = self::insertHistory($connection, $fixture, 10, 20);
        self::updateClass($connection, $classId, '10.00', 50);

        $scenario = self::runWorkers(
            $connection,
            'arrecadacao',
            [$historyId, $editionId],
            static function (mysqli $parent) use ($editionId, $classId): void {
                self::lockEditionAndClass($parent, $editionId, $classId);
            },
            [$historyId, $editionId],
        );
        Assertions::assert(
            'Estorno concorrente aguarda os dois workers sob a trava do pai',
            $scenario['waited'],
            $scenario['diagnostic'],
        );
        $results = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $scenario['results']);
        sort($results);
        Assertions::assert(
            'Dois estornos do mesmo histórico removem uma única vez',
            $results === ['already_removed', 'removed'],
            json_encode($scenario['results']),
        );

        $row = self::classTotals(TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test'), $classId);
        Assertions::assert(
            'Estorno concorrente desconta quantidade e pontos somente uma vez',
            $row === ['quantidade' => '0.00', 'pontos' => 30],
            json_encode($row),
        );
        return $historyId;
    }

    /** @param array<string, mixed> $fixture */
    private static function runInscricao(mysqli $connection, array $fixture): void
    {
        $userId = (int) $fixture['user_id'];
        $editionId = (int) $fixture['edition_id'];
        $teamIds = array_values($fixture['signup_team_ids']);
        $scenario = self::runWorkers(
            $connection,
            'inscricao',
            [$userId, $editionId, $teamIds[0]],
            static function (mysqli $parent) use ($userId, $editionId): void {
                $statement = $parent->prepare('SELECT id_interclasse FROM interclasses WHERE id_interclasse = ? FOR UPDATE');
                $statement->bind_param('i', $editionId);
                $statement->execute();
                $statement->close();
                $statement = $parent->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = ? FOR UPDATE');
                $statement->bind_param('i', $userId);
                $statement->execute();
                $statement->close();
            },
            [$userId, $editionId, $teamIds[1]],
        );
        Assertions::assert(
            'Inscrição concorrente aguarda a trava do aluno antes do limite',
            $scenario['waited'],
            $scenario['diagnostic'],
        );
        $results = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $scenario['results']);
        sort($results);
        Assertions::assert(
            'Duas novas modalidades para aluno já em duas aceitam no máximo uma',
            $results === ['accepted', 'rejected'],
            json_encode($scenario['results']),
        );

        $read = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $read->prepare(
            'SELECT COUNT(DISTINCT m.id_modalidade)
             FROM equipes_has_usuarios eu
             INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe AND e.status_equipe = \'1\'
             INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade
             WHERE eu.usuarios_id_usuario = ? AND m.interclasses_id_interclasse = ?',
        );
        $statement->bind_param('ii', $userId, $editionId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $read->close();
        Assertions::assert('Inscrição concorrente mantém no máximo três modalidades', $count === 3, 'Encontradas: ' . $count);
    }

    /** @param array<string, mixed> $fixture */
    private static function runEquipes(mysqli $connection, array $fixture): void
    {
        $modalityId = (int) $fixture['team_modality_id'];
        $classId = (int) $fixture['class_id'];
        $scenario = self::runWorkers(
            $connection,
            'equipe',
            [$modalityId, $classId, 'Equipe manual'],
            static function (mysqli $parent) use ($modalityId, $classId): void {
                $statement = $parent->prepare('SELECT id_modalidade FROM modalidades WHERE id_modalidade = ? FOR UPDATE');
                $statement->bind_param('i', $modalityId);
                $statement->execute();
                $statement->close();
                $statement = $parent->prepare('SELECT id_turma FROM turmas WHERE id_turma = ? FOR UPDATE');
                $statement->bind_param('i', $classId);
                $statement->execute();
                $statement->close();
            },
            [$modalityId, $classId, ''],
        );
        Assertions::assert(
            'Criação concorrente de equipes aguarda modalidade/turma',
            $scenario['waited'],
            $scenario['diagnostic'],
        );
        $results = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $scenario['results']);
        sort($results);
        Assertions::assert(
            'Duas equipes para a última vaga aceitam no máximo uma',
            $results === ['accepted', 'rejected'],
            json_encode($scenario['results']),
        );

        $read = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $read->prepare(
            "SELECT COUNT(*) FROM equipes
             WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'",
        );
        $statement->bind_param('ii', $modalityId, $classId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $read->close();
        Assertions::assert('Criação concorrente não ultrapassa max_equipes=2', $count === 2, 'Encontradas: ' . $count);
    }

    /** @param array<string, mixed> $fixture */
    private static function runAdditionalTeamCases(mysqli $connection, array $fixture): void
    {
        $repository = new MysqliEquipeRepository($connection);
        $classId = (int) $fixture['class_id'];
        $limitOne = (int) $fixture['limit_one_modality_id'];
        $beforeLimitOne = self::teamCount($connection, $limitOne, $classId);
        $manualRejected = false;
        try {
            $repository->create([
                'modalidades_id_modalidade' => $limitOne,
                'turmas_id_turma' => $classId,
                'status_equipe' => '1',
                'nome_equipe' => 'Equipe manual',
            ]);
        } catch (EquipeLimiteException) {
            $manualRejected = true;
        }
        Assertions::assert('Limite1 recusa equipe manual quando já há uma ativa', $manualRejected);

        $automaticRejected = false;
        try {
            $repository->create([
                'modalidades_id_modalidade' => $limitOne,
                'turmas_id_turma' => $classId,
                'status_equipe' => '1',
                'nome_equipe' => null,
            ]);
        } catch (EquipeLimiteException) {
            $automaticRejected = true;
        }
        Assertions::assert('Limite1 recusa equipe automática pelo mesmo critério numérico', $automaticRejected);
        Assertions::assert('Rejeições de limite não fazem escrita parcial', self::teamCount($connection, $limitOne, $classId) === $beforeLimitOne);

        $unlimited = (int) $fixture['unlimited_modality_id'];
        $beforeUnlimited = self::teamCount($connection, $unlimited, $classId);
        $repository->create([
            'modalidades_id_modalidade' => $unlimited,
            'turmas_id_turma' => $classId,
            'status_equipe' => '1',
            'nome_equipe' => 'Ilimitada manual',
        ]);
        $repository->create([
            'modalidades_id_modalidade' => $unlimited,
            'turmas_id_turma' => $classId,
            'status_equipe' => '1',
            'nome_equipe' => null,
        ]);
        Assertions::assert('max_equipes=0 permanece ilimitado', self::teamCount($connection, $unlimited, $classId) === $beforeUnlimited + 2);

        $fullModality = (int) $fixture['team_modality_id'];
        $inactive = $repository->create([
            'modalidades_id_modalidade' => $fullModality,
            'turmas_id_turma' => $classId,
            'status_equipe' => '0',
            'nome_equipe' => 'T20 Reativação',
        ]);
        $reactivationRejected = false;
        try {
            $repository->update($inactive['id_equipe'], ['status_equipe' => '1']);
        } catch (EquipeLimiteException) {
            $reactivationRejected = true;
        }
        Assertions::assert('Reativação respeita a capacidade do destino', $reactivationRejected);
        Assertions::assert('Reativação recusada preserva as duas equipes ativas', self::teamCount($connection, $fullModality, $classId) === 2);

        $generatedModality = (int) $fixture['generation_modality_id'];
        $generated = MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($connection, $generatedModality, $classId);
        $repeated = MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($connection, $generatedModality, $classId);
        Assertions::assert('Geração padrão usa o mesmo limite e cria uma equipe', is_int($generated) && $generated > 0 && $repeated === $generated);

        $beforeFailure = self::teamCount($connection, $limitOne, $classId);
        $failed = false;
        try {
            $repository->create([
                'modalidades_id_modalidade' => $limitOne,
                'turmas_id_turma' => 999999,
                'status_equipe' => '1',
                'nome_equipe' => 'Falha transacional',
            ]);
        } catch (\Throwable) {
            $failed = true;
        }
        Assertions::assert('Falha de vínculo de equipe não persiste parcialmente', $failed && self::teamCount($connection, $limitOne, $classId) === $beforeFailure);
    }

    /** @param array<string, mixed> $fixture */
    private static function runRosterSignupCompetition(mysqli $connection, array $fixture): void
    {
        $editionId = (int) $fixture['edition_id'];
        $classId = (int) $fixture['class_id'];
        $modalityId = (int) $fixture['limit_one_modality_id'];
        $teamId = (int) $fixture['team_ids'][5];
        $studentIds = [];
        $student = $connection->prepare(
            "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario,
                                  genero_usuario, data_nasc_usuario, foto_usuario, status_usuario,
                                  turmas_id_turma, interclasses_id_interclasse)
             VALUES ('RM', ?, ?, ?, '3', 'MASC', '2010-01-01', '', '1', ?, ?)",
        );
        foreach (['gestão', 'portal'] as $source) {
            $registration = 't18_roster_' . bin2hex(random_bytes(6));
            $password = password_hash('t18-roster-secret', PASSWORD_DEFAULT);
            $name = 'Concorrente ' . $source . ' T18';
            $student->bind_param('sssii', $registration, $name, $password, $classId, $editionId);
            $student->execute();
            $studentIds[] = (int) $connection->insert_id;
        }
        $student->close();
        [$adminStudentId, $portalStudentId] = $studentIds;

        try {
            $scenario = self::runWorkerScenarios(
                $connection,
                [
                    ['scenario' => 'equipe-vincular', 'args' => [$teamId, $adminStudentId]],
                    ['scenario' => 'inscricao', 'args' => [$portalStudentId, $editionId, $teamId]],
                ],
                static function (mysqli $parent) use ($editionId): void {
                    $statement = $parent->prepare('SELECT id_interclasse FROM interclasses WHERE id_interclasse = ? LIMIT 1 FOR UPDATE');
                    $statement->bind_param('i', $editionId);
                    $statement->execute();
                    $statement->close();
                },
            );
            Assertions::assert(
                'Inclusão administrativa e inscrição disputam a última vaga sob a barreira da edição',
                $scenario['waited'],
                $scenario['diagnostic'],
            );
            $results = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $scenario['results']);
            sort($results);
            Assertions::assert(
                'A corrida entre gestão e portal aceita apenas um novo membro',
                $results === ['accepted', 'rejected'],
                json_encode($scenario['results']),
            );
            $statement = $connection->prepare(
                'SELECT COUNT(*) FROM equipes_has_usuarios WHERE equipes_id_equipe = ?',
            );
            $statement->bind_param('i', $teamId);
            $statement->execute();
            $memberCount = (int) $statement->get_result()->fetch_column();
            $statement->close();
            Assertions::assert(
                'A vaga concorrida mantém exatamente um vínculo persistido',
                $memberCount === 1,
                'Membros após a corrida: ' . $memberCount . ', modalidade: ' . $modalityId,
            );
        } finally {
            $deleteLink = $connection->prepare(
                'DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?',
            );
            $deleteStudent = $connection->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            foreach ($studentIds as $studentId) {
                $deleteLink->bind_param('ii', $teamId, $studentId);
                $deleteLink->execute();
                $deleteStudent->bind_param('i', $studentId);
                $deleteStudent->execute();
            }
            $deleteLink->close();
            $deleteStudent->close();
        }
    }

    private static function runTeamTransferSnapshotRace(mysqli $connection, int $editionId, int $classId): void
    {
        $categoryStatement = $connection->prepare(
            'SELECT categorias_id_categoria FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1',
        );
        $categoryStatement->bind_param('ii', $classId, $editionId);
        $categoryStatement->execute();
        $categoryId = (int) ($categoryStatement->get_result()->fetch_column() ?: 0);
        $categoryStatement->close();
        $typeId = (int) $connection->query("SELECT id_tipo_modalidade FROM tipos_modalidades WHERE status_tipo_modalidade = '1' ORDER BY id_tipo_modalidade LIMIT 1")->fetch_column();
        if ($categoryId <= 0 || $typeId <= 0) {
            throw new RuntimeException('A corrida L05 exige turma e tipo de modalidade válidos.');
        }

        $marker = bin2hex(random_bytes(6));
        $modalityIds = [];
        $teamId = 0;
        $studentId = 0;
        $modalityInsert = $connection->prepare(
            "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes,
                status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse)
             VALUES (?, 'MASC', 2, 10, '1', ?, ?, ?)",
        );
        foreach (['origem', 'destino'] as $namePart) {
            $name = 'L05 corrida ' . $namePart . ' ' . $marker;
            $modalityInsert->bind_param('siii', $name, $typeId, $categoryId, $editionId);
            $modalityInsert->execute();
            $modalityIds[] = (int) $connection->insert_id;
        }
        $modalityInsert->close();
        [$sourceModalityId, $targetModalityId] = $modalityIds;

        $teamName = 'L05 equipe concorrente ' . $marker;
        $team = $connection->prepare(
            "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
             VALUES ('1', ?, ?, ?)",
        );
        $team->bind_param('iis', $sourceModalityId, $classId, $teamName);
        $team->execute();
        $teamId = (int) $connection->insert_id;
        $team->close();

        $registration = 'l05_' . $marker . '_' . bin2hex(random_bytes(4));
        $studentName = 'L05 aluno concorrente ' . $marker;
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $student = $connection->prepare(
            "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario,
                genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma,
                interclasses_id_interclasse, chave_usuario_edicao)
             VALUES ('RM', ?, ?, ?, '3', 'MASC', '2010-01-01', '', '1', ?, ?, NULL)",
        );
        $student->bind_param('sssii', $registration, $studentName, $password, $classId, $editionId);
        $student->execute();
        $studentId = (int) $connection->insert_id;
        $student->close();

        $barrier = dirname(__DIR__, 2) . '/test-results/concurrency-l05-' . bin2hex(random_bytes(8));
        if (!mkdir($barrier, 0777, true) && !is_dir($barrier)) {
            self::deleteTeamTransferRaceFixture($connection, $studentId, $teamId, $modalityIds);
            throw new RuntimeException('Não foi possível criar a barreira da corrida L05.');
        }

        $locker = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $locker->begin_transaction();
        $lock = $locker->prepare('SELECT id_modalidade FROM modalidades WHERE id_modalidade = ? FOR UPDATE');
        $lock->bind_param('i', $targetModalityId);
        $lock->execute();
        $lock->close();
        $pipes = [];
        $process = null;
        $transactionOpen = true;
        try {
            $command = [
                PHP_BINARY,
                '-d',
                'display_startup_errors=0',
                dirname(__DIR__) . '/Support/ConcurrentScenarioWorker.php',
                'equipe-transfer',
                $barrier,
                '0',
                (string) $teamId,
                (string) $targetModalityId,
            ];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Não foi possível iniciar o worker de transferência L05.');
            }
            fclose($pipes[0]);

            $readyPath = $barrier . DIRECTORY_SEPARATOR . 'ready-0';
            $deadline = microtime(true) + 5.0;
            while (!is_file($readyPath) && microtime(true) < $deadline) {
                usleep(10000);
            }
            if (!is_file($readyPath)) {
                throw new RuntimeException('Worker de transferência L05 não anunciou prontidão.');
            }
            if (@file_put_contents($barrier . DIRECTORY_SEPARATOR . 'release', 'go', LOCK_EX) === false) {
                throw new RuntimeException('Não foi possível liberar a barreira da corrida L05.');
            }

            [$waited, $diagnostic] = self::waitForLockWaiters($locker, true);
            $adder = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
            try {
                (new MysqliEquipeRepository($adder))->addUsers($teamId, [$studentId]);
            } finally {
                $adder->close();
            }
            $locker->commit();
            $transactionOpen = false;

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $process = null;
            if ($exitCode !== 0) {
                throw new RuntimeException('Worker de transferência L05 falhou: ' . trim((string) $stderr));
            }
            $result = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);

            $state = $connection->prepare(
                'SELECT e.modalidades_id_modalidade, e.turmas_id_turma,
                    (SELECT COUNT(*) FROM equipes_has_usuarios eu WHERE eu.equipes_id_equipe = e.id_equipe) AS members
                 FROM equipes e WHERE e.id_equipe = ? LIMIT 1',
            );
            $state->bind_param('i', $teamId);
            $state->execute();
            $row = $state->get_result()->fetch_assoc() ?: [];
            $state->close();

            Assertions::assert(
                'Transferência L05 aguardou a inserção concorrente de elenco',
                $waited,
                $diagnostic,
            );
            Assertions::assert(
                'A equipe não se transfere após a inserção concorrente do aluno',
                ($result['result'] ?? '') === 'rejected'
                    && (int) ($row['modalidades_id_modalidade'] ?? 0) === $sourceModalityId
                    && (int) ($row['turmas_id_turma'] ?? 0) === $classId
                    && (int) ($row['members'] ?? 0) === 1,
                json_encode(['resultado' => $result, 'estado' => $row]),
            );
        } finally {
            if ($transactionOpen) {
                $locker->rollback();
            }
            $locker->close();
            if (is_resource($process)) {
                file_put_contents($barrier . DIRECTORY_SEPARATOR . 'release', 'go', LOCK_EX);
                $status = proc_get_status($process);
                if ($status['running']) {
                    proc_terminate($process);
                }
                foreach ([1, 2] as $pipeIndex) {
                    if (isset($pipes[$pipeIndex]) && is_resource($pipes[$pipeIndex])) {
                        fclose($pipes[$pipeIndex]);
                    }
                }
                proc_close($process);
            }
            foreach (glob($barrier . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($barrier);
            self::deleteTeamTransferRaceFixture($connection, $studentId, $teamId, $modalityIds);
        }
    }

    /** @param list<int> $modalityIds */
    private static function deleteTeamTransferRaceFixture(mysqli $connection, int $studentId, int $teamId, array $modalityIds): void
    {
        if ($teamId > 0) {
            $deleteLink = $connection->prepare('DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ?');
            $deleteLink->bind_param('i', $teamId);
            $deleteLink->execute();
            $deleteLink->close();
            $deleteTeam = $connection->prepare('DELETE FROM equipes WHERE id_equipe = ?');
            $deleteTeam->bind_param('i', $teamId);
            $deleteTeam->execute();
            $deleteTeam->close();
        }
        if ($studentId > 0) {
            $deleteStudent = $connection->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            $deleteStudent->bind_param('i', $studentId);
            $deleteStudent->execute();
            $deleteStudent->close();
        }
        foreach ($modalityIds as $modalityId) {
            $deleteModality = $connection->prepare('DELETE FROM modalidades WHERE id_modalidade = ?');
            $deleteModality->bind_param('i', $modalityId);
            $deleteModality->execute();
            $deleteModality->close();
        }
    }

    private static function runTeamTransferGameInsertRace(mysqli $connection, int $editionId, int $classId): void
    {
        $categoryStatement = $connection->prepare(
            'SELECT categorias_id_categoria FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1',
        );
        $categoryStatement->bind_param('ii', $classId, $editionId);
        $categoryStatement->execute();
        $categoryId = (int) ($categoryStatement->get_result()->fetch_column() ?: 0);
        $categoryStatement->close();
        $typeId = (int) $connection->query("SELECT id_tipo_modalidade FROM tipos_modalidades WHERE status_tipo_modalidade = '1' ORDER BY id_tipo_modalidade LIMIT 1")->fetch_column();
        if ($categoryId <= 0 || $typeId <= 0) {
            throw new RuntimeException('A corrida de partida L05 exige turma e tipo de modalidade válidos.');
        }

        $marker = bin2hex(random_bytes(6));
        $modalityIds = [];
        $localId = 0;
        $teamId = 0;
        $modalityInsert = $connection->prepare(
            "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes,
                status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse)
             VALUES (?, 'MASC', 2, 10, '1', ?, ?, ?)",
        );
        foreach (['origem', 'destino'] as $namePart) {
            $name = 'L05 jogo concorrente ' . $namePart . ' ' . $marker;
            $modalityInsert->bind_param('siii', $name, $typeId, $categoryId, $editionId);
            $modalityInsert->execute();
            $modalityIds[] = (int) $connection->insert_id;
        }
        $modalityInsert->close();
        [$sourceModalityId, $targetModalityId] = $modalityIds;

        $teamName = 'L05 equipe jogo ' . $marker;
        $team = $connection->prepare(
            "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
             VALUES ('1', ?, ?, ?)",
        );
        $team->bind_param('iis', $sourceModalityId, $classId, $teamName);
        $team->execute();
        $teamId = (int) $connection->insert_id;
        $team->close();

        $gameName = 'L05 corrida jogo ' . $marker;

        $localName = 'L05 local ' . $marker;
        $local = $connection->prepare(
            "INSERT INTO locais (nome_local, disponivel_local, status_local, interclasses_id_interclasse)
             VALUES (?, '1', '1', ?)",
        );
        $local->bind_param('si', $localName, $editionId);
        $local->execute();
        $localId = (int) $connection->insert_id;
        $local->close();

        $barrier = dirname(__DIR__, 2) . '/test-results/concurrency-l05-game-' . bin2hex(random_bytes(8));
        if (!mkdir($barrier, 0777, true) && !is_dir($barrier)) {
            self::deleteTeamGameRaceFixture($connection, $teamId, $modalityIds, $localId, $gameName);
            throw new RuntimeException('Não foi possível criar a barreira da inserção de partida L05.');
        }

        $updater = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        \App\Shared\Database\Transaction::begin($updater);
        $outerTransactionOpen = true;
        $teamLock = $updater->prepare('SELECT id_equipe FROM equipes WHERE id_equipe = ? FOR UPDATE');
        $teamLock->bind_param('i', $teamId);
        $teamLock->execute();
        $teamLock->close();
        (new MysqliEquipeRepository($updater))->update($teamId, [
            'modalidades_id_modalidade' => $targetModalityId,
        ]);

        $pipes = [];
        $process = null;
        try {
            $command = [
                PHP_BINARY,
                '-d',
                'display_startup_errors=0',
                dirname(__DIR__) . '/Support/ConcurrentScenarioWorker.php',
                'jogo-partida',
                $barrier,
                '0',
                $gameName,
                '2049-09-12',
                '08:00:00',
                '08:30:00',
                (string) $sourceModalityId,
                (string) $localId,
                (string) $teamId,
            ];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Não foi possível iniciar o worker de partida L05.');
            }
            fclose($pipes[0]);

            $readyPath = $barrier . DIRECTORY_SEPARATOR . 'ready-0';
            $deadline = microtime(true) + 5.0;
            while (!is_file($readyPath) && microtime(true) < $deadline) {
                usleep(10000);
            }
            if (!is_file($readyPath)) {
                throw new RuntimeException('Worker de partida L05 não anunciou prontidão.');
            }
            if (@file_put_contents($barrier . DIRECTORY_SEPARATOR . 'release', 'go', LOCK_EX) === false) {
                throw new RuntimeException('Não foi possível liberar a barreira da inserção de partida L05.');
            }

            [$waited, $diagnostic] = self::waitForLockWaiters($updater, true);
            \App\Shared\Database\Transaction::commit($updater);
            $outerTransactionOpen = false;

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $process = null;
            if ($exitCode !== 0) {
                throw new RuntimeException('Worker de partida L05 falhou: ' . trim((string) $stderr));
            }
            $result = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);

            $gameCount = $connection->prepare('SELECT COUNT(*) FROM jogos WHERE nome_jogo = ?');
            $gameCount->bind_param('s', $gameName);
            $gameCount->execute();
            $createdGames = (int) $gameCount->get_result()->fetch_column();
            $gameCount->close();
            $partidaCount = $connection->prepare('SELECT COUNT(*) FROM partidas WHERE equipes_id_equipe = ?');
            $partidaCount->bind_param('i', $teamId);
            $partidaCount->execute();
            $createdMatches = (int) $partidaCount->get_result()->fetch_column();
            $partidaCount->close();
            $teamState = $connection->prepare('SELECT modalidades_id_modalidade FROM equipes WHERE id_equipe = ?');
            $teamState->bind_param('i', $teamId);
            $teamState->execute();
            $currentModality = (int) ($teamState->get_result()->fetch_column() ?: 0);
            $teamState->close();

            Assertions::assert(
                'Criação de partida aguarda uma transferência concorrente da equipe',
                $waited,
                $diagnostic,
            );
            Assertions::assert(
                'Partida não é anexada à equipe após sua transferência de modalidade',
                ($result['result'] ?? '') === 'rejected'
                    && $currentModality === $targetModalityId
                    && $createdGames === 0
                    && $createdMatches === 0,
                json_encode([
                    'resultado' => $result,
                    'modalidade_equipe' => $currentModality,
                    'jogos_criados' => $createdGames,
                    'partidas_criadas' => $createdMatches,
                ]),
            );
        } finally {
            if ($outerTransactionOpen) {
                \App\Shared\Database\Transaction::rollback($updater);
            }
            $updater->close();
            if (is_resource($process)) {
                file_put_contents($barrier . DIRECTORY_SEPARATOR . 'release', 'go', LOCK_EX);
                $status = proc_get_status($process);
                if ($status['running']) {
                    proc_terminate($process);
                }
                foreach ([1, 2] as $pipeIndex) {
                    if (isset($pipes[$pipeIndex]) && is_resource($pipes[$pipeIndex])) {
                        fclose($pipes[$pipeIndex]);
                    }
                }
                proc_close($process);
            }
            foreach (glob($barrier . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($barrier);
            self::deleteTeamGameRaceFixture($connection, $teamId, $modalityIds, $localId, $marker);
        }
    }

    /** @param list<int> $modalityIds */
    private static function deleteTeamGameRaceFixture(mysqli $connection, int $teamId, array $modalityIds, int $localId, string $gameName): void
    {
        $deleteGames = $connection->prepare('SELECT id_jogo FROM jogos WHERE nome_jogo = ?');
        $deleteGames->bind_param('s', $gameName);
        $deleteGames->execute();
        $gameIds = array_map('intval', array_column($deleteGames->get_result()->fetch_all(MYSQLI_ASSOC), 'id_jogo'));
        $deleteGames->close();
        foreach ($gameIds as $gameId) {
            $deleteMatches = $connection->prepare('DELETE FROM partidas WHERE jogos_id_jogo = ?');
            $deleteMatches->bind_param('i', $gameId);
            $deleteMatches->execute();
            $deleteMatches->close();
            $deleteGame = $connection->prepare('DELETE FROM jogos WHERE id_jogo = ?');
            $deleteGame->bind_param('i', $gameId);
            $deleteGame->execute();
            $deleteGame->close();
        }
        if ($teamId > 0) {
            $deleteTeam = $connection->prepare('DELETE FROM equipes WHERE id_equipe = ?');
            $deleteTeam->bind_param('i', $teamId);
            $deleteTeam->execute();
            $deleteTeam->close();
        }
        foreach ($modalityIds as $modalityId) {
            $deleteModality = $connection->prepare('DELETE FROM modalidades WHERE id_modalidade = ?');
            $deleteModality->bind_param('i', $modalityId);
            $deleteModality->execute();
            $deleteModality->close();
        }
        if ($localId > 0) {
            $deleteLocal = $connection->prepare('DELETE FROM locais WHERE id_local = ?');
            $deleteLocal->bind_param('i', $localId);
            $deleteLocal->execute();
            $deleteLocal->close();
        }
    }

    private static function runEditionConcurrency(mysqli $connection, int $baseEditionId): void
    {
        $switchFixture = self::createEditionSwitchFixture($connection);
        $createdEditionIds = [];
        try {
            $activation = self::runWorkers(
                $connection,
                'edicao',
                ['activate', $switchFixture['edition_a']],
                static function (mysqli $parent): callable {
                    return self::acquireEditionLock($parent);
                },
                ['activate', $switchFixture['edition_b']],
            );
            Assertions::assert(
                'Ativação concorrente aguarda a trava nomeada da edição',
                $activation['waited'],
                $activation['diagnostic'],
            );
            $activationResults = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $activation['results']);
            sort($activationResults);
            Assertions::assert(
                'Ativar A/B concorrentemente não expõe erro SQL',
                $activationResults === ['accepted', 'accepted'],
                json_encode($activation['results']),
            );
            $activeSwitch = self::activeEditionIds($connection, [$switchFixture['edition_a'], $switchFixture['edition_b']]);
            Assertions::assert('Ativar A/B concorrentemente mantém no máximo uma ativa', count($activeSwitch) === 1);
            $activeEdition = (int) $activeSwitch[0];
            $inactiveEdition = $activeEdition === $switchFixture['edition_a'] ? $switchFixture['edition_b'] : $switchFixture['edition_a'];
            Assertions::assert(
                'Alternância de edição não bloqueia contas de alunos',
                self::userStatus($connection, $switchFixture['user_a']) === '0'
                    && self::userStatus($connection, $switchFixture['user_b']) === '0',
            );
            Assertions::assert('Edição inativa permanece desativada após a ativação concorrente', self::editionStatus($connection, $inactiveEdition) === '0');
            $activeBeforeInvalid = self::activeEditionIds($connection, [$switchFixture['edition_a'], $switchFixture['edition_b']]);
            $invalidRejected = false;
            try {
                (new \App\Modules\Eventos\Infrastructure\MysqliEdicaoRepository(
                    $connection,
                    new MysqliEquipePadraoRepositoryAdapter($connection),
                ))->update(999999, ['status_interclasse' => '1']);
            } catch (RuntimeException) {
                $invalidRejected = true;
            }
            Assertions::assert('ID de edição inexistente não desativa a edição ativa', $invalidRejected && $activeBeforeInvalid === self::activeEditionIds($connection, [$switchFixture['edition_a'], $switchFixture['edition_b']]));

            $creation = self::runWorkers(
                $connection,
                'edicao',
                ['create', 'T21 Concorrente A ' . bin2hex(random_bytes(3)), '2030-01-01'],
                static function (mysqli $parent): callable {
                    return self::acquireEditionLock($parent);
                },
                ['create', 'T21 Concorrente B ' . bin2hex(random_bytes(3)), '2031-01-01'],
            );
            Assertions::assert(
                'Criação concorrente aguarda a trava nomeada da edição',
                $creation['waited'],
                $creation['diagnostic'],
            );
            $creationRows = [];
            foreach ($creation['results'] as $row) {
                $result = $row['result'] ?? null;
                if (is_array($result) && ($result['status'] ?? '') === 'accepted' && (int) ($result['id'] ?? 0) > 0) {
                    $createdEditionIds[] = (int) $result['id'];
                    $creationRows[] = $result;
                }
            }
            Assertions::assert(
                'Duas criações concorrentes não expõem erro SQL',
                count($creationRows) === 2,
                json_encode($creation['results']),
            );
            $allCreatedValid = count($creationRows) === 2;
            foreach ($createdEditionIds as $editionId) {
                $allCreatedValid = $allCreatedValid && self::editionHasDefaultFixture($connection, $editionId);
            }
            Assertions::assert('Criações concorrentes preservam as fixtures de ambas as edições', $allCreatedValid);
            Assertions::assert(
                'Criação concorrente mantém no máximo uma edição ativa',
                self::activeEditionCount($connection) <= 1,
            );
        } finally {
            self::deleteEditionFixtures($connection, $createdEditionIds);
            self::deleteEditionFixtures($connection, [$switchFixture['edition_a'], $switchFixture['edition_b']], [$switchFixture['user_a'], $switchFixture['user_b']]);
            $restore = $connection->prepare("UPDATE interclasses SET status_interclasse = '0' WHERE id_interclasse <> ?");
            $restore->bind_param('i', $baseEditionId);
            $restore->execute();
            $restore->close();
            $restore = $connection->prepare("UPDATE interclasses SET status_interclasse = '1' WHERE id_interclasse = ?");
            $restore->bind_param('i', $baseEditionId);
            $restore->execute();
            $restore->close();
        }
    }

    private static function teamCount(mysqli $connection, int $modalityId, int $classId): int
    {
        $statement = $connection->prepare("SELECT COUNT(*) FROM equipes WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'");
        $statement->bind_param('ii', $modalityId, $classId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    /** @return array{edition_a:int,edition_b:int,user_a:int,user_b:int} */
    private static function createEditionSwitchFixture(mysqli $connection): array
    {
        $editionIds = [];
        $userIds = [];
        foreach (['T21 Ativação A', 'T21 Ativação B'] as $index => $name) {
            $year = (string) (2040 + $index) . '-01-01';
            $edition = $connection->prepare("INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse) VALUES (?, ?, '', '0')");
            $edition->bind_param('ss', $name, $year);
            $edition->execute();
            $editionIds[] = (int) $connection->insert_id;
            $edition->close();

            $matricula = 't21_' . strtolower((string) $index) . '_' . bin2hex(random_bytes(4));
            $password = password_hash('t21-secret', PASSWORD_DEFAULT);
            $user = $connection->prepare(
                "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, interclasses_id_interclasse)
                 VALUES ('RM', ?, ?, ?, '3', 'MASC', '2000-01-01', '', '0', ?)",
            );
            $userName = 'T21 Aluno ' . $index;
            $user->bind_param('sssi', $matricula, $userName, $password, $editionIds[$index]);
            $user->execute();
            $userIds[] = (int) $connection->insert_id;
            $user->close();
        }
        return [
            'edition_a' => $editionIds[0],
            'edition_b' => $editionIds[1],
            'user_a' => $userIds[0],
            'user_b' => $userIds[1],
        ];
    }

    /** @param list<int> $editionIds @param list<int> $userIds */
    private static function deleteEditionFixtures(mysqli $connection, array $editionIds, array $userIds = []): void
    {
        $editionIds = array_values(array_filter(array_map('intval', $editionIds), static fn (int $id): bool => $id > 0));
        if ($editionIds === []) {
            return;
        }
        $list = implode(',', $editionIds);
        $connection->query("DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe IN (SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade IN (SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse IN ($list)))");
        $connection->query("DELETE FROM equipes WHERE modalidades_id_modalidade IN (SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse IN ($list))");
        $connection->query("DELETE FROM partidas WHERE jogos_id_jogo IN (SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade IN (SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse IN ($list)))");
        $connection->query("DELETE FROM jogos WHERE modalidades_id_modalidade IN (SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse IN ($list))");
        $connection->query("DELETE FROM historico_arrecadacoes WHERE id_interclasse IN ($list)");
        $connection->query("DELETE FROM ocorrencias_turmas WHERE interclasses_id_interclasse IN ($list)");
        $connection->query("DELETE FROM usuarios_has_interclasses WHERE interclasses_id_interclasse IN ($list)");
        $connection->query("DELETE FROM modalidades WHERE interclasses_id_interclasse IN ($list)");
        $connection->query("DELETE FROM turmas WHERE interclasses_id_interclasse IN ($list)");
        $connection->query("DELETE FROM locais WHERE interclasses_id_interclasse IN ($list)");
        $connection->query("DELETE FROM categorias WHERE interclasses_id_interclasse IN ($list)");
        if ($userIds !== []) {
            $userList = implode(',', array_values(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
            if ($userList !== '') {
                $connection->query("DELETE FROM usuarios WHERE id_usuario IN ($userList)");
            }
        } else {
            $connection->query("DELETE FROM usuarios WHERE interclasses_id_interclasse IN ($list)");
        }
        $connection->query("DELETE FROM interclasses WHERE id_interclasse IN ($list)");
    }

    private static function acquireEditionLock(mysqli $connection): callable
    {
        $name = self::editionLockName($connection);
        $statement = $connection->prepare('SELECT GET_LOCK(?, 5)');
        $statement->bind_param('s', $name);
        $statement->execute();
        $acquired = (int) ($statement->get_result()->fetch_column() ?? 0);
        $statement->close();
        if ($acquired !== 1) {
            throw new RuntimeException('O coordenador não adquiriu a trava nomeada da edição.');
        }
        return static function () use ($connection, $name): void {
            $statement = $connection->prepare('SELECT RELEASE_LOCK(?)');
            $statement->bind_param('s', $name);
            $statement->execute();
            $statement->close();
        };
    }

    private static function editionLockName(mysqli $connection): string
    {
        $database = (string) ($connection->query('SELECT DATABASE()')->fetch_column() ?? '');
        return sha1($database . ':edicao-ativa');
    }

    /** @param list<int> $editionIds @return list<int> */
    private static function activeEditionIds(mysqli $connection, array $editionIds): array
    {
        $list = implode(',', array_map('intval', $editionIds));
        $rows = $connection->query("SELECT id_interclasse FROM interclasses WHERE id_interclasse IN ($list) AND status_interclasse = '1' ORDER BY id_interclasse")->fetch_all(MYSQLI_ASSOC);
        return array_map(static fn (array $row): int => (int) $row['id_interclasse'], $rows);
    }

    private static function activeEditionCount(mysqli $connection): int
    {
        return (int) $connection->query("SELECT COUNT(*) FROM interclasses WHERE status_interclasse = '1'")->fetch_column();
    }

    private static function editionStatus(mysqli $connection, int $editionId): string
    {
        $statement = $connection->prepare('SELECT status_interclasse FROM interclasses WHERE id_interclasse = ?');
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $status = (string) ($statement->get_result()->fetch_column() ?? '');
        $statement->close();
        return $status;
    }

    private static function userStatus(mysqli $connection, int $userId): string
    {
        $statement = $connection->prepare('SELECT status_usuario FROM usuarios WHERE id_usuario = ?');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $status = (string) ($statement->get_result()->fetch_column() ?? '');
        $statement->close();
        return $status;
    }

    private static function editionHasDefaultFixture(mysqli $connection, int $editionId): bool
    {
        $statement = $connection->prepare(
            "SELECT
                (SELECT COUNT(*) FROM categorias WHERE interclasses_id_interclasse = ?) AS categorias,
                (SELECT COUNT(*) FROM turmas WHERE interclasses_id_interclasse = ?) AS turmas,
                (SELECT COUNT(*) FROM modalidades WHERE interclasses_id_interclasse = ?) AS modalidades,
                (SELECT COUNT(*) FROM equipes e INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = ?) AS equipes",
        );
        $statement->bind_param('iiii', $editionId, $editionId, $editionId, $editionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        return (int) ($row['categorias'] ?? 0) >= 2
            && (int) ($row['turmas'] ?? 0) >= 7
            && (int) ($row['modalidades'] ?? 0) >= 10
            && (int) ($row['equipes'] ?? 0) >= 35;
    }

    /**
     * @param list<int|string> $workerArgs
     * @param callable(mysqli):void $lock
     * @param list<list<int|string>> $additionalArgs
     * @return array{waited:bool,diagnostic:string,results:list<array<string,mixed>>}
     */
    private static function runWorkers(mysqli $connection, string $scenario, array $workerArgs, callable $lock, array ...$additionalArgs): array
    {
        $workers = [['scenario' => $scenario, 'args' => $workerArgs]];
        foreach ($additionalArgs as $args) {
            $workers[] = ['scenario' => $scenario, 'args' => $args];
        }

        return self::runWorkerScenarios($connection, $workers, $lock);
    }

    /**
     * @param list<array{scenario:string,args:list<int|string>}> $workers
     * @param callable(mysqli):void $lock
     * @return array{waited:bool,diagnostic:string,results:list<array<string,mixed>>}
     */
    private static function runWorkerScenarios(mysqli $connection, array $workers, callable $lock): array
    {
        $connection->begin_transaction();
        $transactionOpen = true;
        $barrier = dirname(__DIR__, 2) . '/test-results/concurrency-t18-' . bin2hex(random_bytes(8));
        if (!mkdir($barrier, 0777, true) && !is_dir($barrier)) {
            $connection->rollback();
            throw new RuntimeException('Não foi possível criar a barreira do teste concorrente.');
        }

        $processes = [];
        $releaseLock = null;
        try {
            $candidateRelease = $lock($connection);
            if (is_callable($candidateRelease)) {
                $releaseLock = $candidateRelease;
            }
            foreach ($workers as $index => $worker) {
                $pipes = [];
                $command = [
                    PHP_BINARY,
                    '-d',
                    'display_startup_errors=0',
                    dirname(__DIR__) . '/Support/ConcurrentScenarioWorker.php',
                    $worker['scenario'],
                    $barrier,
                    (string) $index,
                    ...array_map(static fn (int|string $value): string => (string) $value, $worker['args']),
                ];
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) {
                    throw new RuntimeException('Não foi possível iniciar processo concorrente de ' . $worker['scenario'] . '.');
                }
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }

            $deadline = microtime(true) + 5.0;
            while (microtime(true) < $deadline) {
                $ready = true;
                foreach (array_keys($workers) as $index) {
                    if (!is_file($barrier . '/ready-' . $index)) {
                        $ready = false;
                        break;
                    }
                }
                if ($ready) {
                    break;
                }
                usleep(10000);
            }
            foreach (array_keys($workers) as $index) {
                if (!is_file($barrier . '/ready-' . $index)) {
                    throw new RuntimeException('Worker ' . $index . ' não anunciou pronto em ' . $workers[$index]['scenario'] . '.');
                }
            }
            file_put_contents($barrier . '/release', 'go', LOCK_EX);

            [$waited, $diagnostic] = self::waitForLockWaiters($connection, $releaseLock !== null);
            $connection->commit();
            $transactionOpen = false;
            if ($releaseLock !== null) {
                $releaseLock();
                $releaseLock = null;
            }
            $results = self::collectWorkers($processes);
            return ['waited' => $waited, 'diagnostic' => $diagnostic, 'results' => $results];
        } finally {
            if ($releaseLock !== null) {
                $releaseLock();
            }
            if ($transactionOpen) {
                $connection->rollback();
            }
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    $status = proc_get_status($process);
                    if ($status['running']) {
                        proc_terminate($process);
                    }
                }
                foreach ([1, 2] as $pipeIndex) {
                    if (isset($pipes[$pipeIndex]) && is_resource($pipes[$pipeIndex])) {
                        fclose($pipes[$pipeIndex]);
                    }
                }
                if (is_resource($process)) {
                    proc_close($process);
                }
            }
            foreach (glob($barrier . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($barrier);
        }
    }

    /** @param list<array{0:resource,1:array<int,resource>}> $processes @return list<array<string,mixed>> */
    private static function collectWorkers(array $processes): array
    {
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $observedExitCode = -1;
            $deadline = microtime(true) + 10.0;
            do {
                $status = proc_get_status($process);
                $observedExitCode = ProcessExitCode::observe($observedExitCode, $status) ?? -1;
                if (!$status['running']) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            if ($status['running']) {
                proc_terminate($process);
                throw new RuntimeException('Worker concorrente excedeu o prazo.');
            }
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $closeExitCode = proc_close($process);
            if (ProcessExitCode::resolve($closeExitCode, $observedExitCode) !== 0) {
                throw new RuntimeException('Falha no worker concorrente: ' . $errors);
            }
            $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                throw new RuntimeException('Resposta inválida de worker concorrente.');
            }
            $results[] = $decoded;
        }
        return $results;
    }

    /** @return array{0:bool,1:string} */
    private static function waitForLockWaiters(mysqli $connection, bool $namedLock): array
    {
        $deadline = microtime(true) + 5.0;
        $last = [];
        while (microtime(true) < $deadline) {
            $lockWaits = (int) $connection->query("SELECT COUNT(*) FROM information_schema.INNODB_TRX WHERE trx_state = 'LOCK WAIT'")->fetch_column();
            $trxWaits = (int) $connection->query("SELECT COUNT(*) FROM information_schema.INNODB_TRX WHERE trx_state = 'LOCK WAIT'")->fetch_column();
            $processList = $connection->query('SELECT STATE, INFO FROM information_schema.PROCESSLIST WHERE ID <> CONNECTION_ID() AND DB = DATABASE()');
            $states = [];
            while ($processList !== false && ($row = $processList->fetch_assoc()) !== null) {
                $states[] = ($row['STATE'] ?? '') . '|' . ($row['INFO'] ?? '');
            }
            $last = ['lock_waits' => $lockWaits, 'trx_waits' => $trxWaits, 'states' => $states];
            $forUpdateWaits = count(array_filter($states, static fn (string $state): bool => str_contains($state, 'FOR UPDATE')));
            $namedLockWaits = count(array_filter($states, static fn (string $state): bool => str_contains($state, 'GET_LOCK')));
            $threshold = $namedLock ? 1 : 2;
            if (max($lockWaits, $trxWaits, $forUpdateWaits, $namedLockWaits) >= $threshold) {
                return [true, json_encode($last) ?: ''];
            }
            usleep(20000);
        }
        return [false, 'Esperas observadas: ' . (json_encode($last) ?: '{}')];
    }

    /** @param array<string,mixed> $fixture */
    private static function insertHistory(mysqli $connection, array $fixture, int $quantity, int $points): int
    {
        $statement = $connection->prepare(
            'INSERT INTO historico_arrecadacoes (id_turma, id_interclasse, quantidade, pontos_adicionados, status_historico) VALUES (?, ?, ?, ?, \'1\')',
        );
        $classId = (int) $fixture['class_id'];
        $editionId = (int) $fixture['edition_id'];
        $quantityToken = number_format((float) $quantity, 2, '.', '');
        $statement->bind_param('iisi', $classId, $editionId, $quantityToken, $points);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        return $id;
    }

    private static function lockEditionAndClass(mysqli $connection, int $editionId, int $classId): void
    {
        $statement = $connection->prepare('SELECT id_interclasse FROM interclasses WHERE id_interclasse = ? FOR UPDATE');
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $statement->close();
        $statement = $connection->prepare('SELECT id_turma FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ? FOR UPDATE');
        $statement->bind_param('ii', $classId, $editionId);
        $statement->execute();
        $statement->close();
    }

    private static function updateClass(mysqli $connection, int $classId, string $quantity, int $points): void
    {
        $statement = $connection->prepare('UPDATE turmas SET qtd_itens_arrecadados = ?, pontuacao_turma = ? WHERE id_turma = ?');
        $statement->bind_param('sii', $quantity, $points, $classId);
        $statement->execute();
        $statement->close();
    }

    /** @return array{quantidade:string,pontos:int} */
    private static function classTotals(mysqli $connection, int $classId): array
    {
        $statement = $connection->prepare('SELECT qtd_itens_arrecadados, pontuacao_turma FROM turmas WHERE id_turma = ?');
        $statement->bind_param('i', $classId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        $connection->close();
        return [
            'quantidade' => number_format((float) ($row['qtd_itens_arrecadados'] ?? 0), 2, '.', ''),
            'pontos' => (int) ($row['pontuacao_turma'] ?? 0),
        ];
    }

    /** @return array<string,mixed> */
    private static function createFixture(mysqli $connection, int $editionId, int $classId): array
    {
        $type = (int) $connection->query("SELECT id_tipo_modalidade FROM tipos_modalidades WHERE status_tipo_modalidade = '1' ORDER BY id_tipo_modalidade LIMIT 1")->fetch_column();
        $categoryStatement = $connection->prepare('SELECT categorias_id_categoria FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ?');
        $categoryStatement->bind_param('ii', $classId, $editionId);
        $categoryStatement->execute();
        $category = (int) $categoryStatement->get_result()->fetch_column();
        $categoryStatement->close();

        $userName = 't18_concorrente_' . bin2hex(random_bytes(4));
        $password = password_hash('t18-secret', PASSWORD_DEFAULT);
        $userStatement = $connection->prepare(
            "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse)
             VALUES ('RM', ?, 'Concorrente T18', ?, '3', 'MASC', '2000-01-01', '', '1', ?, ?)",
        );
        $userStatement->bind_param('ssii', $userName, $password, $classId, $editionId);
        $userStatement->execute();
        $userId = (int) $connection->insert_id;
        $userStatement->close();

        $modalityIds = [];
        $modalityStatement = $connection->prepare(
            "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse)
             VALUES (?, 'MASC', 1, ?, '1', ?, ?, ?)",
        );
        foreach (['T18 Base A', 'T18 Base B', 'T18 Nova A', 'T18 Nova B', 'T18 Equipes', 'T20 Limite1', 'T20 Ilimitada', 'T20 Geração'] as $index => $name) {
            $maxTeams = match ($index) {
                4 => 2,
                6 => 0,
                default => 1,
            };
            $modalityStatement->bind_param('siiii', $name, $maxTeams, $type, $category, $editionId);
            $modalityStatement->execute();
            $modalityIds[] = (int) $connection->insert_id;
        }
        $modalityStatement->close();

        $teamIds = [];
        $teamStatement = $connection->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)");
        foreach ($modalityIds as $index => $modalityId) {
            if ($index === 7) {
                continue;
            }
            $name = 'T18 Equipe ' . ($index + 1);
            $teamStatement->bind_param('iis', $modalityId, $classId, $name);
            $teamStatement->execute();
            $teamIds[$index] = (int) $connection->insert_id;
        }
        $teamStatement->close();
        $linkStatement = $connection->prepare('INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
        foreach ([$teamIds[0], $teamIds[1]] as $teamId) {
            $linkStatement->bind_param('ii', $teamId, $userId);
            $linkStatement->execute();
        }
        $linkStatement->close();

        $classStatement = $connection->prepare('SELECT qtd_itens_arrecadados, pontuacao_turma FROM turmas WHERE id_turma = ?');
        $classStatement->bind_param('i', $classId);
        $classStatement->execute();
        $classRow = $classStatement->get_result()->fetch_assoc() ?: [];
        $classStatement->close();
        $editionStatement = $connection->prepare('SELECT valor_item_arrecadacao FROM interclasses WHERE id_interclasse = ?');
        $editionStatement->bind_param('i', $editionId);
        $editionStatement->execute();
        $editionValue = (int) $editionStatement->get_result()->fetch_column();
        $editionStatement->close();

        $fixture = [
            'edition_id' => $editionId,
            'class_id' => $classId,
            'user_id' => $userId,
            'user_matricula' => $userName,
            'modality_ids' => $modalityIds,
            'team_ids' => $teamIds,
            'signup_team_ids' => [$teamIds[2], $teamIds[3]],
            'team_modality_id' => $modalityIds[4],
            'limit_one_modality_id' => $modalityIds[5],
            'unlimited_modality_id' => $modalityIds[6],
            'generation_modality_id' => $modalityIds[7],
            'original_class' => [
                'quantidade' => (string) ($classRow['qtd_itens_arrecadados'] ?? '0.00'),
                'pontos' => (int) ($classRow['pontuacao_turma'] ?? 0),
            ],
            'original_edition_value' => $editionValue,
        ];
        return $fixture;
    }

    /** @param array<string,mixed> $fixture */
    private static function runAutomaticRedConcurrency(mysqli $connection, array $fixture): void
    {
        $userId = (int) $fixture['user_id'];
        $gameId = (int) $fixture['occurrence_game_id'];
        $classId = (int) $fixture['class_id'];
        $scenario = self::runWorkers(
            $connection,
            'ocorrencia-amarelo',
            [$userId, $gameId, $classId, 'worker-a'],
            static function (mysqli $parent) use ($userId): void {
                $statement = $parent->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = ? FOR UPDATE');
                $statement->bind_param('i', $userId);
                $statement->execute();
                $statement->close();
            },
            [$userId, $gameId, $classId, 'worker-b'],
        );
        $results = array_map(static fn (array $row): string => (string) ($row['result'] ?? ''), $scenario['results']);
        sort($results);
        Assertions::assert(
            'Criação de amarelos concorrente aguarda o bloqueio do atleta',
            $scenario['waited'] && $results === ['created', 'created'],
            $scenario['diagnostic'] . ' ' . json_encode($scenario['results']),
        );

        $statement = $connection->prepare(
            "SELECT COUNT(*) FROM ocorrencias
             WHERE usuarios_id_usuario = ? AND titulo_ocorrencia = 'Amarelo'
               AND status_ocorrencia = '1' AND descricao_ocorrencia LIKE ?",
        );
        $marker = '%[JOGO:' . $gameId . ']%';
        $statement->bind_param('is', $userId, $marker);
        $statement->execute();
        $yellowCount = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $red = $connection->prepare(
            "SELECT COUNT(*) AS total, SUM(o.status_ocorrencia = '1') AS active
             FROM ocorrencias_vermelhos_automaticos a
             INNER JOIN ocorrencias o ON o.id_ocorrencia = a.ocorrencia_vermelha_id
             WHERE a.usuarios_id_usuario = ? AND a.jogos_id_jogo = ?",
        );
        $red->bind_param('ii', $userId, $gameId);
        $red->execute();
        $redRow = $red->get_result()->fetch_assoc() ?: [];
        $red->close();
        Assertions::assert(
            'Duas criações simultâneas produzem dois amarelos e um único vermelho ativo',
            $yellowCount === 2 && (int) ($redRow['total'] ?? 0) === 1 && (int) ($redRow['active'] ?? 0) === 1,
            json_encode(['yellows' => $yellowCount, 'automatic_red' => $redRow]),
        );
    }

    /** @param array<string,mixed> $fixture */
    private static function createOccurrenceGame(mysqli $connection, array $fixture): int
    {
        $editionId = (int) $fixture['edition_id'];
        $modalityId = (int) $fixture['team_modality_id'];
        $local = $connection->prepare(
            "SELECT id_local FROM locais WHERE interclasses_id_interclasse = ? AND status_local = '1'
             ORDER BY id_local LIMIT 1",
        );
        $local->bind_param('i', $editionId);
        $local->execute();
        $localId = (int) $local->get_result()->fetch_column();
        $local->close();
        if ($localId <= 0) {
            throw new RuntimeException('Fixture concorrente não encontrou local ativo.');
        }
        $name = 'L10 concorrência ' . bin2hex(random_bytes(4));
        $date = date('Y-m-d');
        $start = '00:00:00';
        $end = '00:01:00';
        $status = 'Agendado';
        $statement = $connection->prepare(
            'INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo,
                modalidades_id_modalidade, locais_id_local) VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->bind_param('sssssii', $name, $date, $start, $end, $status, $modalityId, $localId);
        $statement->execute();
        $gameId = (int) $statement->insert_id;
        $statement->close();
        $teamId = (int) $fixture['team_ids'][4];
        $partida = $connection->prepare(
            "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida)
             VALUES (?, ?, 0, '1')",
        );
        $partida->bind_param('ii', $gameId, $teamId);
        $partida->execute();
        $partida->close();
        return $gameId;
    }

    /** @param array<string,mixed> $fixture */
    private static function restoreFixture(mysqli $connection, array $fixture): void
    {
        $classId = (int) $fixture['class_id'];
        $editionId = (int) $fixture['edition_id'];
        $modalityIds = array_map('intval', $fixture['modality_ids']);
        $historyIds = array_map('intval', $fixture['history_ids'] ?? []);
        $userId = (int) $fixture['user_id'];
        $connection->query('DELETE FROM ocorrencias_vermelhos_automaticos WHERE usuarios_id_usuario = ' . $userId);
        $deleteOccurrences = $connection->prepare('DELETE FROM ocorrencias WHERE usuarios_id_usuario = ?');
        $deleteOccurrences->bind_param('i', $userId);
        $deleteOccurrences->execute();
        $deleteOccurrences->close();
        $gameId = (int) ($fixture['occurrence_game_id'] ?? 0);
        if ($gameId > 0) {
            $connection->query('DELETE FROM partidas WHERE jogos_id_jogo = ' . $gameId);
            $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $gameId);
        }
        if ($historyIds !== []) {
            $connection->query('DELETE FROM historico_arrecadacoes WHERE id_historico IN (' . implode(',', $historyIds) . ')');
        }
        $connection->query(
            'DELETE eu FROM equipes_has_usuarios eu INNER JOIN equipes e ON e.id_equipe = eu.equipes_id_equipe WHERE e.modalidades_id_modalidade IN (' . implode(',', $modalityIds) . ') OR eu.usuarios_id_usuario = ' . (int) $fixture['user_id'],
        );
        $connection->query('DELETE FROM equipes WHERE modalidades_id_modalidade IN (' . implode(',', $modalityIds) . ')');
        $connection->query('DELETE FROM modalidades WHERE id_modalidade IN (' . implode(',', $modalityIds) . ')');
        $deleteUser = $connection->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
        $deleteUser->bind_param('i', $userId);
        $deleteUser->execute();
        $deleteUser->close();
        $edition = $connection->prepare('UPDATE interclasses SET valor_item_arrecadacao = ? WHERE id_interclasse = ?');
        $value = (int) $fixture['original_edition_value'];
        $edition->bind_param('ii', $value, $editionId);
        $edition->execute();
        $edition->close();
        $original = $fixture['original_class'];
        self::updateClass($connection, $classId, (string) $original['quantidade'], (int) $original['pontos']);
    }
}
