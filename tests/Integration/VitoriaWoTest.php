<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class VitoriaWoTest
{
    public static function run(int $editionId, int $classId): void
    {
        echo "\n  [Vitória por W.O. e pódio vazio]\n";
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $modalityId = 0;
        $gameId = 0;
        $byeGameId = 0;
        $finalId = 0;
        $teams = [];
        $before = (int) $connection->query('SELECT pontuacao_turma FROM turmas WHERE id_turma = ' . $classId)->fetch_column();
        try {
            $category = (int) $connection->query('SELECT categorias_id_categoria FROM turmas WHERE id_turma = ' . $classId)->fetch_column();
            $type = (int) $connection->query("SELECT id_tipo_modalidade FROM tipos_modalidades WHERE LOWER(nome_tipo_modalidade) LIKE '%mata%' LIMIT 1")->fetch_column();
            if ($type <= 0) {
                throw new \RuntimeException('Tipo mata-mata não encontrado no fixture.');
            }
            $modality = $connection->prepare("INSERT INTO modalidades (nome_modalidade, genero_modalidade, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse) VALUES ('Regressão W.O.', 'MISTO', '1', ?, ?, ?)");
            $modality->bind_param('iii', $type, $category, $editionId);
            $modality->execute();
            $modalityId = (int) $connection->insert_id;
            $modality->close();
            $team = $connection->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)");
            foreach (['Vencedora W.O.', 'Desclassificada na semifinal', 'Finalista pelo bye'] as $name) {
                $team->bind_param('iis', $modalityId, $classId, $name);
                $team->execute();
                $teams[] = (int) $connection->insert_id;
            }
            $team->close();
            $game = $connection->prepare("INSERT INTO jogos (nome_jogo, status_jogo, modalidades_id_modalidade) VALUES ('MM:4:0:N', 'Agendado', ?)");
            $game->bind_param('i', $modalityId);
            $game->execute();
            $gameId = (int) $connection->insert_id;
            $game->close();
            $part = $connection->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
            foreach (array_slice($teams, 0, 2) as $teamId) {
                $part->bind_param('ii', $gameId, $teamId);
                $part->execute();
            }
            $part->close();
            $bye = $connection->prepare("INSERT INTO jogos (nome_jogo, status_jogo, modalidades_id_modalidade) VALUES ('MM:4:1:B', 'Concluido', ?)");
            $bye->bind_param('i', $modalityId);
            $bye->execute();
            $byeGameId = (int) $connection->insert_id;
            $bye->close();
            $byePart = $connection->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
            $byePart->bind_param('ii', $byeGameId, $teams[2]);
            $byePart->execute();
            $byePart->close();

            $mesario = new TestClient();
            $mesario->login('mesario', '123');
            $payload = ['id_jogo' => $gameId, 'id_modalidade' => $modalityId,
                'resultados' => [['id_equipe' => $teams[0], 'gols' => 0], ['id_equipe' => $teams[1], 'gols' => 0]],
                'vencedor_wo_id' => $teams[0]];
            Assertions::assertStatus('W.O. exige sessão autorizada', (new TestClient())->postJson('api/v1/resultados', $payload), 401);
            Assertions::assertStatus('W.O. recusa vencedor alheio ao jogo', $mesario->postJson('api/v1/resultados', array_replace($payload, ['vencedor_wo_id' => 999999])), 422);
            Assertions::assertStatus('W.O. recusa modalidade divergente', $mesario->postJson('api/v1/resultados', array_replace($payload, ['id_modalidade' => 999999])), 422);
            Assertions::assertStatus('W.O. recusa pontos offline', $mesario->postJson('api/v1/resultados', array_replace($payload, ['pontos' => [['id_equipe' => $teams[0]]]])), 422);
            Assertions::assertJsonSuccess('Mesário conclui a semifinal por W.O.', $mesario->postJson('api/v1/resultados', $payload));
            Assertions::assertStatus('Reenvio não duplica o avanço', $mesario->postJson('api/v1/resultados', $payload), 422);
            $final = $connection->query("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = $modalityId AND nome_jogo = 'MM:2:0:N' LIMIT 1")->fetch_assoc();
            $finalId = (int) ($final['id_jogo'] ?? 0);
            $finalists = $finalId > 0 ? $connection->query('SELECT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ' . $finalId)->fetch_all(MYSQLI_ASSOC) : [];
            $finalistIds = array_map('intval', array_column($finalists, 'equipes_id_equipe'));
            sort($finalistIds);
            $expectedFinalists = [$teams[0], $teams[2]];
            sort($expectedFinalists);
            Assertions::assert('Vencedora por W.O. avança à final contra o bye',
                $finalId > 0 && $finalistIds === $expectedFinalists);
            if ($finalId <= 0) {
                throw new \RuntimeException('A semifinal não gerou a final esperada.');
            }
            Assertions::assertJsonSuccess('Mesário conclui também a final por W.O.', $mesario->postJson('api/v1/resultados', [
                'id_jogo' => $finalId, 'id_modalidade' => $modalityId,
                'resultados' => [['id_equipe' => $teams[0], 'gols' => 0], ['id_equipe' => $teams[2], 'gols' => 0]],
                'vencedor_wo_id' => $teams[0],
            ]));

            $state = $connection->query('SELECT status_jogo, vencedor_wo_equipe_id FROM jogos WHERE id_jogo = ' . $finalId)->fetch_assoc();
            $scores = $connection->query('SELECT resultado_partida FROM partidas WHERE jogos_id_jogo = ' . $finalId)->fetch_all(MYSQLI_ASSOC);
            $individual = (int) $connection->query('SELECT COUNT(*) FROM artilheiros WHERE jogos_id_jogo IN (' . $gameId . ', ' . $finalId . ')')->fetch_column();
            Assertions::assert('W.O. persiste vencedor sem gols nem pontos individuais',
                $state['status_jogo'] === 'Concluido' && (int) $state['vencedor_wo_equipe_id'] === $teams[0]
                && array_map('intval', array_column($scores, 'resultado_partida')) === [0, 0] && $individual === 0);
            $podium = $connection->query('SELECT posicao, id_equipe FROM pontuacoes_podio WHERE id_modalidade = ' . $modalityId . ' AND ativo = 1')->fetch_all(MYSQLI_ASSOC);
            Assertions::assert('Segundo e terceiro lugares permanecem vazios', count($podium) === 1 && (int) $podium[0]['posicao'] === 1 && (int) $podium[0]['id_equipe'] === $teams[0]);
            $admin = new TestClient();
            $admin->login('admin', '123');
            Assertions::assertStatus('Pódio manual não requalifica perdedor por W.O.', $admin->postJson('api/v1/podios', [
                'id_interclasse' => $editionId, 'id_modalidade' => $modalityId,
                'podio' => [['posicao' => 2, 'id_equipe' => $teams[1]]],
            ]), 400);
            Assertions::assertStatus('Pódio manual não requalifica finalista derrotada por W.O.', $admin->postJson('api/v1/podios', [
                'id_interclasse' => $editionId, 'id_modalidade' => $modalityId,
                'podio' => [['posicao' => 2, 'id_equipe' => $teams[2]]],
            ]), 400);
        } finally {
            if ($modalityId > 0) {
                $connection->query('DELETE FROM pontuacoes_podio WHERE id_modalidade = ' . $modalityId);
            }
            foreach ([$finalId, $gameId, $byeGameId] as $createdGameId) {
                if ($createdGameId <= 0) {
                    continue;
                }
                $connection->query('DELETE FROM partidas WHERE jogos_id_jogo = ' . $createdGameId);
                $connection->query('DELETE FROM jogos WHERE id_jogo = ' . $createdGameId);
            }
            foreach ($teams as $teamId) {
                $connection->query('DELETE FROM equipes WHERE id_equipe = ' . $teamId);
            }
            if ($modalityId > 0) {
                $connection->query('DELETE FROM modalidades WHERE id_modalidade = ' . $modalityId);
            }
            $connection->query('UPDATE turmas SET pontuacao_turma = ' . $before . ' WHERE id_turma = ' . $classId);
            $connection->close();
        }
    }
}
