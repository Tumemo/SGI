<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestDatabase;

final class AuditFixturesTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 0: Fixtures sintéticas da auditoria]\033[0m\n";

        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = null;

        try {
            $fixture = AuditFixtures::createAuthorizationFixture($connection);
            $activeId = AuditFixtures::activeEditionId($connection);

            Assertions::assert('Fixture cria duas edições com IDs distintos',
                $fixture['by_edition']['A']['interclasse_id'] > 0
                && $fixture['by_edition']['B']['interclasse_id'] > 0
                && $fixture['by_edition']['A']['interclasse_id'] !== $fixture['by_edition']['B']['interclasse_id']);
            Assertions::assert('Fixture deixa a edição A ativa e B inativa',
                $activeId === $fixture['by_edition']['A']['interclasse_id']
                && self::editionStatus($connection, $fixture['by_edition']['B']['interclasse_id']) === '0');

            foreach (['A', 'B'] as $label) {
                $edition = $fixture['by_edition'][$label];
                Assertions::assert("Fixture cria duas turmas em {$label}", count($edition['turma_ids']) === 2);
                Assertions::assert("Fixture cria duas equipes em {$label}", count($edition['equipe_ids']) === 2);
                Assertions::assert("Fixture cria dois atletas em {$label}", count($edition['atleta_ids']) === 2);
                Assertions::assert("Fixture cria jogo e duas partidas em {$label}",
                    count($edition['jogo_ids']) === 1 && count($edition['partida_ids']) === 2);
            }
        } finally {
            if ($fixture !== null) {
                AuditFixtures::restoreAndRemove($connection, $fixture, $previousActiveId);
            }
        }

        Assertions::assert('Fixture restaura a edição ativa anterior',
            AuditFixtures::activeEditionId($connection) === $previousActiveId);
        foreach ([
            'interclasses' => 'id_interclasse',
            'categorias' => 'id_categoria',
            'turmas' => 'id_turma',
            'modalidades' => 'id_modalidade',
            'locais' => 'id_local',
            'equipes' => 'id_equipe',
            'usuarios' => 'id_usuario',
            'jogos' => 'id_jogo',
            'partidas' => 'id_partida',
        ] as $table => $column) {
            Assertions::assert(
                "Fixture remove os registros sintéticos de {$table}",
                AuditFixtures::countRowsByIds($connection, $table, $column, $fixture['ids'][$table]) === 0,
            );
        }
        foreach ($fixture['auto_increments'] as $table => $expected) {
            Assertions::assert(
                "Fixture restaura AUTO_INCREMENT de {$table}",
                AuditFixtures::autoIncrement($connection, $table) === $expected,
            );
        }
        $connection->close();
    }

    private static function editionStatus(\mysqli $connection, int $editionId): string
    {
        $statement = $connection->prepare('SELECT status_interclasse FROM interclasses WHERE id_interclasse = ?');
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $status = (string) ($statement->get_result()->fetch_column() ?? '');
        $statement->close();
        return $status;
    }
}
