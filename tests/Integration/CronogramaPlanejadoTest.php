<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Competicoes\Application\CronogramaService;
use App\Modules\Competicoes\Infrastructure\MysqliCronogramaRepository;
use App\Modules\Competicoes\Infrastructure\MysqliEquipeRepository;
use App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository;
use App\Shared\Database\MigrationRunner;
use App\Shared\Database\Transaction;
use App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway;
use SGITests\Support\TestClient;
use mysqli;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class CronogramaPlanejadoTest
{
    public static function run(): void
    {
        echo "\n  [Cronograma planejado antes das inscrições]\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $root = dirname(__DIR__, 2);
        $version = '001_cronograma_inscricoes.sql';
        $runner = new MigrationRunner($connection, $root . '/database/migrations');
        $runner->migrate();
        $editionId = 0;
        $originalStatuses = [];
        $originalClassStatuses = [];
        $reservationId = 0;
        $createdModalityId = 0;
        $createdStudentId = 0;
        $createdStudentIds = [];
        $releaseBlockerGameId = 0;
        try {
            $editionId = (int) $connection->query("SELECT id_interclasse FROM interclasses ORDER BY id_interclasse LIMIT 1")->fetch_column();
            $modalityId = (int) $connection->query("SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse = {$editionId} AND status_modalidade = '1' ORDER BY id_modalidade LIMIT 1")->fetch_column();
            $localId = (int) $connection->query("SELECT id_local FROM locais WHERE interclasses_id_interclasse = {$editionId} AND status_local = '1' AND disponivel_local = '1' ORDER BY id_local LIMIT 1")->fetch_column();
            $categoryId = (int) $connection->query("SELECT categorias_id_categoria FROM modalidades WHERE id_modalidade = {$modalityId}")->fetch_column();
            $classRows = $connection->query("SELECT id_turma, status_turma FROM turmas WHERE interclasses_id_interclasse = {$editionId} AND categorias_id_categoria = {$categoryId} ORDER BY id_turma")->fetch_all(MYSQLI_ASSOC);
            $firstClassId = (int) ($classRows[0]['id_turma'] ?? 0);
            foreach ($classRows as $classRow) {
                $originalClassStatuses[(int) $classRow['id_turma']] = (string) $classRow['status_turma'];
            }
            $statusRows = $connection->query("SELECT id_modalidade, status_modalidade FROM modalidades WHERE interclasses_id_interclasse = {$editionId}")->fetch_all(MYSQLI_ASSOC);
            foreach ($statusRows as $statusRow) {
                $originalStatuses[(int) $statusRow['id_modalidade']] = (string) $statusRow['status_modalidade'];
            }
            $connection->query("UPDATE modalidades SET status_modalidade = '0' WHERE interclasses_id_interclasse = {$editionId} AND id_modalidade <> {$modalityId}");
            $connection->query("UPDATE turmas SET status_turma = '0' WHERE interclasses_id_interclasse = {$editionId} AND categorias_id_categoria = {$categoryId} AND id_turma <> {$firstClassId}");
            $primaryGender = $connection->real_escape_string((string) $connection->query("SELECT genero_modalidade FROM modalidades WHERE id_modalidade = {$modalityId}")->fetch_column());
            $registration = 'CRONOGRAMA-' . bin2hex(random_bytes(4));
            $studentName = 'Aluno Cronograma';
            $studentPassword = password_hash('cronograma-fixture', PASSWORD_DEFAULT);
            $student = $connection->prepare("INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao) VALUES ('RM', ?, ?, ?, '3', ?, '2010-01-01', '', '1', ?, ?, NULL)");
            $student->bind_param('ssssii', $registration, $studentName, $studentPassword, $primaryGender, $firstClassId, $editionId);
            $student->execute();
            $createdStudentId = (int) $connection->insert_id;
            $createdStudentIds[] = $createdStudentId;
            $student->close();
            $modalityTypeId = (int) $connection->query("SELECT tipos_modalidades_id_tipo_modalidade FROM modalidades WHERE id_modalidade = {$modalityId}")->fetch_column();
            $admin = new TestClient();
            $admin->login('admin', '123');
            $createdModality = $admin->postJson('api/v1/modalidades', [
                'nome_modalidade' => 'Modalidade cronograma ' . bin2hex(random_bytes(3)),
                'genero_modalidade' => $primaryGender,
                'max_inscrito_modalidade' => 10,
                'max_equipes' => 4,
                'tipos_modalidades_id_tipo_modalidade' => $modalityTypeId,
                'categorias_id_categoria' => $categoryId,
                'interclasses_id_interclasse' => $editionId,
            ]);
            $createdModalityId = (int) ($createdModality['json']['id_modalidade'] ?? 0);
            if ($createdModalityId <= 0 || ($createdModality['code'] ?? 0) < 200 || ($createdModality['code'] ?? 0) >= 300) {
                throw new \RuntimeException('Não foi possível criar a segunda modalidade da fixture de cronograma.');
            }
            Assertions::assert('Fixture cria outra modalidade coletiva de mesma categoria e gênero', $createdModalityId > 0);
            $secondModalityId = $createdModalityId;
            $connection->query("UPDATE modalidades SET status_modalidade = '1' WHERE id_modalidade = {$secondModalityId}");
            $connection->query("INSERT INTO interclasse_planejamentos (id_interclasse, cronograma_status, inscricoes_status) VALUES ({$editionId}, 'rascunho', 'fechadas') ON DUPLICATE KEY UPDATE cronograma_status = 'rascunho', inscricoes_status = 'fechadas', cronograma_versao = 0");
            $connection->query("INSERT INTO modalidade_planejamentos (id_modalidade, equipes_planejadas, min_inscritos_equipe, max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min) VALUES ({$modalityId}, 3, 1, 1, 'equipe', 20, 10) ON DUPLICATE KEY UPDATE equipes_planejadas = 3, min_inscritos_equipe = 1, max_inscritos_equipe = 1, formato_participacao = 'equipe'");
            $connection->query("INSERT INTO modalidade_planejamentos (id_modalidade, equipes_planejadas, min_inscritos_equipe, max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min) VALUES ({$secondModalityId}, 4, 1, 1, 'equipe', 20, 10) ON DUPLICATE KEY UPDATE equipes_planejadas = 4, min_inscritos_equipe = 1, max_inscritos_equipe = 1, formato_participacao = 'equipe'");
            $connection->query("INSERT INTO agenda_reservas (id_interclasse, id_modalidade, chave_versao, chave_tag, data_reserva, inicio_reserva, termino_reserva, id_local) VALUES ({$editionId}, {$modalityId}, 'manual-test', 'BLOQUEIO-CRONOGRAMA', '2030-10-01', '08:00:00', '08:20:00', {$localId})");
            $reservationId = (int) $connection->insert_id;
            $legacyTeamId = (int) $connection->query("SELECT e.id_equipe FROM equipes e LEFT JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = {$modalityId} AND e.turmas_id_turma = {$firstClassId} AND e.status_equipe = '1' AND ep.id_equipe IS NULL ORDER BY e.id_equipe LIMIT 1")->fetch_column();
            if ($legacyTeamId <= 0) {
                $legacyName = $connection->real_escape_string('Equipe padrão sem planejamento');
                $connection->query("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', {$modalityId}, {$firstClassId}, '{$legacyName}')");
                $legacyTeamId = (int) $connection->insert_id;
            }
            $service = new CronogramaService(new MysqliCronogramaRepository($connection));
            $service->preparar($editionId, 1);
            $legacyPrepared = (int) $connection->query("SELECT planejada FROM equipe_planejamentos WHERE id_equipe = {$legacyTeamId}")->fetch_column();
            Assertions::assert('Preparação incorpora equipes padrão sem metadados', $legacyPrepared === 1);
            $preparedTeamForRegression = (int) $connection->query("SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = {$modalityId} AND e.turmas_id_turma = {$firstClassId} AND e.status_equipe = '1' ORDER BY ep.ordem_planejada, e.id_equipe LIMIT 1")->fetch_column();
            if ($preparedTeamForRegression <= 0) {
                throw new \RuntimeException('A fixture não criou equipe planejada para a regressão de reativação.');
            }
            $connection->query("UPDATE equipe_planejamentos SET planejada = 0 WHERE id_equipe = {$preparedTeamForRegression}");
            $service->preparar($editionId, 1);
            $reactivated = (int) $connection->query("SELECT planejada FROM equipe_planejamentos WHERE id_equipe = {$preparedTeamForRegression}")->fetch_column();
            Assertions::assert('Preparação reativa equipes padrão existentes', $reactivated === 1);
            $connection->query("DELETE ehu FROM equipes_has_usuarios ehu INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade IN ({$modalityId}, {$secondModalityId})");
            $membershipsBeforePlanning = (int) $connection->query("SELECT COUNT(*) FROM equipes_has_usuarios ehu INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe WHERE e.modalidades_id_modalidade IN ({$modalityId}, {$secondModalityId})")->fetch_column();
            Assertions::assert('Planejamento começa sem alunos vinculados às equipes', $membershipsBeforePlanning === 0);
            $draft = $service->gerar($editionId, 1, [
                'data_inicio' => '2030-10-01',
                'data_fim' => '2030-10-01',
                'hora_inicio' => '08:00',
                'hora_fim' => '18:00',
                'duracao_min' => 20,
                'id_locais' => [$localId],
            ]);
            $firstNodes = array_values(array_filter($draft['nos'] ?? [], static fn (array $node): bool => (int) ($node['id_modalidade'] ?? 0) === $modalityId));
            $normalNodes = array_values(array_filter($firstNodes, static fn (array $node): bool => ($node['tipo_no'] ?? '') === 'normal'));
            $byeNodes = array_values(array_filter($firstNodes, static fn (array $node): bool => ($node['tipo_no'] ?? '') === 'bye'));
            $firstCommitments = array_values(array_filter($draft['compromissos'] ?? [], static fn (array $item): bool => (int) ($item['id_modalidade'] ?? 0) === $modalityId));
            Assertions::assert('Geração planejada usa a árvore exata de três equipes', $draft['success'] === true && count($normalNodes) === 2 && count($byeNodes) === 1 && count($firstCommitments) === 2 && ($firstCommitments[0]['condicional'] ?? 1) === 0);
            Assertions::assert('Geração respeita reserva existente com margem de troca', ($firstCommitments[0]['inicio_compromisso'] ?? '') !== '08:00:00');
            Assertions::assert('A geração persiste a árvore planejada sem jogos reais', count($draft['nos'] ?? []) >= count($draft['compromissos'] ?? []) && count($draft['nos'] ?? []) > 0);
            $incompleteRejected = false;
            try {
                $service->publicar($editionId, 1, ['cronograma_versao' => 0, 'compromissos' => [], 'nos' => $draft['nos']]);
            } catch (\InvalidArgumentException $exception) {
                $incompleteRejected = str_contains($exception->getMessage(), 'compromisso') || str_contains($exception->getMessage(), 'cronograma');
            }
            $publishedRowsAfterRejectedAttempt = (int) $connection->query("SELECT COUNT(*) FROM cronograma_nos WHERE id_interclasse = {$editionId}")->fetch_column();
            Assertions::assert('Publicação incompleta recusa a proposta e não grava árvore parcial', $incompleteRejected && $publishedRowsAfterRejectedAttempt === 0);
            $published = $service->publicar($editionId, 1, ['cronograma_versao' => 0, 'compromissos' => $draft['compromissos'], 'nos' => $draft['nos']]);
            $opened = $service->abrir($editionId, 1, ['cronograma_versao' => $published['cronograma_versao'], 'inscricoes_abertura' => '2020-01-01 00:00:00', 'inscricoes_encerramento' => '2031-01-01 00:00:00']);
            Assertions::assert('Publicação ocorre antes da abertura das inscrições', $published['cronograma_status'] === 'publicado' && $opened['inscricoes_status'] === 'abertas');
            Assertions::assert('Estado publicado expõe a mesma revisão e compromisso', $service->estado($editionId)['cronograma_versao'] === $published['cronograma_versao']);
            $service->fechar($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            $nodeId = (int) $connection->query("SELECT id_no FROM cronograma_nos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$published['cronograma_versao']} AND tipo_no = 'normal' ORDER BY id_no LIMIT 1")->fetch_column();
            $releaseRejected = false;
            try {
                $service->liberar($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            } catch (\InvalidArgumentException $exception) {
                $releaseRejected = str_contains($exception->getMessage(), 'mínimos de elenco');
            }
            Assertions::assert('Liberação exige mínimos de elenco antes da operação', $releaseRejected);
            $materializationRejected = false;
            try {
                $service->materializar($editionId, 1, ['id_no' => $nodeId]);
            } catch (\InvalidArgumentException $exception) {
                $materializationRejected = str_contains($exception->getMessage(), 'operação liberada');
            }
            Assertions::assert('Materialização bloqueia operação não liberada', $materializationRejected);
            $teamId = (int) $connection->query("SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = {$modalityId} AND e.turmas_id_turma = {$firstClassId} AND e.status_equipe = '1' ORDER BY ep.ordem_planejada LIMIT 1")->fetch_column();
            $cronogramaRepository = new MysqliCronogramaRepository($connection);
            $parallelTeams = $connection->query("SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = {$modalityId} AND status_equipe = '1' ORDER BY id_equipe LIMIT 2")->fetch_all(MYSQLI_ASSOC);
            $parallelName = 'FORA-DO-PLANO-' . bin2hex(random_bytes(4));
            $parallelResponse = $admin->postJson('api/v1/sincronizacao/chaveamento', [
                'id_modalidade' => $modalityId,
                'tipo_modalidade' => 'mata_mata',
                'jogos' => [[
                    'nome_jogo' => $parallelName,
                    'status_jogo' => 'Agendado',
                    'partidas' => [
                        ['id_equipe' => (int) ($parallelTeams[0]['id_equipe'] ?? 0), 'resultado' => 0],
                        ['id_equipe' => (int) ($parallelTeams[1]['id_equipe'] ?? 0), 'resultado' => 0],
                    ],
                ]],
            ]);
            $parallelGameCount = (int) $connection->query("SELECT COUNT(*) FROM jogos WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = '{$connection->real_escape_string($parallelName)}'")->fetch_column();
            Assertions::assert(
                'Sincronização legada não cria outro jogo depois da publicação do calendário',
                ($parallelResponse['code'] ?? 0) === 400
                && ($parallelResponse['json']['success'] ?? true) === false
                && $parallelGameCount === 0,
                json_encode(['response' => $parallelResponse, 'games' => $parallelGameCount], JSON_UNESCAPED_UNICODE),
            );
            $projected = $cronogramaRepository->commitmentsForTeams($editionId, [$teamId]);
            Assertions::assert('Projeção da inscrição inclui as fases possíveis da equipe', count($projected) === 2 && count(array_filter($projected, static fn (array $item): bool => (int) ($item['condicional'] ?? 0) === 1)) === 1);
            $studentAgenda = $cronogramaRepository->studentAgenda($editionId, $createdStudentId, [$teamId]);
            Assertions::assert('Aluno consulta a agenda publicada antes de possuir elenco', ($studentAgenda['publicado'] ?? false) === true && count($studentAgenda['compromissos'] ?? []) === 2 && count($studentAgenda['equipes'] ?? []) === 1);
            $service->abrir($editionId, 1, ['cronograma_versao' => $published['cronograma_versao'], 'inscricoes_abertura' => '2020-01-01 00:00:00', 'inscricoes_encerramento' => '2031-01-01 00:00:00']);
            $secondTeamId = (int) $connection->query("SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = {$secondModalityId} AND e.turmas_id_turma = {$firstClassId} AND e.status_equipe = '1' ORDER BY ep.ordem_planejada LIMIT 1")->fetch_column();
            $studentId = $createdStudentId;
            if ($secondTeamId <= 0 || $studentId <= 0) {
                throw new \RuntimeException('A fixture não possui equipe e estudante compatíveis para o conflito planejado.');
            }
            $firstSlot = $firstCommitments[0] ?? [];
            $overlapDate = $connection->real_escape_string((string) ($firstSlot['data_compromisso'] ?? '2030-10-01'));
            $overlapStart = $connection->real_escape_string((string) ($firstSlot['inicio_compromisso'] ?? '08:30:00'));
            $overlapEnd = $connection->real_escape_string((string) ($firstSlot['termino_compromisso'] ?? '08:50:00'));
            $overlapLocal = (int) ($firstSlot['id_local'] ?? $localId);
            $connection->query("UPDATE cronograma_compromissos SET data_compromisso = '{$overlapDate}', inicio_compromisso = '{$overlapStart}', termino_compromisso = '{$overlapEnd}', id_local = {$overlapLocal} WHERE id_interclasse = {$editionId} AND id_modalidade = {$secondModalityId} AND cronograma_versao = {$published['cronograma_versao']}");
            $equipes = new MysqliEquipeRepository($connection, new MysqliCronogramaRepository($connection));
            $equipes->addUsers($teamId, [$studentId]);
            $conflictRejected = false;
            try {
                $equipes->addUsers($secondTeamId, [$studentId]);
            } catch (\InvalidArgumentException $exception) {
                $conflictRejected = str_contains($exception->getMessage(), 'Conflito de agenda');
            }
            Assertions::assert('Inscrição planejada recusa conflito entre modalidades da mesma turma', $conflictRejected);

            $createFixtureStudent = static function () use ($connection, $firstClassId, $editionId, $primaryGender, &$createdStudentIds): int {
                $registration = 'CRONOGRAMA-' . bin2hex(random_bytes(4));
                $name = 'Aluno Cronograma ' . count($createdStudentIds);
                $password = password_hash('cronograma-fixture', PASSWORD_DEFAULT);
                $statement = $connection->prepare("INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao) VALUES ('RM', ?, ?, ?, '3', ?, '2010-01-01', '', '1', ?, ?, NULL)");
                $statement->bind_param('ssssii', $registration, $name, $password, $primaryGender, $firstClassId, $editionId);
                $statement->execute();
                $studentId = (int) $connection->insert_id;
                $statement->close();
                $createdStudentIds[] = $studentId;
                return $studentId;
            };
            $allPlannedTeams = $connection->query("SELECT e.id_equipe, e.modalidades_id_modalidade FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade IN ({$modalityId}, {$secondModalityId}) AND e.status_equipe = '1' ORDER BY e.modalidades_id_modalidade, ep.ordem_planejada, e.id_equipe")->fetch_all(MYSQLI_ASSOC);
            foreach ($allPlannedTeams as $plannedTeam) {
                $plannedTeamId = (int) $plannedTeam['id_equipe'];
                if ($plannedTeamId === $teamId) {
                    continue;
                }
                $equipes->addUsers($plannedTeamId, [$createFixtureStudent()]);
            }
            $reviewed = $service->revisar($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            $oldSnapshot = (int) $connection->query("SELECT COUNT(*) FROM cronograma_compromissos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$published['cronograma_versao']}")->fetch_column();
            Assertions::assert('Revisão fecha o cronograma', $reviewed['cronograma_status'] === 'revisao');
            Assertions::assert('Revisão fecha as inscrições', $reviewed['inscricoes_status'] === 'fechadas');
            Assertions::assert('Revisão avança a versão', (int) $reviewed['cronograma_versao'] === (int) $published['cronograma_versao'] + 1);
            Assertions::assert('Revisão preserva todos os compromissos da versão suspensa', $oldSnapshot === (int) ($published['compromissos'] ?? -1));

            $finalDraft = $service->gerar($editionId, 1, [
                'data_inicio' => '2030-10-01',
                'data_fim' => '2030-10-01',
                'hora_inicio' => '08:00',
                'hora_fim' => '18:00',
                'duracao_min' => 20,
                'id_locais' => [$localId],
            ]);
            $finalPublished = $service->publicar($editionId, 1, [
                'cronograma_versao' => $reviewed['cronograma_versao'],
                'compromissos' => $finalDraft['compromissos'],
                'nos' => $finalDraft['nos'],
            ]);
            $preview = MysqliChaveamentoRepository::montarJsonArvore($connection, $modalityId);
            $plannedPreview = array_values(array_filter($preview['jogos'] ?? [], static fn (array $game): bool => (bool) ($game['virtual_planejado'] ?? false)));
            Assertions::assert('Árvore publicada aparece como prevista antes da liberação', count($plannedPreview) === 3 && count(array_filter($plannedPreview, static fn (array $game): bool => ($game['status_jogo'] ?? '') === 'Previsto')) >= 2);
            $fourTeamPreview = MysqliChaveamentoRepository::montarJsonArvore($connection, $secondModalityId);
            $fourTeamNodes = array_values(array_filter($fourTeamPreview['jogos'] ?? [], static fn (array $game): bool => ($game['virtual_planejado'] ?? false)));
            $fourTeamOpeningGames = array_values(array_filter($fourTeamNodes, static fn (array $game): bool => (int) ($game['fase_nivel'] ?? 0) === 4));
            Assertions::assert('Modalidade com quatro equipes prevê dois jogos e a final', count($fourTeamNodes) === 3 && count($fourTeamOpeningGames) === 2, 'nós=' . count($fourTeamNodes) . ', abertura=' . count($fourTeamOpeningGames) . ', árvore=' . json_encode($fourTeamPreview['jogos'] ?? []));
            $service->fechar($editionId, 1, ['cronograma_versao' => $finalPublished['cronograma_versao']]);
            $releaseNodes = $connection->query("SELECT cn.id_no, cn.id_modalidade, cn.chave_tag, cc.data_compromisso, cc.inicio_compromisso, cc.termino_compromisso, cc.id_local FROM cronograma_nos cn INNER JOIN cronograma_compromissos cc ON cc.id_interclasse = cn.id_interclasse AND cc.id_modalidade = cn.id_modalidade AND cc.cronograma_versao = cn.cronograma_versao AND cc.chave_tag = cn.chave_tag WHERE cn.id_interclasse = {$editionId} AND cn.cronograma_versao = {$finalPublished['cronograma_versao']} AND cn.origem_a_tag IS NULL AND cn.origem_b_tag IS NULL AND cn.tipo_no <> 'bye' ORDER BY cn.fase_largura DESC, cn.slot, cn.id_no")->fetch_all(MYSQLI_ASSOC);
            if (count($releaseNodes) < 2) {
                throw new \RuntimeException('A fixture precisa de dois confrontos iniciais para testar rollback da liberação.');
            }
            $blockerTarget = $releaseNodes[1];
            $blockerName = 'BLOQUEIO-LIBERACAO-' . bin2hex(random_bytes(4));
            $blocker = $connection->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, ?, ?, ?, 'Agendado', ?, ?)");
            $blockerDate = (string) $blockerTarget['data_compromisso'];
            $blockerStart = (string) $blockerTarget['inicio_compromisso'];
            $blockerEnd = (string) $blockerTarget['termino_compromisso'];
            $blockerModalityId = (int) $blockerTarget['id_modalidade'];
            $blockerLocalId = (int) $blockerTarget['id_local'];
            $blocker->bind_param('ssssii', $blockerName, $blockerDate, $blockerStart, $blockerEnd, $blockerModalityId, $blockerLocalId);
            $blocker->execute();
            $releaseBlockerGameId = (int) $connection->insert_id;
            $blocker->close();
            $releaseRollback = false;
            try {
                $service->liberar($editionId, 1, ['cronograma_versao' => $finalPublished['cronograma_versao']]);
            } catch (\InvalidArgumentException $exception) {
                $releaseRollback = str_contains($exception->getMessage(), 'conflito') || str_contains($exception->getMessage(), 'agendado');
            }
            $rolledBackGameLinks = (int) $connection->query("SELECT COUNT(*) FROM cronograma_nos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$finalPublished['cronograma_versao']} AND id_jogo IS NOT NULL")->fetch_column();
            $rolledBackOperationFlag = (int) $connection->query("SELECT operacao_liberada FROM interclasse_planejamentos WHERE id_interclasse = {$editionId}")->fetch_column();
            Assertions::assert('Falha em confronto posterior reverte jogos, vínculos e flag da liberação', $releaseRollback && $rolledBackGameLinks === 0 && $rolledBackOperationFlag === 0, 'erro=' . (int) $releaseRollback . ', vínculos=' . $rolledBackGameLinks . ', flag=' . $rolledBackOperationFlag);
            $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $releaseBlockerGameId);
            $releaseBlockerGameId = 0;
            $released = $service->liberar($editionId, 1, ['cronograma_versao' => $finalPublished['cronograma_versao']]);
            Assertions::assert('Liberação materializa as partidas iniciais publicadas', $released['liberada'] === true && (int) $released['jogos_criados'] === 3, 'jogos_criados=' . (int) ($released['jogos_criados'] ?? -1));
            $initialGameCount = (int) $connection->query("SELECT COUNT(*) FROM cronograma_nos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$finalPublished['cronograma_versao']} AND id_jogo IS NOT NULL")->fetch_column();
            Assertions::assert('Liberação associa cada confronto físico ao nó publicado', $initialGameCount === 3, 'vínculos=' . $initialGameCount);
            $treeAfterRelease = MysqliChaveamentoRepository::montarJsonArvore($connection, $modalityId);
            Assertions::assert('A árvore mantém fases futuras sem criar jogos fictícios', count($treeAfterRelease['jogos'] ?? []) === 3 && count(array_filter($treeAfterRelease['jogos'] ?? [], static fn (array $game): bool => (bool) ($game['virtual_planejado'] ?? false))) === 2);
            $retry = $service->liberar($editionId, 1, ['cronograma_versao' => $finalPublished['cronograma_versao']]);
            $retryGameCount = (int) $connection->query("SELECT COUNT(*) FROM cronograma_nos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$finalPublished['cronograma_versao']} AND id_jogo IS NOT NULL")->fetch_column();
            Assertions::assert('Repetição da liberação não duplica partidas', ($retry['idempotente'] ?? false) === true && $retryGameCount === $initialGameCount);
            $reviewAfterReleaseRejected = false;
            try {
                $service->revisar($editionId, 1, ['cronograma_versao' => $finalPublished['cronograma_versao']]);
            } catch (\InvalidArgumentException $exception) {
                $reviewAfterReleaseRejected = str_contains($exception->getMessage(), 'operação já começou');
            }
            Assertions::assert('Revisão não substitui a árvore depois de liberar a operação', $reviewAfterReleaseRejected);

            $initialGame = $connection->query("SELECT cn.id_jogo FROM cronograma_nos cn WHERE cn.id_interclasse = {$editionId} AND cn.id_modalidade = {$modalityId} AND cn.cronograma_versao = {$finalPublished['cronograma_versao']} AND cn.tipo_no = 'normal' AND cn.origem_a_tag IS NULL AND cn.origem_b_tag IS NULL AND cn.id_jogo IS NOT NULL ORDER BY cn.slot LIMIT 1")->fetch_column();
            $initialGameId = (int) $initialGame;
            $scores = $connection->query("SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = {$initialGameId} ORDER BY id_partida")->fetch_all(MYSQLI_ASSOC);
            if ($initialGameId <= 0 || count($scores) !== 2) {
                throw new \RuntimeException('A liberação não preparou uma partida inicial com duas equipes.');
            }
            $gateway = new MysqliPartidaGateway($connection);
            Transaction::begin($connection);
            $gateway->persistirPlacar($initialGameId, [
                ['id_equipe' => (int) $scores[0]['equipes_id_equipe'], 'gols' => 1],
                ['id_equipe' => (int) $scores[1]['equipes_id_equipe'], 'gols' => 0],
            ]);
            $gateway->concluirJogo($initialGameId);
            $gateway->avancarChaveamento($initialGameId);
            Transaction::commit($connection);
            $finalNode = $connection->query("SELECT cn.id_jogo FROM cronograma_nos cn WHERE cn.id_interclasse = {$editionId} AND cn.id_modalidade = {$modalityId} AND cn.cronograma_versao = {$finalPublished['cronograma_versao']} AND cn.fase_largura = 2 ORDER BY cn.slot LIMIT 1")->fetch_column();
            $finalTeams = $connection->query('SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ' . (int) $finalNode . ' ORDER BY id_partida')->fetch_all(MYSQLI_ASSOC);
            $winner = (int) $scores[0]['equipes_id_equipe'];
            $byeWinner = (int) $connection->query("SELECT cne.id_equipe FROM cronograma_nos cn INNER JOIN cronograma_no_equipes cne ON cne.id_no = cn.id_no WHERE cn.id_interclasse = {$editionId} AND cn.id_modalidade = {$modalityId} AND cn.cronograma_versao = {$finalPublished['cronograma_versao']} AND cn.tipo_no = 'bye' LIMIT 1")->fetch_column();
            $actualFinalTeams = array_map(static fn (array $team): int => (int) $team['equipes_id_equipe'], $finalTeams);
            sort($actualFinalTeams);
            $expectedFinalTeams = [$winner, $byeWinner];
            sort($expectedFinalTeams);
            Assertions::assert('Resultado avança vencedor e BYE para a final publicada', (int) $finalNode > 0 && $actualFinalTeams === $expectedFinalTeams);
            $finalTag = (string) $connection->query("SELECT chave_tag FROM cronograma_nos WHERE id_interclasse = {$editionId} AND id_modalidade = {$modalityId} AND cronograma_versao = {$finalPublished['cronograma_versao']} AND fase_largura = 2 ORDER BY slot LIMIT 1")->fetch_column();
            $finalCommitmentStatement = $connection->prepare('SELECT data_compromisso, inicio_compromisso, id_local FROM cronograma_compromissos WHERE id_interclasse = ? AND id_modalidade = ? AND cronograma_versao = ? AND chave_tag = ? LIMIT 1');
            $finalVersion = (int) $finalPublished['cronograma_versao'];
            $finalCommitmentStatement->bind_param('iiis', $editionId, $modalityId, $finalVersion, $finalTag);
            $finalCommitmentStatement->execute();
            $finalSlot = $finalCommitmentStatement->get_result()->fetch_assoc();
            $finalCommitmentStatement->close();
            $finalGame = $connection->query('SELECT data_jogo, inicio_jogo, locais_id_local FROM jogos WHERE id_jogo = ' . (int) $finalNode)->fetch_assoc();
            Assertions::assert('Final usa data, início e local do compromisso publicado', $finalGame !== null && $finalSlot !== null && $finalGame['data_jogo'] === $finalSlot['data_compromisso'] && $finalGame['inicio_jogo'] === $finalSlot['inicio_compromisso'] && (int) $finalGame['locais_id_local'] === (int) $finalSlot['id_local']);
        } finally {
            if ($releaseBlockerGameId > 0) {
                $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $releaseBlockerGameId);
            }
            $linkedGames = $connection->query('SELECT id_jogo FROM cronograma_nos WHERE id_jogo IS NOT NULL')->fetch_all(MYSQLI_NUM);
            foreach ($linkedGames as $linkedGame) {
                $linkedGameId = (int) $linkedGame[0];
                $connection->query('DELETE FROM partidas WHERE jogos_id_jogo = ' . $linkedGameId);
                $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $linkedGameId);
            }
            $createdTeams = $connection->query('SELECT id_equipe FROM equipe_planejamentos')->fetch_all(MYSQLI_NUM);
            foreach ($createdTeams as $createdTeam) {
                $teamId = (int) $createdTeam[0];
                $connection->query('DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ' . $teamId);
                $connection->query('DELETE FROM equipes WHERE id_equipe = ' . $teamId);
            }
            foreach ($originalStatuses as $statusModalityId => $status) {
                $statement = $connection->prepare('UPDATE modalidades SET status_modalidade = ? WHERE id_modalidade = ?');
                $statement->bind_param('si', $status, $statusModalityId);
                $statement->execute();
                $statement->close();
            }
            foreach ($originalClassStatuses as $statusClassId => $status) {
                $statement = $connection->prepare('UPDATE turmas SET status_turma = ? WHERE id_turma = ?');
                $statement->bind_param('si', $status, $statusClassId);
                $statement->execute();
                $statement->close();
            }
            $connection->query('DROP TABLE IF EXISTS cronograma_no_equipes');
            $connection->query('DROP TABLE IF EXISTS cronograma_nos');
            $connection->query('DROP TABLE IF EXISTS cronograma_compromissos');
            $connection->query('DROP TABLE IF EXISTS equipe_planejamentos');
            $connection->query('DROP TABLE IF EXISTS modalidade_planejamentos');
            $connection->query('DROP TABLE IF EXISTS interclasse_planejamentos');
            if ($createdModalityId > 0) {
                $connection->query('DELETE FROM modalidades WHERE id_modalidade = ' . $createdModalityId);
            }
            if ($reservationId > 0) {
                $connection->query('DELETE FROM agenda_reservas WHERE id_reserva = ' . $reservationId);
            }
            foreach ($createdStudentIds as $createdUserId) {
                $connection->query('DELETE FROM equipes_has_usuarios WHERE usuarios_id_usuario = ' . (int) $createdUserId);
                $connection->query('DELETE FROM usuarios WHERE id_usuario = ' . (int) $createdUserId);
            }
            $gameLinkVersion = '004_cronograma_no_jogo.sql';
            $statement = $connection->prepare('DELETE FROM sgi_migrations WHERE version IN (?, ?, ?, ?)');
            $nodesVersion = '002_cronograma_nos.sql';
            $contractVersion = '003_cronograma_final_contract.sql';
            $statement->bind_param('ssss', $version, $nodesVersion, $contractVersion, $gameLinkVersion);
            $statement->execute();
            $statement->close();
            $connection->close();
        }
    }
}
