<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class PontuacaoReconciliationTest
{
    public static function run(int $interclasseId, int $turmaId): void
    {
        echo "\n  \033[1;34m[Suite 2.1: Reconciliação de pontuação da arrecadação]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);

        $original = $connection->prepare(
            'SELECT i.valor_item_arrecadacao, t.qtd_itens_arrecadados, t.pontuacao_turma
             FROM interclasses i INNER JOIN turmas t ON t.interclasses_id_interclasse = i.id_interclasse
             WHERE i.id_interclasse = ? AND t.id_turma = ? LIMIT 1',
        );
        $original->bind_param('ii', $interclasseId, $turmaId);
        $original->execute();
        $originalRow = $original->get_result()->fetch_assoc();
        $original->close();
        $originalValue = (int) ($originalRow['valor_item_arrecadacao'] ?? 2);
        $originalQuantity = (string) ($originalRow['qtd_itens_arrecadados'] ?? '0');
        $originalPoints = (int) ($originalRow['pontuacao_turma'] ?? 0);

        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 2]);
        $statement = $connection->prepare(
            'UPDATE turmas SET qtd_itens_arrecadados = 10, pontuacao_turma = 30 WHERE id_turma = ? AND interclasses_id_interclasse = ?',
        );
        $statement->bind_param('ii', $turmaId, $interclasseId);
        $statement->execute();
        $statement->close();

        $alteracao = $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 3]);
        Assertions::assertJsonSuccess('Alterar valor do item de arrecadação', $alteracao);

        $check = $connection->prepare('SELECT pontuacao_turma FROM turmas WHERE id_turma = ? LIMIT 1');
        $check->bind_param('i', $turmaId);
        $check->execute();
        $points = (int) $check->get_result()->fetch_column();
        $check->close();
        Assertions::assert('Revalorização preserva pontos esportivos (30 + 10 x 3 - 10 x 2 = 40)', $points === 40, 'Pontos encontrados: ' . $points);

        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 2]);
        $points = self::points($connection, $turmaId);
        Assertions::assert('Voltar V3 para V2 restaura o bruto inicial em 30 pontos', $points === 30, 'Pontos encontrados: ' . $points);

        self::setTurma($connection, $turmaId, '10', 35);
        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 3]);
        Assertions::assert('Revalorização preserva ajuste J5 (35 - 20 + 30 = 45)', self::points($connection, $turmaId) === 45);
        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 2]);

        self::setTurma($connection, $turmaId, '0', 17);
        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 3]);
        Assertions::assert('Quantidade zero preserva pontos esportivos e ajustes', self::points($connection, $turmaId) === 17);
        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 2]);

        self::setTurma($connection, $turmaId, '1.25', 5);
        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => 3]);
        Assertions::assert('Quantidade fracionária usa arredondamento inteiro único', self::points($connection, $turmaId) === 6);

        $admin->postJson('api/interclasse.php?id=' . $interclasseId, ['valor_item_arrecadacao' => $originalValue]);
        self::setTurma($connection, $turmaId, $originalQuantity, $originalPoints);
        Assertions::assert('Reprodução restaura a linha da turma usada no cenário', self::points($connection, $turmaId) === $originalPoints);
        $connection->close();
    }

    private static function setTurma(\mysqli $connection, int $turmaId, string $quantity, int $points): void
    {
        $statement = $connection->prepare('UPDATE turmas SET qtd_itens_arrecadados = ?, pontuacao_turma = ? WHERE id_turma = ?');
        $statement->bind_param('sii', $quantity, $points, $turmaId);
        $statement->execute();
        $statement->close();
    }

    private static function points(\mysqli $connection, int $turmaId): int
    {
        $check = $connection->prepare('SELECT pontuacao_turma FROM turmas WHERE id_turma = ? LIMIT 1');
        $check->bind_param('i', $turmaId);
        $check->execute();
        $points = (int) $check->get_result()->fetch_column();
        $check->close();
        return $points;
    }
}
