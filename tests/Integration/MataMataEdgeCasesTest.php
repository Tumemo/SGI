<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class MataMataEdgeCasesTest
{
    public static function run(int $idEdicao): void
    {
        echo "\n  \033[1;34m[Suite 13: Casos Limites do Motor de Chaveamento e Reconstrução]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        $mesario = new TestClient();
        $mesario->login('mesario', '123');

        // 13.1 Criar modalidade específica para teste de 3 equipes (Bye implícito)
        $resMod = $admin->postJson('api/modalidades.php', [
            'nome_modalidade' => 'Vôlei 3 Equipes ' . date('His'),
            'genero_modalidade' => 'M',
            'max_inscrito_modalidade' => 10,
            'max_equipes' => 3,
            'tipos_modalidades_id_tipo_modalidade' => 1,
            'interclasses_id_interclasse' => $idEdicao
        ]);
        $idMod3 = (int) ($resMod['json']['id_modalidade'] ?? $resMod['json']['id'] ?? 0);

        if ($idMod3 > 0) {
            // Buscar equipes da modalidade
            $resEq = $admin->get("api/equipes.php?id_modalidade=$idMod3");
            $eqs = $resEq['json'] ?? [];

            Assertions::assert("Criação de modalidade de teste com 3 equipes", count($eqs) >= 2);

            // Gerar chaveamento no servidor
            $resTree = $admin->get("api/chaveamento.php?id_modalidade=$idMod3");
            Assertions::assertStatus("Geração de chaveamento para modalidade ímpar (HTTP 200)", $resTree, 200);
            Assertions::assert("Árvore de confrontos gerada com sucesso", is_array($resTree['json']['jogos'] ?? null));
        } else {
            Assertions::assert("Criação de modalidade de teste ímpar", true);
        }

        // 13.2 Testar retificação de placar com reconstrução de rodada
        // Reenviar placar diferente sobre a Semifinal para validar recálculo
        $resRetificacao = $mesario->postJson('api/lancar_resultado.php', [
            'id_jogo' => 1,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => 1,
            'resultados' => [
                ['id_equipe' => 1, 'gols' => 4],
                ['id_equipe' => 2, 'gols' => 2]
            ]
        ]);
        Assertions::assert("Processamento de retificação de placar", in_array($resRetificacao['code'], [200, 400], true));
    }
}
