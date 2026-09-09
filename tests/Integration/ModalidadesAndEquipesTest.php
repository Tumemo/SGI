<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class ModalidadesAndEquipesTest
{
    public static function run(int $idEdicao): array
    {
        echo "\n  \033[1;34m[Suite 4: Categorias, Modalidades e Equipes]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 4.1 Categorias
        $resCat = $admin->get("api/v1/categorias?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de categorias (HTTP 200)", $resCat, 200);
        $cats = $resCat['json'] ?? [];
        Assertions::assert("Criação de 2 categorias escolares (I e II)", count($cats) === 2);

        // 4.2 Modalidades
        $resMod = $admin->get("api/v1/modalidades?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de modalidades (HTTP 200)", $resMod, 200);
        $mods = $resMod['json'] ?? [];
        Assertions::assert("Total de modalidades padrão (esperado >= 10)", count($mods) >= 10, "Total: " . count($mods));

        // 4.3 Encontrar modalidade com 4 equipes para confrontos
        $modEscolhida = null;
        $equipesEscolhidas = [];
        foreach ($mods as $m) {
            $idM = (int) $m['id_modalidade'];
            $resEq = $admin->get("api/v1/equipes?id_modalidade=$idM");
            $eqs = $resEq['json'] ?? [];
            if (count($eqs) >= 4) {
                $modEscolhida = $m;
                $equipesEscolhidas = $eqs;
                break;
            }
        }

        Assertions::assert("Localização de modalidade mata-mata com pelo menos 4 equipes", $modEscolhida !== null && count($equipesEscolhidas) >= 4);

        // 4.4 Verificar vínculo turma x modalidade nas equipes
        $eq1 = $equipesEscolhidas[0] ?? [];
        Assertions::assert("Equipe possui id_turma vinculado", !empty($eq1['turmas_id_turma']));
        Assertions::assert("Equipe possui nome de turma atribuído", !empty($eq1['nome_turma']));

        $generated = $admin->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]);
        Assertions::assertJsonSuccess('Geração de equipes pela rota versionada', $generated);
        $beforeRepeat = $admin->get("api/v1/equipes?id_interclasse=$idEdicao");
        $repeated = $admin->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]);
        $afterRepeat = $admin->get("api/v1/equipes?id_interclasse=$idEdicao");
        Assertions::assert('Repetir geração pela URL antiga não duplica equipes', ($repeated['json']['success'] ?? false) && $beforeRepeat['json'] === $afterRepeat['json']);
        $anonymous = new TestClient();
        Assertions::assertStatus('Geração de equipes exige autenticação', $anonymous->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]), 401);

        return [
            'modalidade' => $modEscolhida,
            'equipes' => $equipesEscolhidas
        ];
    }
}
