<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class RankingPublicationTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite: Publicação do ranking por edição]\033[0m\n";

        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($databaseName);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = null;
        $termsCreated = false;

        try {
            $fixture = AuditFixtures::createAuthorizationFixture($connection);
            $editionA = $fixture['by_edition']['A'];
            $editionB = $fixture['by_edition']['B'];
            $editionAId = (int) $editionA['interclasse_id'];
            $editionBId = (int) $editionB['interclasse_id'];
            $studentId = (int) $editionB['atleta_ids'][0];
            $gameId = (int) $editionB['jogo_ids'][0];

            $activateStudent = $connection->prepare("UPDATE usuarios SET status_usuario = '1' WHERE id_usuario = ?");
            $activateStudent->bind_param('i', $studentId);
            $activateStudent->execute();
            $activateStudent->close();

            $acceptedAt = '2026-09-12 12:00:00';
            $terms = $connection->prepare(
                "INSERT INTO usuarios_has_interclasses
                    (usuarios_id_usuario, interclasses_id_interclasse, dt_hr_aceita, aceito_termo, status_termo)
                 VALUES (?, ?, ?, 'sim', 'Ativo')",
            );
            $terms->bind_param('iis', $studentId, $editionBId, $acceptedAt);
            $terms->execute();
            $terms->close();
            $termsCreated = true;

            $matricula = self::studentRegistration($connection, $studentId);
            $student = new TestClient();
            $login = $student->login($matricula, 'fixture-password');
            Assertions::assertJsonSuccess('Aluno ativo de B autentica com aceite de termos', $login);

            $unpublished = $student->get('api/v1/ranking?id_interclasse=' . $editionBId);
            Assertions::assert(
                'Aluno não lê edição B encerrada enquanto o ranking não foi publicado',
                $unpublished['code'] === 403
                && ($unpublished['json']['success'] ?? true) === false
                && !str_contains((string) $unpublished['body'], 'nome_turma'),
            );

            $admin = new TestClient();
            $admin->login('admin', '123');
            $collaborator = new TestClient();
            $collaborator->login('colab', '123');
            $adminRanking = $admin->get('api/v1/ranking?id_interclasse=' . $editionBId);
            $collaboratorRanking = $collaborator->get('api/v1/ranking?id_interclasse=' . $editionBId);
            Assertions::assert(
                'Administrador e colaborador continuam lendo ranking não publicado',
                $adminRanking['code'] === 200
                && $collaboratorRanking['code'] === 200
                && count($adminRanking['json'] ?? []) > 0
                && count($collaboratorRanking['json'] ?? []) > 0,
            );

            $withoutEdition = $student->get('api/v1/ranking');
            $missingEdition = $student->get('api/v1/ranking?id_interclasse=' . (max($fixture['ids']['interclasses']) + 100000));
            Assertions::assert(
                'ID de edição ausente e inexistente não retornam dados do ranking',
                $withoutEdition['code'] === 400
                && $missingEdition['code'] === 403
                && !str_contains((string) $missingEdition['body'], 'nome_turma'),
            );

            $finishGame = $connection->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
            $finishGame->bind_param('i', $gameId);
            $finishGame->execute();
            $finishGame->close();
            $published = $admin->postJson(
                'api/v1/edicoes?acao=publicar_ranking&id=' . $editionBId,
                [],
            );
            Assertions::assertJsonSuccess('Administrador publica ranking de edição encerrada', $published);

            $publishedRanking = $student->get('api/v1/ranking?id_interclasse=' . $editionBId);
            Assertions::assert(
                'Aluno consulta dados de B somente depois do encerramento e publicação',
                $publishedRanking['code'] === 200
                && is_array($publishedRanking['json'] ?? null)
                && count($publishedRanking['json']) > 0,
            );

            self::setEditionStatus($connection, $editionAId, '0');
            self::setEditionStatus($connection, $editionBId, '1');
            $reactivatedPublished = $student->get('api/v1/ranking?id_interclasse=' . $editionBId);
            Assertions::assert(
                'Edição publicada volta a ser bloqueada ao ser reativada',
                $reactivatedPublished['code'] === 403
                && ($reactivatedPublished['json']['success'] ?? true) === false
                && !str_contains((string) $reactivatedPublished['body'], 'nome_turma'),
            );

            self::setEditionStatus($connection, $editionBId, '0');
            self::setEditionStatus($connection, $editionAId, '1');
        } finally {
            if ($fixture !== null) {
                if ($termsCreated) {
                    $studentId = (int) $fixture['by_edition']['B']['atleta_ids'][0];
                    $editionBId = (int) $fixture['by_edition']['B']['interclasse_id'];
                    $removeTerms = $connection->prepare(
                        'DELETE FROM usuarios_has_interclasses WHERE usuarios_id_usuario = ? AND interclasses_id_interclasse = ?',
                    );
                    $removeTerms->bind_param('ii', $studentId, $editionBId);
                    $removeTerms->execute();
                    $removeTerms->close();
                }
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

    private static function studentRegistration(\mysqli $connection, int $studentId): string
    {
        $statement = $connection->prepare('SELECT matricula_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1');
        $statement->bind_param('i', $studentId);
        $statement->execute();
        $registration = (string) ($statement->get_result()->fetch_column() ?: '');
        $statement->close();
        return $registration;
    }

    private static function setEditionStatus(\mysqli $connection, int $editionId, string $status): void
    {
        $statement = $connection->prepare('UPDATE interclasses SET status_interclasse = ? WHERE id_interclasse = ?');
        $statement->bind_param('si', $status, $editionId);
        $statement->execute();
        $statement->close();
    }
}
