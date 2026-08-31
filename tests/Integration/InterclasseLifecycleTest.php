<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class InterclasseLifecycleTest
{
    public static function run(): int
    {
        echo "\n  \033[1;34m[Suite 2: Ciclo de Vida da Edição de Interclasse]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 2.1 Criar nova edição
        $nome = "Edicao Suite Test " . date('Ymd_His');
        $res = $admin->postJson('api/interclasse.php', [
            'nome_interclasse' => $nome,
            'ano_interclasse' => date('Y-m-d')
        ]);
        Assertions::assertJsonSuccess("Criação atômica de nova edição de Interclasse", $res);
        $idEdicao = (int) ($res['json']['id'] ?? 0);
        Assertions::assert("ID gerado válido para a nova edição", $idEdicao > 0);

        // 2.2 Garantia de 35 equipes padrão criadas
        $eqCount = (int) ($res['json']['equipes_padrao_garantidas'] ?? 0);
        Assertions::assert("Criação automática de no mínimo 35 equipes padrão", $eqCount >= 35, "Criadas: $eqCount");

        // 2.3 Listagem de edições
        $resList = $admin->get('api/interclasse.php?regulamento=true');
        Assertions::assertStatus("Listagem de edições (HTTP 200)", $resList, 200);
        Assertions::assert("Retorno de lista não vazia de edições", is_array($resList['json']) && count($resList['json']) > 0);

        // 2.4 Atualização de Pontuações de Pódio e Arrecadação
        $resConfig = $admin->postJson("api/interclasse.php?id=$idEdicao", [
            'ponto_1_lugar' => 20,
            'ponto_2_lugar' => 12,
            'ponto_3_lugar' => 8,
            'valor_item_arrecadacao' => 4
        ]);
        Assertions::assertJsonSuccess("Configuração de pontuações de pódio e arrecadação", $resConfig);

        // 2.5 Consultar edição criada e verificar persistência dos pontos
        $resCheck = $admin->get("api/interclasse.php?id=$idEdicao");
        $dados = $resCheck['json'] ?? [];
        if (isset($dados[0])) $dados = $dados[0];
        Assertions::assert("Persistência do valor do 1º lugar (20 pontos)", (int)($dados['ponto_1_lugar'] ?? 0) === 20);
        Assertions::assert("Persistência do valor por item de arrecadação (4 pontos)", (int)($dados['valor_item_arrecadacao'] ?? 0) === 4);

        return $idEdicao;
    }
}
