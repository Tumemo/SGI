<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class HistoricoTurmaAndClassificacaoTest
{
    public static function run(int $idEdicao, int $idTurma, int $idModalidade): void
    {
        echo "\n  \033[1;34m[Suite 11: Histórico de Turma, Pódio e Tipos de Modalidade]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 11.1 Consultar histórico completo da turma
        $resHist = $admin->get("api/historico_turma.php?id_turma=$idTurma&id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de histórico da turma (HTTP 200)", $resHist, 200);
        Assertions::assert("Retorno de dados da turma no histórico", ($resHist['json']['success'] ?? false) === true);
        Assertions::assert("Estatísticas de pontuação presentes", isset($resHist['json']['resumo']) && isset($resHist['json']['turma']));

        // 11.2 Rejeição de histórico sem parâmetros obrigatórios
        $resHistSemParam = $admin->get("api/historico_turma.php");
        Assertions::assertStatus("Rejeição de histórico sem id_turma/id_interclasse (HTTP 400)", $resHistSemParam, 400);

        // 11.3 Consultar classificação/pódio da modalidade
        $resClassif = $admin->get("api/classificacao.php?id_modalidade=$idModalidade");
        Assertions::assertStatus("Consulta de classificação da modalidade (HTTP 200)", $resClassif, 200);
        Assertions::assert("Estrutura válida de classificação/pódio", isset($resClassif['json']['podio']) || isset($resClassif['json']['classificacao']));

        // 11.4 Consultar tipos de modalidade (Mata-Mata / Individual)
        $resTipos = $admin->get("api/tipoModalidade.php");
        Assertions::assertStatus("Consulta de tipos de modalidades (HTTP 200)", $resTipos, 200);
        Assertions::assert("Retorno de tipos de modalidades cadastrados", is_array($resTipos['json']) && count($resTipos['json']) > 0);
    }
}
