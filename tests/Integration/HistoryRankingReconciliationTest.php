<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Resultados\Infrastructure\MysqliHistoricoTurmaRepository;
use App\Modules\Resultados\Infrastructure\MysqliRankingRepository;
use App\Shared\Database\Transaction;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

final class HistoryRankingReconciliationTest
{
    public static function run(int $editionId, int $fallbackClassId): void
    {
        echo "\n  \033[1;34m[Suite 2.5: Histórico, ranking e ajustes]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $source = self::source($connection, $editionId);
        $classId = $source !== null ? (int) $source['id_turma'] : $fallbackClassId;
        if ($source === null || $classId <= 0) {
            throw new \RuntimeException('O cenário T17 não encontrou uma fonte de pódio ativa.');
        }

        Transaction::begin($connection);
        try {
            $sourceId = (int) $source['id_pontuacao'];
            self::exec($connection, 'UPDATE pontuacoes_podio SET ativo = 0 WHERE id_interclasse = ? AND id_turma = ?', 'ii', [$editionId, $classId]);
            self::exec($connection, 'UPDATE pontuacoes_podio SET ativo = 1, pontos = 10 WHERE id_pontuacao = ?', 'i', [$sourceId]);
            self::exec($connection, "UPDATE historico_arrecadacoes SET status_historico = '0' WHERE id_interclasse = ? AND id_turma = ?", 'ii', [$editionId, $classId]);
            self::exec($connection, 'UPDATE interclasses SET valor_item_arrecadacao = 3 WHERE id_interclasse = ?', 'i', [$editionId]);
            self::exec($connection, 'UPDATE turmas SET qtd_itens_arrecadados = 10, pontuacao_turma = 45 WHERE id_turma = ? AND interclasses_id_interclasse = ?', 'ii', [$classId, $editionId]);
            self::exec($connection, 'DELETE FROM ocorrencias_turmas WHERE turmas_id_turma = ? AND interclasses_id_interclasse = ?', 'ii', [$classId, $editionId]);
            self::exec($connection, "UPDATE ocorrencias o INNER JOIN usuarios u ON u.id_usuario = o.usuarios_id_usuario SET o.status_ocorrencia = '0' WHERE u.turmas_id_turma = ? AND u.interclasses_id_interclasse = ?", 'ii', [$classId, $editionId]);
            self::exec(
                $connection,
                'INSERT INTO historico_arrecadacoes (id_turma, id_interclasse, quantidade, pontos_adicionados, registrado_por, status_historico) VALUES (?, ?, 10, 30, NULL, \'1\')',
                'ii',
                [$classId, $editionId],
            );
            self::exec(
                $connection,
                'INSERT INTO ocorrencias_turmas (turmas_id_turma, interclasses_id_interclasse, titulo_ocorrencia, descricao_ocorrencia, pontos_descontados, data_ocorrencia, usuarios_id_usuario) VALUES (?, ?, \'T17\', \'Ajuste sintético\', 3, CURRENT_DATE(), NULL)',
                'ii',
                [$classId, $editionId],
            );

            $history = (new MysqliHistoricoTurmaRepository($connection))->find($classId, $editionId);
            Assertions::assert('Histórico usa D(Q,V) atual para arrecadação', ($history['arrecadacao']['pontos'] ?? null) === 30);
            Assertions::assert('Histórico lê crédito esportivo da fonte de pódio', ($history['esportes']['pontos_total'] ?? null) === 10 && count($history['esportes']['modalidades'] ?? []) === 1);
            Assertions::assert('Histórico expõe modalidade e posição do crédito', ($history['esportes']['modalidades'][0]['fontes'][0]['posicao'] ?? null) === (int) $source['posicao']);
            Assertions::assert('Histórico mantém ajuste residual J=5', ($history['resumo']['ajuste_pontos'] ?? null) === 5);
            Assertions::assert('Histórico calcula líquido 42 sem somar penalidade ao esporte', ($history['resumo']['liquido'] ?? null) === 42 && ($history['turma']['pontuacao_liquida'] ?? null) === 42);

            $ranking = (new MysqliRankingRepository($connection))->list(['id_interclasse' => $editionId, 'id_turma' => $classId]);
            $row = $ranking[0] ?? [];
            Assertions::assert('Ranking separa bruto, esporte, ajuste e líquido', (int) ($row['pontuacao_bruta'] ?? -1) === 45 && (int) ($row['pontuacao_esportes'] ?? -1) === 10 && (int) ($row['ajuste_pontuacao'] ?? -1) === 5 && (int) ($row['pontuacao_liquida'] ?? -1) === 42);
        } finally {
            Transaction::rollback($connection);
            $connection->close();
        }
    }

    /** @return array<string, mixed>|null */
    private static function source(\mysqli $connection, int $editionId): ?array
    {
        $statement = $connection->prepare('SELECT id_pontuacao, id_turma, posicao FROM pontuacoes_podio WHERE id_interclasse = ? AND ativo = 1 ORDER BY id_pontuacao LIMIT 1');
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $source = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $source;
    }

    /** @param list<int> $params */
    private static function exec(\mysqli $connection, string $sql, string $types, array $params): void
    {
        $statement = $connection->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException($connection->error);
        }
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException($error);
        }
        $statement->close();
    }
}
