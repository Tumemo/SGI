<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;
use mysqli;

final class TurmaScopeConsistencyTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite: Consistência do escopo das turmas]\033[0m\n";

        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($databaseName);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = null;
        $classIds = [];
        $categoryIds = [];
        $studentIds = [];

        try {
            $fixture = AuditFixtures::createAuthorizationFixture($connection);
            $editionA = $fixture['by_edition']['A'];
            $editionB = $fixture['by_edition']['B'];
            $editionAId = (int) $editionA['interclasse_id'];
            $editionBId = (int) $editionB['interclasse_id'];
            $categoryAId = (int) $editionA['categoria_id'];
            $categoryBId = (int) $editionB['categoria_id'];

            $admin = new TestClient();
            Assertions::assertJsonSuccess('Administrador autentica para alterar escopo de turma', $admin->login('admin', '123'));

            $emptyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma sem vínculos');
            $studentOnlyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma com aluno');
            $teamOnlyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma com equipe');
            $donationOnlyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma com doação');
            $podiumOnlyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma com pódio');
            $occurrenceOnlyClassId = self::insertClass($connection, $classIds, $editionAId, $categoryAId, 'N10 turma com ocorrência');

            $inactiveCategoryId = self::insertCategory($connection, $categoryIds, $editionAId, 'N10 categoria inativa', '0');
            $secondCategoryId = self::insertCategory($connection, $categoryIds, $editionAId, 'N10 categoria ativa', '1');

            $invalidCreateName = 'N10 create mismatch ' . bin2hex(random_bytes(3));
            $invalidCreate = $admin->postJson('api/v1/turmas', [
                'interclasses_id_interclasse' => $editionAId,
                'categorias_id_categoria' => $categoryBId,
                'nome_turma' => $invalidCreateName,
                'turno_turma' => 'manha',
            ]);
            $createdInvalidId = (int) ($invalidCreate['json']['id_turma'] ?? 0);
            if ($createdInvalidId > 0) {
                $classIds[] = $createdInvalidId;
            }
            Assertions::assert(
                'Endpoint de turmas recusa criação com categoria de outra edição',
                (int) ($invalidCreate['code'] ?? 0) === 400 && $createdInvalidId === 0,
                'Esperado HTTP 400 sem persistência; obtido HTTP ' . (int) ($invalidCreate['code'] ?? 0) . '.',
            );

            $categoryOnlyRanking = $admin->putJson('api/v1/ranking', [
                'id_turma' => $emptyClassId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Ranking recusa categoria de outra edição',
                $connection,
                $categoryOnlyRanking,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $editionOnlyTurmas = $admin->putJson('api/v1/turmas', [
                'id_turma' => $emptyClassId,
                'interclasses_id_interclasse' => $editionBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas recusa edição parcial incompatível com a categoria atual',
                $connection,
                $editionOnlyTurmas,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $categoryOnlyTurmas = $admin->putJson('api/v1/turmas', [
                'id_turma' => $emptyClassId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas recusa categoria parcial de outra edição',
                $connection,
                $categoryOnlyTurmas,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $missingEdition = $admin->putJson('api/v1/ranking', [
                'id_turma' => $emptyClassId,
                'interclasses_id_interclasse' => max($fixture['ids']['interclasses']) + 100000,
                'categorias_id_categoria' => $categoryAId,
            ]);
            self::assertRejectedAndUnchanged(
                'Ranking recusa edição inexistente sem alterar vínculo',
                $connection,
                $missingEdition,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $missingCategory = $admin->putJson('api/v1/turmas', [
                'id_turma' => $emptyClassId,
                'categorias_id_categoria' => max($fixture['ids']['categorias']) + 100000,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas recusa categoria inexistente sem alterar vínculo',
                $connection,
                $missingCategory,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $inactiveCategory = $admin->putJson('api/v1/turmas', [
                'id_turma' => $emptyClassId,
                'categorias_id_categoria' => $inactiveCategoryId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas recusa categoria inativa sem alterar vínculo',
                $connection,
                $inactiveCategory,
                $emptyClassId,
                $editionAId,
                $categoryAId,
            );

            $missingClass = $admin->putJson('api/v1/turmas', [
                'id_turma' => max($fixture['ids']['turmas']) + 100000,
                'nome_turma' => 'N10 inexistente',
            ]);
            Assertions::assertStatus('Endpoint de turmas mantém 404 para turma inexistente', $missingClass, 404);

            $linkedClassId = (int) $editionA['turma_ids'][0];
            $linkedRankingTransfer = $admin->putJson('api/v1/ranking', [
                'id_turma' => $linkedClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Ranking bloqueia transferência de turma com alunos e equipes',
                $connection,
                $linkedRankingTransfer,
                $linkedClassId,
                $editionAId,
                $categoryAId,
            );

            $linkedTurmasTransfer = $admin->putJson('api/v1/turmas', [
                'id_turma' => $linkedClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas bloqueia a mesma transferência vinculada',
                $connection,
                $linkedTurmasTransfer,
                $linkedClassId,
                $editionAId,
                $categoryAId,
            );

            $studentId = self::insertStudent($connection, $studentIds, $studentOnlyClassId, $editionAId);
            $teamId = self::insertTeam($connection, $teamOnlyClassId, (int) $editionA['modalidade_id']);
            self::insertDonation($connection, $donationOnlyClassId, $editionAId);
            self::insertPodium($connection, $podiumOnlyClassId, $editionAId, (int) $editionA['modalidade_id']);
            self::insertOccurrence($connection, $occurrenceOnlyClassId, $editionAId);

            $studentTransfer = $admin->putJson('api/v1/ranking', [
                'id_turma' => $studentOnlyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Ranking preserva aluno vinculado ao impedir transferência',
                $connection,
                $studentTransfer,
                $studentOnlyClassId,
                $editionAId,
                $categoryAId,
            );

            $teamTransfer = $admin->putJson('api/v1/turmas', [
                'id_turma' => $teamOnlyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas preserva equipe vinculada ao impedir transferência',
                $connection,
                $teamTransfer,
                $teamOnlyClassId,
                $editionAId,
                $categoryAId,
            );

            $donationTransfer = $admin->putJson('api/v1/turmas', [
                'id_turma' => $donationOnlyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas preserva histórico de doação ao impedir transferência',
                $connection,
                $donationTransfer,
                $donationOnlyClassId,
                $editionAId,
                $categoryAId,
            );

            $podiumTransfer = $admin->putJson('api/v1/ranking', [
                'id_turma' => $podiumOnlyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Ranking preserva crédito de pódio ao impedir transferência',
                $connection,
                $podiumTransfer,
                $podiumOnlyClassId,
                $editionAId,
                $categoryAId,
            );

            $occurrenceTransfer = $admin->putJson('api/v1/turmas', [
                'id_turma' => $occurrenceOnlyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
            ]);
            self::assertRejectedAndUnchanged(
                'Endpoint de turmas preserva histórico disciplinar ao impedir transferência',
                $connection,
                $occurrenceTransfer,
                $occurrenceOnlyClassId,
                $editionAId,
                $categoryAId,
            );

            $validRankingUpdate = $admin->putJson('api/v1/ranking', [
                'id_turma' => $emptyClassId,
                'interclasses_id_interclasse' => $editionBId,
                'categorias_id_categoria' => $categoryBId,
                'nome_fantasia_turma' => 'Nome preservado',
                'pontuacao_turma' => 41,
            ]);
            Assertions::assertJsonSuccess('Ranking aceita transferência válida sem descendentes e mantém ajuste de pontos', $validRankingUpdate);
            Assertions::assert(
                'Ranking persiste escopo final, descrição e pontuação informados',
                self::scopeMatches($connection, $emptyClassId, $editionBId, $categoryBId)
                && self::detailsMatch($connection, $emptyClassId, 'Nome preservado', 41),
            );

            $validTurmasUpdate = $admin->putJson('api/v1/turmas', [
                'id_turma' => $emptyClassId,
                'interclasses_id_interclasse' => $editionAId,
                'categorias_id_categoria' => $secondCategoryId,
            ]);
            Assertions::assertJsonSuccess('Endpoint de turmas aceita edição e categoria compatíveis sem descendentes', $validTurmasUpdate);
            Assertions::assert(
                'Endpoint de turmas persiste categoria válida na edição atual',
                self::scopeMatches($connection, $emptyClassId, $editionAId, $secondCategoryId),
            );

            $rankingDescription = $admin->putJson('api/v1/ranking', [
                'id_turma' => $linkedClassId,
                'nome_fantasia_turma' => 'Ajuste legítimo',
                'pontuacao_turma' => 57,
            ]);
            Assertions::assertJsonSuccess('Ranking mantém atualização descritiva e ajuste legítimo em turma vinculada', $rankingDescription);
            Assertions::assert(
                'Ajuste legítimo não desloca histórico da turma vinculada',
                self::scopeMatches($connection, $linkedClassId, $editionAId, $categoryAId)
                && self::detailsMatch($connection, $linkedClassId, 'Ajuste legítimo', 57),
            );
        } finally {
            if ($fixture !== null) {
                self::removeSyntheticRows($connection, $classIds, $categoryIds, $studentIds);
                $connection->close();
                AuditFixtures::restoreAndRemove(
                    TestDatabase::connect($databaseName),
                    $fixture,
                    $previousActiveId,
                );
            } else {
                $connection->close();
            }
        }
    }

    /** @param list<int> $classIds @param list<int> $categoryIds */
    private static function insertClass(mysqli $connection, array &$classIds, int $editionId, int $categoryId, string $name): int
    {
        $statement = $connection->prepare(
            "INSERT INTO turmas
                (interclasses_id_interclasse, nome_turma, turno_turma, nome_fantasia_turma, status_turma, categorias_id_categoria)
             VALUES (?, ?, 'manha', NULL, '1', ?)",
        );
        $statement->bind_param('isi', $editionId, $name, $categoryId);
        $statement->execute();
        $classId = (int) $statement->insert_id;
        $statement->close();
        $classIds[] = $classId;
        return $classId;
    }

    /** @param list<int> $categoryIds */
    private static function insertCategory(mysqli $connection, array &$categoryIds, int $editionId, string $name, string $status): int
    {
        $statement = $connection->prepare(
            'INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, ?, ?)',
        );
        $statement->bind_param('ssi', $name, $status, $editionId);
        $statement->execute();
        $categoryId = (int) $statement->insert_id;
        $statement->close();
        $categoryIds[] = $categoryId;
        return $categoryId;
    }

    /** @param list<int> $studentIds */
    private static function insertStudent(mysqli $connection, array &$studentIds, int $classId, int $editionId): int
    {
        $registration = 'N10' . bin2hex(random_bytes(5));
        $name = 'N10 aluno vinculado';
        $password = password_hash('fixture-password', PASSWORD_DEFAULT);
        $statement = $connection->prepare(
            "INSERT INTO usuarios
                (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario,
                 data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao)
             VALUES ('RM', ?, ?, ?, '3', 'MASC', '2010-01-01', '', '0', ?, ?, NULL)",
        );
        $statement->bind_param('sssii', $registration, $name, $password, $classId, $editionId);
        $statement->execute();
        $studentId = (int) $statement->insert_id;
        $statement->close();
        $studentIds[] = $studentId;
        return $studentId;
    }

    private static function insertTeam(mysqli $connection, int $classId, int $modalityId): int
    {
        $name = 'N10 equipe sem atletas';
        $statement = $connection->prepare(
            "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
             VALUES ('1', ?, ?, ?)",
        );
        $statement->bind_param('iis', $modalityId, $classId, $name);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();
        return $id;
    }

    private static function insertDonation(mysqli $connection, int $classId, int $editionId): void
    {
        $quantity = 1.0;
        $points = 2;
        $statement = $connection->prepare(
            "INSERT INTO historico_arrecadacoes (id_turma, id_interclasse, quantidade, pontos_adicionados, status_historico)
             VALUES (?, ?, ?, ?, '1')",
        );
        $statement->bind_param('iidi', $classId, $editionId, $quantity, $points);
        $statement->execute();
        $statement->close();
    }

    private static function insertPodium(mysqli $connection, int $classId, int $editionId, int $modalityId): void
    {
        $position = 1;
        $points = 10;
        $origin = 'manual';
        $statement = $connection->prepare(
            'INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, pontos, origem_registro)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $statement->bind_param('iiiiis', $editionId, $modalityId, $position, $classId, $points, $origin);
        $statement->execute();
        $statement->close();
    }

    private static function insertOccurrence(mysqli $connection, int $classId, int $editionId): void
    {
        $title = 'N10 ocorrência sintética';
        $description = 'Somente vínculo histórico da turma.';
        $points = 1;
        $date = date('Y-m-d');
        $statement = $connection->prepare(
            'INSERT INTO ocorrencias_turmas
                (turmas_id_turma, interclasses_id_interclasse, titulo_ocorrencia, descricao_ocorrencia, pontos_descontados, data_ocorrencia)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $statement->bind_param('iissis', $classId, $editionId, $title, $description, $points, $date);
        $statement->execute();
        $statement->close();
    }

    /** @param list<int> $classIds @param list<int> $categoryIds @param list<int> $studentIds */
    private static function removeSyntheticRows(mysqli $connection, array $classIds, array $categoryIds, array $studentIds): void
    {
        if ($classIds !== []) {
            $placeholders = implode(',', array_fill(0, count($classIds), '?'));
            foreach ([
                'historico_arrecadacoes' => 'id_turma',
                'pontuacoes_podio' => 'id_turma',
                'ocorrencias_turmas' => 'turmas_id_turma',
                'equipes' => 'turmas_id_turma',
                'usuarios' => 'turmas_id_turma',
            ] as $table => $column) {
                $statement = $connection->prepare("DELETE FROM {$table} WHERE {$column} IN ({$placeholders})");
                $types = str_repeat('i', count($classIds));
                $statement->bind_param($types, ...$classIds);
                $statement->execute();
                $statement->close();
            }
            $statement = $connection->prepare("DELETE FROM turmas WHERE id_turma IN ({$placeholders})");
            $types = str_repeat('i', count($classIds));
            $statement->bind_param($types, ...$classIds);
            $statement->execute();
            $statement->close();
        }
        if ($categoryIds !== []) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $statement = $connection->prepare("DELETE FROM categorias WHERE id_categoria IN ({$placeholders})");
            $types = str_repeat('i', count($categoryIds));
            $statement->bind_param($types, ...$categoryIds);
            $statement->execute();
            $statement->close();
        }
        if ($studentIds !== []) {
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $statement = $connection->prepare("DELETE FROM usuarios WHERE id_usuario IN ({$placeholders})");
            $types = str_repeat('i', count($studentIds));
            $statement->bind_param($types, ...$studentIds);
            $statement->execute();
            $statement->close();
        }
    }

    /** @param array<string, mixed> $response */
    private static function assertRejectedAndUnchanged(
        string $name,
        mysqli $connection,
        array $response,
        int $classId,
        int $editionId,
        int $categoryId,
    ): void {
        $status = (int) ($response['code'] ?? 0);
        Assertions::assert(
            $name,
            $status === 400 && self::scopeMatches($connection, $classId, $editionId, $categoryId),
            "Esperado HTTP 400 com escopo intacto; obtido HTTP {$status}.",
        );
    }

    private static function scopeMatches(mysqli $connection, int $classId, int $editionId, int $categoryId): bool
    {
        $statement = $connection->prepare(
            'SELECT interclasses_id_interclasse, categorias_id_categoria FROM turmas WHERE id_turma = ? LIMIT 1',
        );
        $statement->bind_param('i', $classId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row !== null
            && (int) $row['interclasses_id_interclasse'] === $editionId
            && (int) $row['categorias_id_categoria'] === $categoryId;
    }

    private static function detailsMatch(mysqli $connection, int $classId, string $fantasyName, int $score): bool
    {
        $statement = $connection->prepare(
            'SELECT nome_fantasia_turma, pontuacao_turma FROM turmas WHERE id_turma = ? LIMIT 1',
        );
        $statement->bind_param('i', $classId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row !== null
            && (string) $row['nome_fantasia_turma'] === $fantasyName
            && (int) $row['pontuacao_turma'] === $score;
    }
}
