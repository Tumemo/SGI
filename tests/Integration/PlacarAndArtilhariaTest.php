<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

class PlacarAndArtilhariaTest
{
    public static function run(int $idJogo, int $idModalidade, array $equipesIds, ?int $idInterclasse = null): void
    {
        echo "\n  \033[1;34m[Suite 6: Operação de Mesário, Placar e Artilharia]\033[0m\n";

        $mesario = new TestClient();
        $mesario->login('mesario', '123');

        $e1 = $equipesIds[0];
        $e2 = $equipesIds[1];
        $partidas = $mesario->get('api/v1/partidas?id_jogo=' . $idJogo);
        $partidaRows = is_array($partidas['json'] ?? null) ? $partidas['json'] : [];
        $partidaPorEquipe = [];
        foreach ($partidaRows as $partidaRow) {
            $partidaPorEquipe[(int) ($partidaRow['equipes_id_equipe'] ?? 0)] = (int) ($partidaRow['id_partida'] ?? 0);
        }
        $atletasEquipe1 = $mesario->get('api/v1/pontos?acao=atletas&id_jogo=' . $idJogo . '&id_equipe=' . $e1);
        $atletasEquipe2 = $mesario->get('api/v1/pontos?acao=atletas&id_jogo=' . $idJogo . '&id_equipe=' . $e2);
        $atleta1 = (int) (($atletasEquipe1['json']['atletas'][0]['id_usuario'] ?? 0));
        $atleta2 = (int) (($atletasEquipe2['json']['atletas'][0]['id_usuario'] ?? 0));

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

        // 6.3 O placar só aceita pontos vinculados à partida e ao atleta.
        $semAtleta = $mesario->postJson('api/v1/pontos', [
            'jogos_id_jogo' => $idJogo,
            'id_partida' => $partidaPorEquipe[$e1] ?? 0,
            'equipes_id_equipe' => $e1,
            'chave_jogada' => 'regressao-sem-atleta-' . bin2hex(random_bytes(4)),
        ]);
        Assertions::assertStatus('Ponto sem atleta é bloqueado antes de tocar o placar', $semAtleta, 422);

        $pontoBase = [
            'jogos_id_jogo' => $idJogo,
            'id_partida' => $partidaPorEquipe[$e1] ?? 0,
            'equipes_id_equipe' => $e1,
            'usuarios_id_usuario' => $atleta1,
            'chave_jogada' => 'regressao-ponto-' . bin2hex(random_bytes(5)),
            'registrado_por' => self::userId('admin'),
        ];
        $pointMutation = ['X-SGI-Mutation-Id' => 'regressao-ponto-' . bin2hex(random_bytes(6))];
        $resPonto = $mesario->postJson('api/v1/pontos', $pontoBase, $pointMutation);
        Assertions::assertJsonSuccess('Primeiro ponto incrementa o placar junto com o evento do atleta', $resPonto);
        Assertions::assert('O seletor de atletas é restrito à equipe da partida',
            ($atletasEquipe1['code'] ?? 0) === 200
            && ($atletasEquipe2['code'] ?? 0) === 200
            && $atleta1 > 0
            && $atleta2 > 0
            && count($atletasEquipe1['json']['atletas'] ?? []) === 1
            && (int) ($atletasEquipe1['json']['atletas'][0]['id_usuario'] ?? 0) === $atleta1,
        );
        $retryPonto = $mesario->postJson('api/v1/pontos', $pontoBase, $pointMutation);
        Assertions::assert('Reenvio do mesmo ponto não duplica placar nem histórico', $resPonto['json'] === $retryPonto['json']);
        $pontoConflitante = $mesario->postJson('api/v1/pontos', array_replace($pontoBase, ['usuarios_id_usuario' => $atleta2]), $pointMutation);
        Assertions::assertStatus('Chave de jogada reutilizada com outro atleta é rejeitada', $pontoConflitante, 409);

        $pontosEquipe1 = [$pontoBase];
        for ($i = 0; $i < 2; $i++) {
            $pontosEquipe1[] = array_replace($pontoBase, ['chave_jogada' => 'regressao-ponto-' . bin2hex(random_bytes(5))]);
            Assertions::assertJsonSuccess('Ponto adicional da mesma equipe é aceito', $mesario->postJson('api/v1/pontos', $pontosEquipe1[$i + 1]));
        }
        $pontoEquipe2 = array_replace($pontoBase, [
            'id_partida' => $partidaPorEquipe[$e2] ?? 0,
            'equipes_id_equipe' => $e2,
            'usuarios_id_usuario' => $atleta2,
            'chave_jogada' => 'regressao-ponto-' . bin2hex(random_bytes(5)),
        ]);
        Assertions::assertJsonSuccess('Ponto da equipe adversária é aceito com seu próprio atleta', $mesario->postJson('api/v1/pontos', $pontoEquipe2));

        $pontos = $mesario->get('api/v1/pontos?id_jogo=' . $idJogo);
        $pontoParaAnular = 0;
        foreach (($pontos['json']['pontos'] ?? []) as $ponto) {
            if (($ponto['chave_jogada'] ?? '') === ($pontoBase['chave_jogada'] ?? '')) {
                $pontoParaAnular = (int) ($ponto['id_artilheiro'] ?? 0);
                break;
            }
        }
        $anulador = new TestClient();
        $loginAnulador = $anulador->login('admin', '123');
        Assertions::assertJsonSuccess('Outro operador administrativo autenticado para anular o ponto', $loginAnulador);
        $cancelMutation = ['X-SGI-Mutation-Id' => 'regressao-anulacao-' . bin2hex(random_bytes(6))];
        $anulado = $anulador->putJson('api/v1/pontos', ['id_ponto' => $pontoParaAnular], $cancelMutation);
        $anuladoReplay = $anulador->putJson('api/v1/pontos', ['id_ponto' => $pontoParaAnular], $cancelMutation);
        $audit = self::pointAudit($pontoParaAnular);
        Assertions::assertJsonSuccess('Anulação retira o ponto do placar', $anulado);
        Assertions::assert('Anulação preserva o evento individual como anulado',
            (int) ($anulado['json']['placar'] ?? -1) === 2
            && ($anulado['json']['ponto']['status_artilheiro'] ?? '') === 'anulado'
            && (int) ($anulado['json']['ponto']['conta_no_placar'] ?? 1) === 0,
        );
        Assertions::assert(
            'Autor original é preservado e a anulação registra o operador atual',
            $audit !== null
            && $audit['registrado_por'] === self::userId('mesario')
            && $audit['anulado_por'] === self::userId('admin'),
            json_encode($audit, JSON_UNESCAPED_UNICODE),
        );
        Assertions::assert(
            'Replay da anulação devolve a resposta original sem descontar o placar duas vezes',
            $anuladoReplay['json'] === $anulado['json']
            && (int) ($anuladoReplay['json']['placar'] ?? -1) === 2,
        );
        Assertions::assertStatus('Endpoint antigo de artilharia não cria pontuação isolada', $mesario->postJson('api/v1/artilheiros', [
            'usuarios_id_usuario' => $atleta1,
            'jogos_id_jogo' => $idJogo,
            'num_gol' => 1,
        ]), 422);

        $resReposicao = $mesario->postJson('api/v1/pontos', array_replace($pontoBase, ['chave_jogada' => 'regressao-ponto-' . bin2hex(random_bytes(5))]));
        Assertions::assertJsonSuccess('Novo ponto pode repor uma jogada anulada sem apagar o histórico', $resReposicao);

        $artilhariaJogo = $mesario->get('api/v1/artilheiros?id_jogo=' . $idJogo);
        $historicoAtleta = null;
        foreach (($artilhariaJogo['json'] ?? []) as $linha) {
            if ((int) ($linha['id_usuario'] ?? 0) === $atleta1) {
                $historicoAtleta = $linha;
                break;
            }
        }
        Assertions::assert('Relatório mantém a ação anulada e separa o total que vale no placar',
            is_array($historicoAtleta)
            && (int) ($historicoAtleta['total_gols'] ?? -1) === 3
            && (int) ($historicoAtleta['total_acoes'] ?? 0) === 4
            && (int) ($historicoAtleta['total_anulados'] ?? 0) === 1,
            json_encode(['response' => $artilhariaJogo, 'atleta' => $historicoAtleta], JSON_UNESCAPED_UNICODE),
        );

        $resDestaques = $mesario->get('api/v1/artilheiros?acao=destaques_modalidades');
        Assertions::assert(
            'Consulta de alunos destaques por modalidade',
            ($resDestaques['code'] ?? 0) === 200
                && ($resDestaques['json']['success'] ?? false) === true
                && is_array($resDestaques['json']['data'] ?? null),
            'Resposta: ' . mb_substr((string) ($resDestaques['body'] ?? ''), 0, 200),
        );

        if ($idInterclasse !== null && $idInterclasse > 0) {
            $admin = new TestClient();
            $loginAdmin = $admin->login('admin', '123');
            Assertions::assertJsonSuccess('Administrador autenticado para consultar destaques da edição', $loginAdmin);
            $resDestaquesEdicao = $admin->get('api/v1/artilheiros?acao=destaques_modalidades&id_interclasse=' . $idInterclasse);
            Assertions::assert(
                'Consulta de alunos destaques por modalidade com edição explícita',
                ($resDestaquesEdicao['code'] ?? 0) === 200
                    && ($resDestaquesEdicao['json']['success'] ?? false) === true
                    && is_array($resDestaquesEdicao['json']['data'] ?? null),
                'Resposta: ' . mb_substr((string) ($resDestaquesEdicao['body'] ?? ''), 0, 200),
            );
        }

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

        $scoresBeforeInvalidResults = self::scoresForGame($idJogo);
        $pointsBeforeInvalidResults = self::pointCountForGame($idJogo);
        Assertions::assertStatus('Placar em string decimal é rejeitado pelo endpoint', $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => '3.9'],
                ['id_equipe' => $e2, 'gols' => 1],
            ],
        ]), 422);
        Assertions::assert('Entrada decimal não altera placar nem partidas', self::scoresForGame($idJogo) === $scoresBeforeInvalidResults);

        Assertions::assertStatus('Linha escalar em resultados rejeita o lote completo', $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 3],
                ['id_equipe' => $e2, 'gols' => 1],
                'not-a-result-row',
            ],
        ]), 422);
        Assertions::assert('Linha escalar não altera placar nem pontos', self::scoresForGame($idJogo) === $scoresBeforeInvalidResults
            && self::pointCountForGame($idJogo) === $pointsBeforeInvalidResults);

        Assertions::assertStatus('Evento escalar em pontos rejeita o lote completo', $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 3],
                ['id_equipe' => $e2, 'gols' => 1],
            ],
            'pontos' => ['not-an-offline-event'],
        ]), 422);
        Assertions::assert('Evento escalar não altera placar nem pontos', self::scoresForGame($idJogo) === $scoresBeforeInvalidResults
            && self::pointCountForGame($idJogo) === $pointsBeforeInvalidResults);

        Assertions::assertStatus('Tipo escalar na raiz de pontos retorna 422', $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 3],
                ['id_equipe' => $e2, 'gols' => 1],
            ],
            'pontos' => 'not-a-list',
        ]), 422);
        Assertions::assert('Tipo escalar de pontos não altera placar nem eventos', self::scoresForGame($idJogo) === $scoresBeforeInvalidResults
            && self::pointCountForGame($idJogo) === $pointsBeforeInvalidResults);

        $rollbackKey = 'n08-atomic-rollback-' . bin2hex(random_bytes(5));
        $rollback = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => 5],
                ['id_equipe' => $e2, 'gols' => 1],
            ],
            'pontos' => [[
                'id_equipe' => $e1,
                'usuarios_id_usuario' => $atleta1,
                'chave_jogada' => $rollbackKey,
            ]],
        ]);
        Assertions::assertStatus('Inconsistência entre eventos e placar retorna 4xx', $rollback, 422);
        Assertions::assert('Rejeição de evento offline desfaz ponto e placar da transação',
            self::scoresForGame($idJogo) === $scoresBeforeInvalidResults
            && self::pointCountForGame($idJogo) === $pointsBeforeInvalidResults,
        );

        $integerStrings = $mesario->postJson('api/v1/resultados', [
            'id_jogo' => $idJogo,
            'nome_jogo' => 'MM:4:0:N',
            'id_modalidade' => $idModalidade,
            'resultados' => [
                ['id_equipe' => $e1, 'gols' => '03'],
                ['id_equipe' => $e2, 'gols' => '1'],
            ],
        ]);
        Assertions::assertJsonSuccess('Strings de placar inteiro continuam compatíveis com o formulário', $integerStrings);
    }

    private static function userId(string $registration): int
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $statement = $connection->prepare('SELECT id_usuario FROM usuarios WHERE matricula_usuario = ? LIMIT 1');
        $statement->bind_param('s', $registration);
        $statement->execute();
        $id = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $connection->close();
        return $id;
    }

    /** @return array{registrado_por:?int,anulado_por:?int}|null */
    private static function pointAudit(int $pointId): ?array
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $statement = $connection->prepare('SELECT registrado_por, anulado_por FROM artilheiros WHERE id_artilheiro = ? LIMIT 1');
        $statement->bind_param('i', $pointId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        $connection->close();
        if ($row === null) {
            return null;
        }
        return [
            'registrado_por' => $row['registrado_por'] === null ? null : (int) $row['registrado_por'],
            'anulado_por' => $row['anulado_por'] === null ? null : (int) $row['anulado_por'],
        ];
    }

    /** @return array<int, int> */
    private static function scoresForGame(int $gameId): array
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $statement = $connection->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ? ORDER BY equipes_id_equipe');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $scores = [];
        foreach ($statement->get_result()->fetch_all(\MYSQLI_ASSOC) as $row) {
            $scores[(int) $row['equipes_id_equipe']] = (int) $row['resultado_partida'];
        }
        $statement->close();
        $connection->close();
        return $scores;
    }

    private static function pointCountForGame(int $gameId): int
    {
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        $connection = TestDatabase::connect($database);
        $statement = $connection->prepare('SELECT COUNT(*) FROM artilheiros WHERE jogos_id_jogo = ?');
        $statement->bind_param('i', $gameId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $connection->close();
        return $count;
    }

}
