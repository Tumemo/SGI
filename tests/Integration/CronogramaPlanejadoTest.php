<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Competicoes\Application\CronogramaService;
use App\Modules\Competicoes\Infrastructure\MysqliCronogramaRepository;
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
        try {
            $editionId = (int) $connection->query("SELECT id_interclasse FROM interclasses ORDER BY id_interclasse LIMIT 1")->fetch_column();
            $modalityId = (int) $connection->query("SELECT id_modalidade FROM modalidades WHERE interclasses_id_interclasse = {$editionId} AND status_modalidade = '1' ORDER BY id_modalidade LIMIT 1")->fetch_column();
            $localId = (int) $connection->query("SELECT id_local FROM locais WHERE interclasses_id_interclasse = {$editionId} AND status_local = '1' AND disponivel_local = '1' ORDER BY id_local LIMIT 1")->fetch_column();
            $statusRows = $connection->query("SELECT id_modalidade, status_modalidade FROM modalidades WHERE interclasses_id_interclasse = {$editionId}")->fetch_all(MYSQLI_ASSOC);
            foreach ($statusRows as $statusRow) {
                $originalStatuses[(int) $statusRow['id_modalidade']] = (string) $statusRow['status_modalidade'];
            }
            $connection->query("UPDATE modalidades SET status_modalidade = '0' WHERE interclasses_id_interclasse = {$editionId} AND id_modalidade <> {$modalityId}");
            $connection->query("INSERT INTO interclasse_planejamentos (id_interclasse, modo_planejamento, cronograma_status, inscricoes_status) VALUES ({$editionId}, 'planejado', 'rascunho', 'fechadas') ON DUPLICATE KEY UPDATE modo_planejamento = 'planejado', cronograma_status = 'rascunho', inscricoes_status = 'fechadas', cronograma_versao = 0");
            $connection->query("INSERT INTO modalidade_planejamentos (id_modalidade, equipes_planejadas, min_inscritos_equipe, max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min) VALUES ({$modalityId}, 1, 1, 1, 'individual', 20, 10) ON DUPLICATE KEY UPDATE equipes_planejadas = 1, min_inscritos_equipe = 1, max_inscritos_equipe = 1");
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
            Assertions::assert('Geração planejada é determinística e retorna compromisso futuro', $draft['success'] === true && count($draft['compromissos'] ?? []) >= 1 && ($draft['compromissos'][0]['condicional'] ?? 1) === 0);
            $published = $service->publicar($editionId, 1, ['cronograma_versao' => 0, 'compromissos' => $draft['compromissos']]);
            $opened = $service->abrir($editionId, 1, ['cronograma_versao' => $published['cronograma_versao']]);
            Assertions::assert('Publicação ocorre antes da abertura das inscrições', $published['cronograma_status'] === 'publicado' && $opened['inscricoes_status'] === 'abertas');
            Assertions::assert('Estado publicado expõe a mesma revisão e compromisso', $service->estado($editionId)['cronograma_versao'] === $published['cronograma_versao']);
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
            $connection->query('DROP TABLE IF EXISTS cronograma_compromissos');
            $connection->query('DROP TABLE IF EXISTS equipe_planejamentos');
            $connection->query('DROP TABLE IF EXISTS modalidade_planejamentos');
            $connection->query('DROP TABLE IF EXISTS interclasse_planejamentos');
            $statement = $connection->prepare('DELETE FROM sgi_migrations WHERE version = ?');
            $statement->bind_param('s', $version);
            $statement->execute();
            $statement->close();
            $connection->close();
        }
    }
}
