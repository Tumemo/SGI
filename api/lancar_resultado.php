<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

// Esperamos: { "id_jogo": 1, "resultados": [ {"id_equipe": 5, "gols": 2}, {"id_equipe": 8, "gols": 1} ] }

if (!isset($data->id_jogo, $data->resultados)) {
    echo json_encode(["success" => false, "message" => "Dados insuficientes."]);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Atualizar os resultados na tabela 'partidas'
    foreach ($data->resultados as $res) {
        $stmt = $conn->prepare("UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?");
        $stmt->bind_param("iii", $res->gols, $data->id_jogo, $res->id_equipe);
        $stmt->execute();
    }

    // 2. Marcar o jogo como 'Concluido'
    $stmtStatus = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
    $stmtStatus->bind_param("i", $data->id_jogo);
    $stmtStatus->execute();

    // 3. Verificar se TODOS os jogos da modalidade no momento estão concluídos
    // Primeiro, pegamos a modalidade desse jogo
    $resMod = $conn->query("SELECT modalidades_id_modalidade FROM jogos WHERE id_jogo = {$data->id_jogo}");
    $id_modalidade = $resMod->fetch_assoc()['modalidades_id_modalidade'];

    // Verificamos se há algum jogo ainda 'Agendado' para esta modalidade
    $resPendentes = $conn->query("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = $id_modalidade AND status_jogo = 'Agendado'");
    
    if ($resPendentes->num_rows === 0) {
        // HORA DE GERAR A PRÓXIMA FASE!
        gerarProximaFase($conn, $id_modalidade);
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Resultado salvo e chaveamento atualizado!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

/**
 * Função para promover os vencedores
 */
function gerarProximaFase($conn, $id_modalidade) {
    // Busca os vencedores da última leva de jogos (os que não foram "promovidos" ainda)
    // Como não temos a coluna 'fase', pegamos os vencedores dos jogos que não têm sucessores
    $sqlVencedores = "SELECT p.equipes_id_equipe 
                      FROM partidas p
                      INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                      WHERE j.modalidades_id_modalidade = ? 
                      AND j.status_jogo = 'Concluido'
                      AND p.resultado_partida = (SELECT MAX(resultado_partida) FROM partidas WHERE jogos_id_jogo = j.id_jogo)
                      ORDER BY j.id_jogo ASC";

    $stmtV = $conn->prepare($sqlVencedores);
    $stmtV->bind_param("i", $id_modalidade);
    $stmtV->execute();
    $todosVencedores = $stmtV->get_result()->fetch_all(MYSQLI_ASSOC);

    // Se o número de vencedores for 1, o torneio acabou.
    if (count($todosVencedores) <= 1) return;
    $vencedoresParaProxima = $todosVencedores; // Aqui você pode filtrar se necessário

    $resLocal = $conn->query("SELECT id_local FROM locais LIMIT 1");
    $id_local = $resLocal->fetch_assoc()['id_local'] ?? 1;

    for ($i = 0; $i < count($vencedoresParaProxima); $i += 2) {
        if (!isset($vencedoresParaProxima[$i+1])) break;

        $nome = "Proxima Fase - Jogo " . (($i/2)+1);
        $stmtJ = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '10:00:00', 'Agendado', ?, ?)");
        $stmtJ->bind_param("sii", $nome, $id_modalidade, $id_local);
        $stmtJ->execute();
        $id_novo_jogo = $conn->insert_id;

        $stmtP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, status_pardida) VALUES (?, ?, '1')");
        $stmtP->bind_param("ii", $id_novo_jogo, $vencedoresParaProxima[$i]['equipes_id_equipe']);
        $stmtP->execute();
        $stmtP->bind_param("ii", $id_novo_jogo, $vencedoresParaProxima[$i+1]['equipes_id_equipe']);
        $stmtP->execute();
    }
}