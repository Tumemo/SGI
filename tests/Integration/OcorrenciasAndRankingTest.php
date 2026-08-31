<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class OcorrenciasAndRankingTest
{
    public static function run(int $idEdicao, int $idTurma): void
    {
        echo "\n  \033[1;34m[Suite 7: Ocorrências Disciplinares, Arrecadação e Ranking]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 7.1 Lançar ocorrência disciplinar individual (Cartão Amarelo)
        $resOcorrAluno = $admin->postJson('api/ocorrencias.php', [
            'titulo_ocorrencia' => 'Amarelo',
            'descricao_ocorrencia' => '[JOGO:1][TURMA:' . $idTurma . '] Falta tática no contra-ataque',
            'data_ocorrencia' => date('Y-m-d'),
            'hora_ocorrencia' => date('H:i:s'),
            'usuarios_id_usuario' => 1,
            'penalidade' => 0
        ]);
        Assertions::assert("Registro de ocorrência disciplinar individual (Amarelo)", in_array($resOcorrAluno['code'], [200, 201], true));

        // 7.2 Lançar ocorrência disciplinar na turma (-10 pontos)
        $resOcorrTurma = $admin->postJson('api/ocorrencias_turmas.php', [
            'turmas_id_turma' => $idTurma,
            'interclasses_id_interclasse' => $idEdicao,
            'titulo_ocorrencia' => 'Comportamento antidesportivo da torcida',
            'descricao_ocorrencia' => 'Uso de sinalizadores na arquibancada',
            'pontos_descontados' => 10,
            'data_ocorrencia' => date('Y-m-d')
        ]);
        Assertions::assert("Lançamento de ocorrência disciplinar na turma (-10 pontos)", ($resOcorrTurma['json']['success'] ?? false) === true);

        // 7.3 Lançamento de arrecadação de alimentos em lote (40 itens)
        $resArrec = $admin->postJson('api/arrecadacao.php', [
            'id_interclasse' => $idEdicao,
            'arrecadacoes' => [
                ['id_turma' => $idTurma, 'quantidade' => 40]
            ]
        ]);
        Assertions::assertJsonSuccess("Lançamento em lote de itens arrecadados (40 itens)", $resArrec);

        // 7.4 Consultar ranking geral consolidado
        $resRanking = $admin->get("api/ranking.php?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de ranking geral (HTTP 200)", $resRanking, 200);
        $ranking = $resRanking['json'] ?? [];
        Assertions::assert("Retorno de dados do ranking geral", is_array($ranking) && count($ranking) > 0);

        // Verificar turma no ranking
        $turmaNoRank = null;
        foreach ($ranking as $r) {
            if ((int)($r['id_turma'] ?? 0) === $idTurma) {
                $turmaNoRank = $r;
                break;
            }
        }
        Assertions::assert("Turma de teste presente no ranking", $turmaNoRank !== null);
    }
}
