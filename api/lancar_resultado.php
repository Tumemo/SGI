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

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Resultado lançado!'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
