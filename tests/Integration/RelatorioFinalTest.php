<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\AuditFixtures;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class RelatorioFinalTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite: Relatório final por interclasse e PDF]\033[0m\n";

        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($databaseName);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = null;
        try {
            $fixture = AuditFixtures::createAuthorizationFixture($connection);
            $editionA = $fixture['by_edition']['A'];
            $editionB = $fixture['by_edition']['B'];
            $editionAId = (int) $editionA['interclasse_id'];
            $editionBId = (int) $editionB['interclasse_id'];
            $classA = (int) $editionA['turma_ids'][0];
            $classB = (int) $editionA['turma_ids'][1];
            $classOtherEdition = (int) $editionB['turma_ids'][0];
            $modalityA = (int) $editionA['modalidade_id'];
            $modalityB = (int) $editionB['modalidade_id'];
            $userA = (int) $editionA['atleta_ids'][0];
            $userB = (int) $editionB['atleta_ids'][0];
            $gameA = (int) $editionA['jogo_ids'][0];
            $gameB = (int) $editionB['jogo_ids'][0];
            self::prepareReportRows($connection, $editionAId, $editionBId, $classA, $classB, $classOtherEdition, $modalityA, $modalityB, $userA, $userB, $gameA, $gameB);

            $admin = new TestClient();
            Assertions::assertJsonSuccess('Administrador autentica para consultar o relatório', $admin->login('admin', '123'));
            $reportA = $admin->get('api/v1/relatorio-final?id_interclasse=' . $editionAId);
            $dataA = $reportA['json']['relatorio'] ?? [];
            $classIdsA = array_map(static fn (array $row): int => (int) $row['id_turma'], $dataA['ranking_geral'] ?? []);
            Assertions::assert(
                'Relatório usa apenas turmas do interclasse solicitado',
                $reportA['code'] === 200
                && ($dataA['edicao']['id_interclasse'] ?? 0) === $editionAId
                && in_array($classA, $classIdsA, true)
                && !in_array($classOtherEdition, $classIdsA, true),
            );
            Assertions::assert(
                'Ranking e métricas incluem doações e penalidades consolidadas',
                ($dataA['metricas']['doacoes_total_kg'] ?? null) === 3.5
                && ($dataA['ranking_geral'][0]['penalidades_pontos'] ?? null) === 3
                && ($dataA['ranking_geral'][0]['pontuacao_liquida'] ?? null) === 47,
            );
            Assertions::assert(
                'Artilharia lista apenas modalidades habilitadas e gols válidos da edição',
                count($dataA['modalidades'][0]['artilheiros'] ?? []) === 1
                && ($dataA['modalidades'][0]['artilheiros'][0]['nome_usuario'] ?? '') === 'Atleta A'
                && !str_contains((string) $reportA['body'], 'Atleta B'),
            );

            $reportB = $admin->get('api/v1/relatorio-final?id_interclasse=' . $editionBId);
            $dataB = $reportB['json']['relatorio'] ?? [];
            Assertions::assert(
                'Modalidade sem artilharia não apresenta ranking individual',
                $reportB['code'] === 200
                && ($dataB['modalidades'][0]['contabiliza_artilharia'] ?? true) === false
                && ($dataB['modalidades'][0]['artilheiros'] ?? null) === [],
            );

            Assertions::assertStatus('ID ausente é rejeitado', $admin->get('api/v1/relatorio-final'), 400);
            Assertions::assertStatus('Edição inexistente não expõe relatório', $admin->get('api/v1/relatorio-final?id_interclasse=999999'), 404);

            $pdf = $admin->get('api/v1/relatorio-final/pdf?id_interclasse=' . $editionAId);
            Assertions::assert(
                'Exportação retorna PDF válido para impressão',
                $pdf['code'] === 200
                && str_starts_with((string) $pdf['body'], '%PDF-')
                && str_contains((string) ($pdf['headers']['Content-Type'] ?? ''), 'application/pdf')
                && str_contains((string) ($pdf['headers']['Content-Disposition'] ?? ''), 'relatorio-interclasse-' . $editionAId . '.pdf'),
            );

            $collaborator = new TestClient();
            $collaborator->login('colab', '123');
            Assertions::assertStatus('Colaborador pode consultar o relatório', $collaborator->get('api/v1/relatorio-final?id_interclasse=' . $editionAId), 200);
            $mesario = new TestClient();
            $mesario->login('mesario', '123');
            Assertions::assertStatus('Mesário não pode consultar o relatório administrativo', $mesario->get('api/v1/relatorio-final?id_interclasse=' . $editionAId), 403);
            $reportPage = $admin->get('relatorios?id=' . $editionAId);
            Assertions::assert(
                'Página apresenta ação de exportação e mantém edição selecionada',
                $reportPage['code'] === 200
                && str_contains((string) $reportPage['body'], 'Gerar Relatório')
                && str_contains((string) $reportPage['body'], 'relatorio-final.js'),
            );
            $deniedPage = $mesario->getWithoutRedirects('relatorios?id=' . $editionAId);
            Assertions::assertStatus('A página não abre para o mesário', $deniedPage, 302);
        } finally {
            if ($fixture !== null) {
                $editionIds = array_map('intval', $fixture['ids']['interclasses']);
                $classIds = array_map('intval', $fixture['ids']['turmas']);
                self::deleteByIds($connection, 'ocorrencias_turmas', 'turmas_id_turma', $classIds);
                self::deleteByIds($connection, 'pontuacoes_podio', 'id_interclasse', $editionIds);
                $connection->close();
                AuditFixtures::restoreAndRemove(TestDatabase::connect($databaseName), $fixture, $previousActiveId);
            } else {
                $connection->close();
            }
        }
    }

    private static function prepareReportRows(\mysqli $connection, int $editionAId, int $editionBId, int $classA, int $classB, int $classOtherEdition, int $modalityA, int $modalityB, int $userA, int $userB, int $gameA, int $gameB): void
    {
        $scores = $connection->prepare('UPDATE turmas SET qtd_itens_arrecadados = ?, pontuacao_turma = ? WHERE id_turma = ? AND interclasses_id_interclasse = ?');
        $quantityA = '2.50';
        $pointsA = 50;
        $scores->bind_param('siii', $quantityA, $pointsA, $classA, $editionAId);
        $scores->execute();
        $quantityB = '1.00';
        $pointsB = 25;
        $scores->bind_param('siii', $quantityB, $pointsB, $classB, $editionAId);
        $scores->execute();
        $otherQuantity = '90.00';
        $otherPoints = 900;
        $scores->bind_param('siii', $otherQuantity, $otherPoints, $classOtherEdition, $editionBId);
        $scores->execute();
        $scores->close();

        $penalty = $connection->prepare("INSERT INTO ocorrencias_turmas (turmas_id_turma, interclasses_id_interclasse, titulo_ocorrencia, descricao_ocorrencia, pontos_descontados, data_ocorrencia) VALUES (?, ?, 'Atraso', 'Registro da edição A', 3, '2026-09-10')");
        $penalty->bind_param('ii', $classA, $editionAId);
        $penalty->execute();
        $penalty->close();

        $credits = $connection->prepare("INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, id_equipe, id_usuario, id_jogo, pontos, ativo, origem_registro) VALUES (?, ?, 1, ?, NULL, ?, ?, 10, 1, 'manual')");
        $credits->bind_param('iiiii', $editionAId, $modalityA, $classA, $userA, $gameA);
        $credits->execute();
        $credits->bind_param('iiiii', $editionBId, $modalityB, $classOtherEdition, $userB, $gameB);
        $credits->execute();
        $credits->close();

        $disable = $connection->prepare('UPDATE modalidades SET contabiliza_artilharia = 0 WHERE id_modalidade = ? AND interclasses_id_interclasse = ?');
        $disable->bind_param('ii', $modalityB, $editionBId);
        $disable->execute();
        $disable->close();

        $rename = $connection->prepare('UPDATE usuarios SET nome_usuario = ? WHERE id_usuario = ? AND interclasses_id_interclasse = ?');
        $athleteAName = 'Atleta A';
        $rename->bind_param('sii', $athleteAName, $userA, $editionAId);
        $rename->execute();
        $athleteBName = 'Atleta B';
        $rename->bind_param('sii', $athleteBName, $userB, $editionBId);
        $rename->execute();
        $rename->close();

        $scorer = $connection->prepare("INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, equipes_id_equipe, num_gol, conta_no_placar, status_artilheiro) VALUES (?, ?, NULL, 2, 1, 'ativo')");
        $scorer->bind_param('ii', $userA, $gameA);
        $scorer->execute();
        $scorer->bind_param('ii', $userB, $gameB);
        $scorer->execute();
        $scorer->close();
    }

    /** @param list<int> $ids */
    private static function deleteByIds(\mysqli $connection, string $table, string $column, array $ids): void
    {
        $allowed = [
            'ocorrencias_turmas' => 'turmas_id_turma',
            'pontuacoes_podio' => 'id_interclasse',
        ];
        if (($allowed[$table] ?? null) !== $column || $ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $connection->prepare("DELETE FROM `{$table}` WHERE `{$column}` IN ({$placeholders})");
        $types = str_repeat('i', count($ids));
        $statement->bind_param($types, ...$ids);
        $statement->execute();
        $statement->close();
    }
}
