<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$id_modalidade = isset($_GET['id_modalidade']) ? intval($_GET['id_modalidade']) : null;

if (!$id_modalidade) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID da modalidade é obrigatório."]);
    exit;
}

try {
    // 1. Identificar a Grande Final (jogo MM:2) concluída
    $sqlFinal = "SELECT j.id_jogo
                 FROM jogos j
                 WHERE j.modalidades_id_modalidade = ?
                   AND j.nome_jogo LIKE 'MM:2:%'
                   AND j.status_jogo IN ('Concluido', 'Finalizado')
                 ORDER BY j.id_jogo DESC LIMIT 1";

    $stmtF = $conn->prepare($sqlFinal);
    $stmtF->bind_param("i", $id_modalidade);
    $stmtF->execute();
    $resFinal = $stmtF->get_result()->fetch_assoc();

    $classificacao = [];

    // Sem Final concluída ainda, o pódio não existe
    if (!$resFinal) {
        echo json_encode([
            "success" => true,
            "modalidade_id" => $id_modalidade,
            "podio" => []
        ]);
        exit;
    }

    $id_final = (int) $resFinal['id_jogo'];

    // 2. Buscar 1º e 2º Lugares (da Final)
    $sqlPosicoesFinais = "SELECT t.nome_turma, t.nome_fantasia_turma, p.resultado_partida, e.id_equipe
                          FROM partidas p
                          INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                          INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                          WHERE p.jogos_id_jogo = ?
                          ORDER BY p.resultado_partida DESC";

    $stmtP = $conn->prepare($sqlPosicoesFinais);
    $stmtP->bind_param("i", $id_final);
    $stmtP->execute();
    $equipesFinal = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);

    $campeao = $equipesFinal[0] ?? null;
    $vice = $equipesFinal[1] ?? null;

    if ($campeao) {
        $classificacao[] = [
            "posicao" => 1,
            "equipe" => $campeao['nome_turma'],
            "fantasia" => $campeao['nome_fantasia_turma'],
            "status" => "Campeão"
        ];
    }

    if ($vice) {
        $classificacao[] = [
            "posicao" => 2,
            "equipe" => $vice['nome_turma'],
            "fantasia" => $vice['nome_fantasia_turma'],
            "status" => "Vice-Campeão"
        ];
    }

    // 3. Buscar o 3º Lugar: vencedor da Disputa de 3º lugar (POS:3) concluída
    $terceiro = null;
    $sqlTerceiro = "SELECT t.nome_turma, t.nome_fantasia_turma, p.resultado_partida, e.id_equipe
                    FROM partidas p
                    INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                    INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                    INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                    WHERE j.modalidades_id_modalidade = ?
                      AND j.nome_jogo = 'POS:3:0:N'
                      AND j.status_jogo IN ('Concluido', 'Finalizado')
                    ORDER BY p.resultado_partida DESC LIMIT 1";

    $stmtT = $conn->prepare($sqlTerceiro);
    $stmtT->bind_param("i", $id_modalidade);
    $stmtT->execute();
    $terceiro = $stmtT->get_result()->fetch_assoc();

    if ($terceiro) {
        $classificacao[] = [
            "posicao" => 3,
            "equipe" => $terceiro['nome_turma'],
            "fantasia" => $terceiro['nome_fantasia_turma'],
            "status" => "3º Lugar"
        ];
    }

    echo json_encode([
        "success" => true,
        "modalidade_id" => $id_modalidade,
        "podio" => $classificacao
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
