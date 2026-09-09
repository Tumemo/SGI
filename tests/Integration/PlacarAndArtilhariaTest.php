<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class PlacarAndArtilhariaTest
{
    public static function run(int $idJogo, int $idModalidade, array $equipesIds): void
    {
        echo "\n  \033[1;34m[Suite 6: Operação de Mesário, Placar e Artilharia]\033[0m\n";

        $mesario = new TestClient();
        $mesario->login('mesario', '123');

        $e1 = $equipesIds[0];
        $e2 = $equipesIds[1];

        // 6.1 Iniciar jogo (status 'Iniciado')
        $resStart = $mesario->putJson('api/v1/jogos', [
            'id_jogo' => $idJogo,
            'status_jogo' => 'Iniciado',
            'duracao_jogo' => 1200,
            'tempo_restante_jogo' => 1200,
            'tempo_extra_jogo' => 0
        ]);
        Assertions::assert("Mudança de status do jogo para 'Iniciado'", ($resStart['json']['success'] ?? false) === true);

        // 6.2 Pausar jogo
        $resPause = $mesario->putJson('api/v1/jogos', [
            'id_jogo' => $idJogo,
            'status_jogo' => 'Pausado',
            'duracao_jogo' => 1200,
            'tempo_restante_jogo' => 850
        ]);
        Assertions::assert("Mudança de status do jogo para 'Pausado'", ($resPause['json']['success'] ?? false) === true);

        // 6.3 Lançar gol de artilheiro (usuário ID 1)
        $resArt = $mesario->postJson('api/v1/artilheiros', [
            'usuarios_id_usuario' => 1,
            'jogos_id_jogo' => $idJogo,
            'num_gol' => 2
        ]);
        Assertions::assertJsonSuccess("Lançamento de artilharia individual", $resArt);

        $mutation = ['X-SGI-Mutation-Id' => 'regression-goal-' . bin2hex(random_bytes(8))];
        $goal = ['usuarios_id_usuario' => 1, 'jogos_id_jogo' => $idJogo, 'num_gol' => 1];
        $first = $mesario->postJson('api/v1/artilheiros', $goal, $mutation);
        $retry = $mesario->postJson('api/v1/artilheiros', $goal, $mutation);
        Assertions::assertJsonSuccess('Primeiro envio com identificador persistente', $first);
        Assertions::assert('Reenvio devolve exatamente o mesmo registro', $first['json'] === $retry['json']);
        $conflict = $mesario->postJson('api/v1/artilheiros', array_replace($goal, ['num_gol' => 4]), $mutation);
        Assertions::assertStatus('Identificador reutilizado com dados diferentes é rejeitado', $conflict, 409);
        $otherActor = new TestClient();
        $otherActor->login('admin', '123');
        Assertions::assertStatus('Identificador não pode ser reutilizado por outro operador', $otherActor->postJson('api/v1/artilheiros', $goal, $mutation), 409);

        // 6.4 Rejeição de finalização com placar 0x0
        $resZero = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 0],
                ['id_equipe' => $e2, 'gols' => 0]
            ]
        ]);
        Assertions::assert("Proibição de finalização de mata-mata com placar 0x0", ($resZero['json']['success'] ?? true) === false);

        // 6.5 Rejeição de finalização empatada (mata-mata não pode empatar)
        $resEmpate = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 2],
                ['id_equipe' => $e2, 'gols' => 2]
            ]
        ]);
        Assertions::assert("Proibição de empate na finalização de partida mata-mata", ($resEmpate['json']['success'] ?? true) === false);

        // 6.6 Finalização correta com placar definido (3x1)
        $resFin = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 3],
                ['id_equipe' => $e2, 'gols' => 1]
            ]
        ]);
        Assertions::assertJsonSuccess("Finalização da partida com placar válido (3x1)", $resFin);
    }
}
