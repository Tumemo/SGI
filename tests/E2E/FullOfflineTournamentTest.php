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
        $atletaE1 = self::firstAthleteId($admin, $e1);
        $atletaE3 = self::firstAthleteId($admin, $e3);

        // 9.1 Concluir Semifinal 2 (Equipe 3 vence Equipe 4 por 2x0)
        $partidasSf2 = $mesario->get('api/v1/partidas?id_jogo=' . $idJogo2);
        $partidaE3 = 0;
        foreach (($partidasSf2['json'] ?? []) as $partida) {
            if ((int) ($partida['equipes_id_equipe'] ?? 0) === $e3) {
                $partidaE3 = (int) ($partida['id_partida'] ?? 0);
                break;
            }
        }
        $rosterE3 = $mesario->get('api/v1/pontos?acao=atletas&id_jogo=' . $idJogo2 . '&id_equipe=' . $e3);
        Assertions::assert('Semifinal 2 expõe atleta ativo da equipe exata', ($rosterE3['code'] ?? 0) === 200 && $atletaE3 > 0);
        $startedSf2 = $mesario->putJson('api/v1/jogos', [
            'id_jogo' => $idJogo2,
            'status_jogo' => 'Iniciado',
            'duracao_jogo' => 1200,
            'tempo_restante_jogo' => 1200,
        ]);
        Assertions::assertJsonSuccess('Início da Semifinal 2 para registrar pontos', $startedSf2);
        for ($i = 1; $i <= 2; $i++) {
            Assertions::assertJsonSuccess('Ponto da Semifinal 2 vinculado ao atleta', $mesario->postJson('api/v1/pontos', [
                'jogos_id_jogo' => $idJogo2,
                'id_partida' => $partidaE3,
                'equipes_id_equipe' => $e3,
                'usuarios_id_usuario' => $atletaE3,
                'chave_jogada' => 'offline-sf2-point-' . $i . '-' . bin2hex(random_bytes(4)),
            ]));
        }
        $resSf2 = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo2,
            'nome_jogo' => 'MM:4:1:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e3, 'gols' => 2],
                ['id_equipe' => $e4, 'gols' => 0]
            ]
        ]);
        Assertions::assertJsonSuccess("Conclusão da Semifinal 2 (2x0)", $resSf2);

        // 9.2 Rotas antigas não podem criar placar ou artilharia isolados.
        $resPartidaLocal = $mesario->postJson('api/v1/partidas', [
            'id_partida' => 'mm_local_-1_0',
            'resultado_partida' => 4
        ]);
        Assertions::assertStatus('Mutação temporária de partida não cria pontuação isolada', $resPartidaLocal, 422);

        $resArtLocal = $mesario->postJson('api/v1/artilheiros', [
            'usuarios_id_usuario' => $atletaE1,
            'jogos_id_jogo' => -1,
            'id_modalidade' => $idModalidade,
            'num_gol' => 3,
        ]);
        Assertions::assertStatus('Artilharia temporária sem partida e jogada é rejeitada', $resArtLocal, 422);

        // 9.3 Concluir Grande Final jogada offline com ID Provisório Negativo (-1)
        // Equipe 1 vence Equipe 3 por 4x2 e torna-se Campeã
        $resFinal = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => -1,
            'nome_jogo' => 'MM:2:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 4],
                ['id_equipe' => $e3, 'gols' => 2]
            ],
            'pontos' => [
                ...self::offlinePoints($e1, $atletaE1, 4, 'offline-final-a'),
                ...self::offlinePoints($e3, $atletaE3, 2, 'offline-final-b'),
            ],
        ]);
        Assertions::assertJsonSuccess("Sincronização da Grande Final (ID -1) gerada offline", $resFinal);

        // 9.4 Verificar consolidação da árvore no servidor
        $resArvore = $admin->get("api/v1/chaveamentos?id_modalidade=$idModalidade");
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

    private static function firstAthleteId(TestClient $client, int $teamId): int
    {
        $response = $client->get('api/v1/equipes?id_equipe=' . $teamId);
        foreach (($response['json'] ?? []) as $member) {
            $id = (int) ($member['id_usuario'] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }
        return 0;
    }

    /** @return list<array<string, int|string>> */
    private static function offlinePoints(int $teamId, int $athleteId, int $quantity, string $prefix): array
    {
        $points = [];
        for ($index = 1; $index <= $quantity; $index++) {
            $points[] = [
                'id_equipe' => $teamId,
                'usuarios_id_usuario' => $athleteId,
                'chave_jogada' => $prefix . '-' . $index . '-' . bin2hex(random_bytes(3)),
                'status_artilheiro' => 'ativo',
            ];
        }
        return $points;
    }
}
