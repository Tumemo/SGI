<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Competicoes\Application\CronogramaService;
use App\Modules\Competicoes\Infrastructure\MysqliCronogramaRepository;
use App\Modules\Competicoes\Infrastructure\MysqliEquipeRepository;
use App\Shared\Database\MigrationRunner;
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
        $createdStudentId = 0;
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
            $student->close();
            $secondModalityId = (int) $connection->query("SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse = {$editionId} AND categorias_id_categoria = {$categoryId} AND genero_modalidade = '{$primaryGender}' AND id_modalidade <> {$modalityId} ORDER BY id_modalidade LIMIT 1")->fetch_column();
            if ($secondModalityId <= 0) {
                throw new \RuntimeException('A fixture precisa de duas modalidades do mesmo gênero e categoria para a regressão de conflito planejado.');
            }
            $connection->query("UPDATE modalidades SET status_modalidade = '1' WHERE id_modalidade = {$secondModalityId}");
            $connection->query("INSERT INTO interclasse_planejamentos (id_interclasse, cronograma_status, inscricoes_status) VALUES ({$editionId}, 'rascunho', 'fechadas') ON DUPLICATE KEY UPDATE cronograma_status = 'rascunho', inscricoes_status = 'fechadas', cronograma_versao = 0");
            $connection->query("INSERT INTO modalidade_planejamentos (id_modalidade, equipes_planejadas, min_inscritos_equipe, max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min) VALUES ({$modalityId}, 3, 1, 1, 'equipe', 20, 10) ON DUPLICATE KEY UPDATE equipes_planejadas = 3, min_inscritos_equipe = 1, max_inscritos_equipe = 1, formato_participacao = 'equipe'");
            $connection->query("INSERT INTO modalidade_planejamentos (id_modalidade, equipes_planejadas, min_inscritos_equipe, max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min) VALUES ({$secondModalityId}, 3, 1, 1, 'equipe', 20, 10) ON DUPLICATE KEY UPDATE equipes_planejadas = 3, min_inscritos_equipe = 1, max_inscritos_equipe = 1, formato_participacao = 'equipe'");
            $connection->query("INSERT INTO agenda_reservas (id_interclasse, id_modalidade, chave_versao, chave_tag, data_reserva, inicio_reserva, termino_reserva, id_local) VALUES ({$editionId}, {$modalityId}, 'manual-test', 'BLOQUEIO-CRONOGRAMA', '2030-10-01', '08:00:00', '08:20:00', {$localId})");
            $reservationId = (int) $connection->insert_id;
            $service = new CronogramaService(new MysqliCronogramaRepository($connection));
            $service->preparar($editionId, 1);
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
            $published = $service->publicar($editionId, 1, ['cronograma_versao' => 0, 'compromissos' => $draft['compromissos'], 'nos' => $draft['nos']]);
            $opened = $service->abrir($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            Assertions::assert('Publicação ocorre antes da abertura das inscrições', $published['cronograma_status'] === 'publicado' && $opened['inscricoes_status'] === 'abertas');
            Assertions::assert('Estado publicado expõe a mesma revisão e compromisso', $service->estado($editionId)['cronograma_versao'] === $published['cronograma_versao']);
            $materialization = $service->materializar($editionId, 1, ['chave_tag' => (string) ($normalNodes[0]['chave_tag'] ?? '')]);
            Assertions::assert('Equipe vazia não materializa partida nem atleta fictício', $materialization['success'] === false && ($materialization['aguardando_elenco'] ?? false) === true && (int) $connection->query("SELECT COUNT(*) FROM jogos WHERE modalidades_id_modalidade = {$modalityId} AND nome_jogo = '" . $connection->real_escape_string((string) ($normalNodes[0]['chave_tag'] ?? '')) . "'")->fetch_column() === 0);
            $teamId = (int) $connection->query("SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = {$modalityId} AND e.turmas_id_turma = {$firstClassId} AND e.status_equipe = '1' ORDER BY ep.ordem_planejada LIMIT 1")->fetch_column();
            $projected = (new MysqliCronogramaRepository($connection))->commitmentsForTeams($editionId, [$teamId]);
            Assertions::assert('Projeção da inscrição inclui as fases possíveis da equipe', count($projected) === 2 && count(array_filter($projected, static fn (array $item): bool => (int) ($item['condicional'] ?? 0) === 1)) === 1);
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
            $reviewed = $service->revisar($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            $oldSnapshot = (int) $connection->query("SELECT COUNT(*) FROM cronograma_compromissos WHERE id_interclasse = {$editionId} AND cronograma_versao = {$published['cronograma_versao']}")->fetch_column();
            Assertions::assert('Revisão fecha o cronograma', $reviewed['cronograma_status'] === 'revisao');
            Assertions::assert('Revisão fecha as inscrições', $reviewed['inscricoes_status'] === 'fechadas');
            Assertions::assert('Revisão avança a versão', (int) $reviewed['cronograma_versao'] === (int) $published['cronograma_versao'] + 1);
            Assertions::assert('Revisão preserva todos os compromissos da versão suspensa', $oldSnapshot === (int) ($published['compromissos'] ?? -1));
        } finally {
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
            if ($reservationId > 0) {
                $connection->query('DELETE FROM agenda_reservas WHERE id_reserva = ' . $reservationId);
            }
            if ($createdStudentId > 0) {
                $connection->query('DELETE FROM equipes_has_usuarios WHERE usuarios_id_usuario = ' . $createdStudentId);
                $connection->query('DELETE FROM usuarios WHERE id_usuario = ' . $createdStudentId);
            }
            $statement = $connection->prepare('DELETE FROM sgi_migrations WHERE version IN (?, ?)');
            $nodesVersion = '002_cronograma_nos.sql';
            $statement->bind_param('ss', $version, $nodesVersion);
            $statement->execute();
            $statement->close();
            $connection->close();
        }
    }
}
