<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\MigrationRunner;
use mysqli;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class MigrationsTest
{
    public static function run(): void
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $query = 'SELECT id_usuario, matricula_usuario, interclasses_id_interclasse, senha_usuario FROM usuarios ORDER BY id_usuario';
        $before = $connection->query($query)->fetch_all(MYSQLI_ASSOC);
        $runner = new MigrationRunner($connection, dirname(__DIR__, 2) . '/database/migrations');
        Assertions::assert('Segunda execução das migrações não reaplica alterações', $runner->migrate() === []);
        Assertions::assert('Migrações preservam identidades, edições e senhas', $before === $connection->query($query)->fetch_all(MYSQLI_ASSOC) && count($before) > 0);
        Assertions::assert('Categorias possuem unicidade de nome por edição', self::hasUniqueIndex($connection, 'categorias', 'uk_categorias_edicao_nome'));
        Assertions::assert('Usuários possuem versão de autorização para revogar sessões', self::hasColumn($connection, 'usuarios', 'auth_version'));
        Assertions::assert('Usuários persistem o estado de troca obrigatória de senha', self::hasColumn($connection, 'usuarios', 'senha_troca_pendente'));
        Assertions::assert('A pendência de troca de senha assume falso para novos usuários', self::columnDefault($connection, 'usuarios', 'senha_troca_pendente') === '0');
        Assertions::assert('Ocorrências de turma não aceitam pontos negativos', self::hasCheckConstraint($connection, 'ocorrencias_turmas', 'chk_ocorrencias_turmas_pontos_nonnegative'));
        Assertions::assert('Ocorrências individuais não aceitam penalidade negativa', self::hasCheckConstraint($connection, 'ocorrencias', 'chk_ocorrencias_penalidade_nonnegative'));
        Assertions::assert('Vermelhos automáticos possuem vínculo estruturado e único por atleta/jogo',
            self::hasTable($connection, 'ocorrencias_vermelhos_automaticos')
            && self::hasIndex($connection, 'ocorrencias_vermelhos_automaticos', 'uk_vermelho_automatico_usuario_jogo')
            && self::hasIndex($connection, 'ocorrencias_vermelhos_automaticos', 'PRIMARY')
            && self::hasForeignKey($connection, 'ocorrencias_vermelhos_automaticos', 'fk_vermelho_automatico_ocorrencia')
            && self::hasForeignKey($connection, 'ocorrencias_vermelhos_automaticos', 'fk_vermelho_automatico_amarelo'),
        );
        Assertions::assert('Jogos novos exigem vínculo de atleta no placar', self::hasColumn($connection, 'jogos', 'exige_vinculo_ponto'));
        Assertions::assert('Pontos preservam partida, status e chave idempotente',
            self::hasColumn($connection, 'artilheiros', 'partidas_id_partida')
            && self::hasColumn($connection, 'artilheiros', 'equipes_id_equipe')
            && self::hasColumn($connection, 'artilheiros', 'status_artilheiro')
            && self::hasIndex($connection, 'artilheiros', 'uk_artilheiros_chave_jogada'),
        );
        $connection->close();
    }

    private static function hasUniqueIndex(mysqli $connection, string $table, string $index): bool
    {
        $table = $connection->real_escape_string($table);
        $index = $connection->real_escape_string($index);
        $sql = "SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = '{$table}'
                  AND index_name = '{$index}'
                  AND non_unique = 0";

        return (int) $connection->query($sql)->fetch_row()[0] === 2;
    }

    private static function hasIndex(mysqli $connection, string $table, string $index): bool
    {
        $table = $connection->real_escape_string($table);
        $index = $connection->real_escape_string($index);
        return (int) $connection->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = '{$table}' AND index_name = '{$index}' AND non_unique = 0")->fetch_row()[0] > 0;
    }

    private static function hasColumn(mysqli $connection, string $table, string $column): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }

    private static function hasTable(mysqli $connection, string $table): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        );
        $statement->bind_param('s', $table);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }

    private static function columnDefault(mysqli $connection, string $table, string $column): ?string
    {
        $statement = $connection->prepare(
            'SELECT column_default FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return is_string($value) ? trim($value, "'\"") : null;
    }

    private static function hasForeignKey(mysqli $connection, string $table, string $constraint): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.table_constraints
             WHERE constraint_schema = DATABASE() AND table_name = ?
               AND constraint_name = ? AND constraint_type = \'FOREIGN KEY\'',
        );
        $statement->bind_param('ss', $table, $constraint);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }

    private static function hasCheckConstraint(mysqli $connection, string $table, string $constraint): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.table_constraints
             WHERE constraint_schema = DATABASE() AND table_name = ?
               AND constraint_name = ? AND constraint_type = \'CHECK\'',
        );
        $statement->bind_param('ss', $table, $constraint);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }
}
