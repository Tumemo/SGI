<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class JogosAndConflitosTest
{
    public static function run(int $idEdicao, int $idModalidade, array $equipes): array
    {
        echo "\n  \033[1;34m[Suite 5: Locais, Agendamento e Detecção de Conflitos]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 5.1 Criar Local de Jogo
        $resLocal = $admin->postJson('api/v1/locais', [
            'nome_local' => 'Ginásio Poliesportivo A',
            'disponivel_local' => '1',
            'carga_local' => 6,
            'interclasses_id_interclasse' => $idEdicao
        ]);
        Assertions::assert("Cadastro de local de jogo (Ginásio)", in_array($resLocal['code'], [200, 201], true) && ($resLocal['json']['success'] ?? false) === true);
        $idLocal = (int) ($resLocal['json']['id_local'] ?? $resLocal['json']['id'] ?? 1);

        // 5.2 Agendar Semifinal 1 (MM:4:0:N)
        $hoje = date('Y-m-d');
        $e1 = (int) $equipes[0]['id_equipe'];
        $e2 = (int) $equipes[1]['id_equipe'];
        $e3 = (int) $equipes[2]['id_equipe'];
        $e4 = (int) $equipes[3]['id_equipe'];

        $resSf1 = $admin->postJson('api/v1/jogos', [
            'nome_jogo' => 'MM:4:0:N',
            'data_jogo' => $hoje,
            'inicio_jogo' => '08:00',
            'termino_jogo' => '08:40',
            'status_jogo' => 'Agendado',
            'duracao_jogo' => 1200,
            'modalidades_id_modalidade' => $idModalidade,
            'locais_id_local' => $idLocal,
            'equipes' => [['id_equipe' => $e1], ['id_equipe' => $e2]]
        ]);
        Assertions::assert("Agendamento de partida (MM:4:0:N) com 2 equipes", in_array($resSf1['code'], [200, 201], true) && ($resSf1['json']['success'] ?? false) === true);
        $idJogo1 = (int) ($resSf1['json']['id_jogo'] ?? $resSf1['json']['id'] ?? 0);

        // 5.3 Testar detecção de conflito de horário no mesmo local (sobreposição às 08:15)
        $resConflito = $admin->postJson('api/v1/jogos', [
            'nome_jogo' => 'MM:4:1:N',
            'data_jogo' => $hoje,
            'inicio_jogo' => '08:15',
            'termino_jogo' => '08:50',
            'status_jogo' => 'Agendado',
            'modalidades_id_modalidade' => $idModalidade,
            'locais_id_local' => $idLocal,
            'equipes' => [['id_equipe' => $e3], ['id_equipe' => $e4]]
        ]);
        Assertions::assert("Bloqueio de conflito de horário no mesmo local", $resConflito['code'] === 400 || ($resConflito['json']['success'] ?? true) === false);

        // 5.4 Agendar Semifinal 2 em horário válido (MM:4:1:N) às 09:00
        $resSf2 = $admin->postJson('api/v1/jogos', [
            'nome_jogo' => 'MM:4:1:N',
            'data_jogo' => $hoje,
            'inicio_jogo' => '09:00',
            'termino_jogo' => '09:40',
            'status_jogo' => 'Agendado',
            'duracao_jogo' => 1200,
            'modalidades_id_modalidade' => $idModalidade,
            'locais_id_local' => $idLocal,
            'equipes' => [['id_equipe' => $e3], ['id_equipe' => $e4]]
        ]);
        Assertions::assert("Agendamento da Semifinal 2 (MM:4:1:N) em horário livre", in_array($resSf2['code'], [200, 201], true) && ($resSf2['json']['success'] ?? false) === true);
        $idJogo2 = (int) ($resSf2['json']['id_jogo'] ?? $resSf2['json']['id'] ?? 0);

        return [
            'id_local' => $idLocal,
            'id_jogo_1' => $idJogo1,
            'id_jogo_2' => $idJogo2,
            'equipes_ids' => [$e1, $e2, $e3, $e4]
        ];
    }
}
