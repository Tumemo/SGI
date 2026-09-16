<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Shared\Database\SchemaInstaller;
use mysqli;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class MigrationsTest
{
    public static function run(): void
    {
        echo "\n  [Schema inicial e migrations futuras]\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $installer = new SchemaInstaller($connection, dirname(__DIR__, 2) . '/database/schema-inicial.sql');

        Assertions::assert('Reexecução do schema inicial não reaplica alterações', $installer->install() === false);
        Assertions::assert('Instalação registra o baseline para migrations futuras', self::hasMigrationRecord($connection, SchemaInstaller::BASELINE_VERSION));
        Assertions::assert('Baseline cria as 23 tabelas esperadas', self::tableCount($connection) === 23);
        Assertions::assert('Schema inclui o vínculo estruturado de vermelhos automáticos',
            self::hasTable($connection, 'ocorrencias_vermelhos_automaticos')
            && self::hasIndex($connection, 'ocorrencias_vermelhos_automaticos', 'uk_vermelho_automatico_usuario_jogo')
            && self::hasForeignKey($connection, 'ocorrencias_vermelhos_automaticos', 'fk_vermelho_automatico_ocorrencia')
            && self::hasForeignKey($connection, 'ocorrencias_vermelhos_automaticos', 'fk_vermelho_automatico_amarelo'),
        );
        Assertions::assert('Schema inclui a troca obrigatória de senha para alunos',
            self::hasColumn($connection, 'usuarios', 'senha_troca_pendente')
            && self::columnDefault($connection, 'usuarios', 'senha_troca_pendente') === '0',
        );
        Assertions::assert('Categorias possuem unicidade de nome por edição', self::hasUniqueIndex($connection, 'categorias', 'uk_categorias_edicao_nome'));
        Assertions::assert('Usuários possuem versão de autorização para revogar sessões', self::hasColumn($connection, 'usuarios', 'auth_version'));
        Assertions::assert('Ocorrências de turma não aceitam pontos negativos', self::hasCheckConstraint($connection, 'ocorrencias_turmas', 'chk_ocorrencias_turmas_pontos_nonnegative'));
        Assertions::assert('Ocorrências individuais não aceitam penalidade negativa', self::hasCheckConstraint($connection, 'ocorrencias', 'chk_ocorrencias_penalidade_nonnegative'));
        Assertions::assert('Jogos novos exigem vínculo de atleta no placar', self::hasColumn($connection, 'jogos', 'exige_vinculo_ponto'));
        Assertions::assert('Pontos preservam partida, status e chave idempotente',
            self::hasColumn($connection, 'artilheiros', 'partidas_id_partida')
            && self::hasColumn($connection, 'artilheiros', 'equipes_id_equipe')
            && self::hasColumn($connection, 'artilheiros', 'status_artilheiro')
            && self::hasIndex($connection, 'artilheiros', 'uk_artilheiros_chave_jogada'),
        );
        Assertions::assert('Idempotência de sincronização preserva o fingerprint', self::hasColumn($connection, 'sincronizacoes_idempotentes', 'request_hash'));
        Assertions::assert('Baseline mantém somente o trigger atual de arrecadação',
            self::hasTrigger($connection, 'tr_atualiza_pontos_arrecadacao')
            && !self::hasTrigger($connection, 'tr_sincroniza_status_usuarios'),
        );
        $connection->close();
    }

    private static function tableCount(mysqli $connection): int
    {
        return (int) $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name <> 'sgi_migrations'")->fetch_row()[0];
    }

    private static function hasUniqueIndex(mysqli $connection, string $table, string $index): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ?
               AND index_name = ? AND non_unique = 0',
        );
        $statement->bind_param('ss', $table, $index);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count > 0;
    }

    private static function hasIndex(mysqli $connection, string $table, string $index): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
        );
        $statement->bind_param('ss', $table, $index);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count > 0;
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

    private static function hasTrigger(mysqli $connection, string $trigger): bool
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = ?');
        $statement->bind_param('s', $trigger);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }

    private static function hasMigrationRecord(mysqli $connection, string $version): bool
    {
        $statement = $connection->prepare('SELECT COUNT(*) FROM sgi_migrations WHERE version = ? AND dirty = 0');
        $statement->bind_param('s', $version);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count === 1;
    }
}
