<?php
declare(strict_types=1);

namespace SGITests\E2E;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class FullOfflineTournamentTest
{
    public static function run(int $idEdicao, int $idModalidade, int $idJogo2, array $equipesIds): void
    {
        echo "\n  \033[1;34m[Suite 9: Torneio Mata-Mata Completo e Sincronização Offline]\033[0m\n";

        $mesario = new TestClient();
        $mesario->login('mesario', '123');

        $admin = new TestClient();
        $admin->login('admin', '123');

        $e1 = $equipesIds[0];
        $e3 = $equipesIds[2];
        $e4 = $equipesIds[3];

        // 9.1 Concluir Semifinal 2 (Equipe 3 vence Equipe 4 por 2x0)
        $resSf2 = $mesario->postJson('api/lancar_resultado.php', [
            'id_jogo' => $idJogo2,
            'nome_jogo' => 'MM:4:1:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e3, 'gols' => 2],
                ['id_equipe' => $e4, 'gols' => 0]
            ]
        ]);
        Assertions::assertJsonSuccess("Conclusão da Semifinal 2 (2x0)", $resSf2);

        // 9.2 Simulação de mutações provisórias offline com ID negativo
        $resPartidaLocal = $mesario->postJson('api/partidas.php', [
            'id_partida' => 'mm_local_-1_0',
            'resultado_partida' => 4
        ]);
        Assertions::assert("Tratamento seguro de mutação temporária de partida", ($resPartidaLocal['json']['success'] ?? false) === true || $resPartidaLocal['code'] === 200);

        $payloadArtilhariaTemporaria = [
            'usuarios_id_usuario' => 1,
            'jogos_id_jogo' => -1,
            'nome_jogo' => 'MM:2:0:N',
            'id_modalidade' => $idModalidade,
            'num_gol' => 3
        ];
        $resArtLocal = $mesario->postJson('api/artilheiro.php', $payloadArtilhariaTemporaria);
        Assertions::assert(
            "Artilharia temporária é resolvida com segurança ou mantida pendente",
            ($resArtLocal['json']['success'] ?? false) === true ||
            ($resArtLocal['code'] === 409 && ($resArtLocal['json']['success'] ?? true) === false)
        );

        // 9.3 Concluir Grande Final jogada offline com ID Provisório Negativo (-1)
        // Equipe 1 vence Equipe 3 por 4x2 e torna-se Campeã
        $resFinal = $mesario->postJson('api/lancar_resultado.php', [
            'id_jogo' => -1,
            'nome_jogo' => 'MM:2:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 4],
                ['id_equipe' => $e3, 'gols' => 2]
            ]
        ]);
        Assertions::assertJsonSuccess("Sincronização da Grande Final (ID -1) gerada offline", $resFinal);

        // Se ainda não havia um jogo real no primeiro envio, a fila do
        // navegador envia o resultado antes da artilharia do mesmo ID
        // temporário. O segundo envio abaixo simula essa retomada.
        if (($resArtLocal['json']['success'] ?? false) !== true) {
            $resArtSincronizado = $mesario->postJson('api/artilheiro.php', $payloadArtilhariaTemporaria);
            Assertions::assertJsonSuccess("Artilharia vinculada à final materializada", $resArtSincronizado);
        }

        // 9.4 Verificar consolidação da árvore no servidor
        $resArvore = $admin->get("api/chaveamento.php?id_modalidade=$idModalidade");
        Assertions::assertStatus("Consulta de árvore no servidor (HTTP 200)", $resArvore, 200);
        $jogos = $resArvore['json']['jogos'] ?? [];

        $jogoFinal = null;
        foreach ($jogos as $j) {
            if (($j['nome_jogo'] ?? '') === 'MM:2:0:N') {
                $jogoFinal = $j;
                break;
            }
        }

        Assertions::assert("Confronto da Grande Final (MM:2:0:N) presente no MySQL", $jogoFinal !== null);
        Assertions::assert("Grande Final marcada como Concluído no MySQL", ($jogoFinal['status_jogo'] ?? '') === 'Concluido' || ($jogoFinal['status_jogo'] ?? '') === 'Finalizado');
        Assertions::assert("Campeão derivado diretamente da Grande Final", !empty($jogoFinal['equipe_vencedora_id']));

        $jogosSolo = array_filter($jogos, static fn($j) => preg_match('/^MM:1:/', (string) ($j['nome_jogo'] ?? '')));
        Assertions::assert("Nenhum jogo solo MM:1 criado automaticamente", count($jogosSolo) === 0);
    }
}
