<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input') ?: '{}');

if (!isset($data->id_jogo, $data->resultados)) {
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idJogo = (int) $data->id_jogo;

$conn->begin_transaction();

try {
    foreach ($data->resultados as $res) {
        $stmt = $conn->prepare('UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?');
        $gols = (int) $res->gols;
        $idEquipe = (int) $res->id_equipe;
        $stmt->bind_param('iii', $gols, $idJogo, $idEquipe);
        $stmt->execute();
        $stmt->close();
    }

    $stmtStatus = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
    $stmtStatus->bind_param('i', $idJogo);
    $stmtStatus->execute();
    $stmtStatus->close();

    sgi_chaveamento_processar_avanco($conn, $idJogo);

    // Premiar pontos para TODAS as partidas (não só a final)
    $stF = $conn->prepare('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
    $stF->bind_param('i', $idJogo);
    $stF->execute();
    $jogoInfo = $stF->get_result()->fetch_assoc();
    $stF->close();

    if ($jogoInfo) {
        $meta = sgi_mm_parse($jogoInfo['nome_jogo'] ?? '');
        $idModalidade = (int) $jogoInfo['modalidades_id_modalidade'];

        $stM = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $stM->bind_param('i', $idModalidade);
        $stM->execute();
        $modRow = $stM->get_result()->fetch_assoc();
        $stM->close();

        if ($modRow) {
            $idInter = (int) $modRow['interclasses_id_interclasse'];
            $stI = $conn->prepare('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ? LIMIT 1');
            $stI->bind_param('i', $idInter);
            $stI->execute();
            $ptRow = $stI->get_result()->fetch_assoc();
            $stI->close();

            if ($ptRow) {
                $p1 = (int) ($ptRow['ponto_1_lugar'] ?? 0);
                $p2 = (int) ($ptRow['ponto_2_lugar'] ?? 0);

                $partidas = sgi_mm_carregar_partidas_jogo($conn, $idJogo);

                if (count($partidas) >= 2) {
                    usort($partidas, static fn($a, $b) => $b['resultado_partida'] <=> $a['resultado_partida']);
                    $vencedorEquipe = (int) $partidas[0]['equipes_id_equipe'];
                    $perdedorEquipe = (int) $partidas[1]['equipes_id_equipe'];

                    $stW = $conn->prepare(
                        'UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                         WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1'
                    );
                    $stW->bind_param('ii', $p1, $vencedorEquipe);
                    $stW->execute();
                    $stW->close();

                    $stL = $conn->prepare(
                        'UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                         WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1'
                    );
                    $stL->bind_param('ii', $p2, $perdedorEquipe);
                    $stL->execute();
                    $stL->close();
                } elseif (count($partidas) === 1 && $meta && $meta['largura'] === 1) {
                    $campeaoEquipe = (int) $partidas[0]['equipes_id_equipe'];
                    $stC = $conn->prepare(
                        'UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                         WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1'
                    );
                    $stC->bind_param('ii', $p1, $campeaoEquipe);
                    $stC->execute();
                    $stC->close();
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Resultado lançado!'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
