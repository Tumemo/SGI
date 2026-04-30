<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id_jogo, $data->resultados)) {
    echo json_encode(["success" => false, "message" => "Dados insuficientes."]);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Atualizar gols
    foreach ($data->resultados as $res) {
        $stmt = $conn->prepare("UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?");
        $stmt->bind_param("iii", $res->gols, $data->id_jogo, $res->id_equipe);
        $stmt->execute();
    }

    // 2. Concluir jogo
    $stmtStatus = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
    $stmtStatus->bind_param("i", $data->id_jogo);
    $stmtStatus->execute();

    // 3. Info da fase
    $resMod = $conn->query("SELECT modalidades_id_modalidade, nome_jogo FROM jogos WHERE id_jogo = {$data->id_jogo}");
    $dados = $resMod->fetch_assoc();
    $id_modalidade = $dados['modalidades_id_modalidade'];
    $fase_limpa = preg_replace('/ - (Jogo \d+|Avanço Automático)/', '', $dados['nome_jogo']);

    // 4. Verificação de trava: Só gera a próxima fase se não houver NENHUM jogo agendado ou concluído da fase SEGUINTE
    $fluxo = ["Oitavas de Final" => "Quartas de Final", "Quartas de Final" => "Semifinal", "Semifinal" => "Grande Final"];
    $proxima_fase_nome = $fluxo[trim($fase_limpa)] ?? null;

    if ($proxima_fase_nome) {
        $stmtCheckNext = $conn->prepare("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE ?");
        $buscaProx = $proxima_fase_nome . "%";
        $stmtCheckNext->bind_param("is", $id_modalidade, $buscaProx);
        $stmtCheckNext->execute();
        
        // Se já existem jogos na próxima fase, não faz nada
        if ($stmtCheckNext->get_result()->num_rows == 0) {
            // Verifica se a fase atual realmente terminou
            $stmtCheckAtual = $conn->prepare("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND status_jogo = 'Agendado' AND nome_jogo LIKE ?");
            $buscaAtual = $fase_limpa . "%";
            $stmtCheckAtual->bind_param("is", $id_modalidade, $buscaAtual);
            $stmtCheckAtual->execute();

            if ($stmtCheckAtual->get_result()->num_rows === 0) {
                gerarProximaFase($conn, $id_modalidade, $fase_limpa, $proxima_fase_nome);
            }
        }
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Resultado lançado!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function gerarProximaFase($conn, $id_modalidade, $fase_anterior, $nova_fase) {
    $resL = $conn->query("SELECT id_local FROM locais WHERE status_local = '1' LIMIT 1");
    $id_local = ($l = $resL->fetch_assoc()) ? $l['id_local'] : 4;

    // CORREÇÃO: Subquery correlacionada para garantir que pegamos o vencedor de CADA jogo da fase
    $sqlV = "SELECT p.equipes_id_equipe 
             FROM partidas p 
             INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
             WHERE j.modalidades_id_modalidade = ? 
             AND j.nome_jogo LIKE ? 
             AND j.status_jogo = 'Concluido'
             AND p.resultado_partida = (
                 SELECT MAX(resultado_partida) 
                 FROM partidas p2 
                 WHERE p2.jogos_id_jogo = j.id_jogo
             )";
    
    $stV = $conn->prepare($sqlV);
    $busca = $fase_anterior . "%";
    $stV->bind_param("is", $id_modalidade, $busca);
    $stV->execute();
    $vencedores = $stV->get_result()->fetch_all(MYSQLI_ASSOC);

    // Inéditos: Equipes que nunca apareceram em NENHUMA partida desta modalidade
    $sqlI = "SELECT id_equipe as equipes_id_equipe FROM equipes 
             WHERE modalidades_id_modalidade = ? 
             AND id_equipe NOT IN (
                 SELECT p3.equipes_id_equipe 
                 FROM partidas p3 
                 INNER JOIN jogos j3 ON p3.jogos_id_jogo = j3.id_jogo 
                 WHERE j3.modalidades_id_modalidade = ?
             )";
    $stI = $conn->prepare($sqlI);
    $stI->bind_param("ii", $id_modalidade, $id_modalidade);
    $stI->execute();
    $ineditas = $stI->get_result()->fetch_all(MYSQLI_ASSOC);

    $promovidos = array_merge($vencedores, $ineditas);
    
    // Remove duplicados (caso um vencedor também apareça como inédito por erro de fluxo)
    $temp = [];
    foreach($promovidos as $p) $temp[$p['equipes_id_equipe']] = $p;
    $promovidos = array_values($temp);

    if (count($promovidos) <= 1) return;

    for ($i = 0; $i < count($promovidos); $i += 2) {
        if (!isset($promovidos[$i+1])) {
            // Avanço Automático
            $nome_j = $nova_fase . " - Avanço Automático";
            $stmt = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '08:00:00', 'Concluido', ?, ?)");
            $stmt->bind_param("sii", $nome_j, $id_modalidade, $id_local);
            $stmt->execute();
            $id_j = $conn->insert_id;

            $stmtP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 1, '1')");
            $stmtP->bind_param("ii", $id_j, $promovidos[$i]['equipes_id_equipe']);
            $stmtP->execute();
            continue;
        }

        // Jogo Normal
        $nome_j = $nova_fase . " - Jogo " . (($i/2)+1);
        $stmt = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
        $stmt->bind_param("sii", $nome_j, $id_modalidade, $id_local);
        $stmt->execute();
        $id_j = $conn->insert_id;

        $stmtP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, status_pardida) VALUES (?, ?, '1')");
        $stmtP->bind_param("ii", $id_j, $promovidos[$i]['equipes_id_equipe']); $stmtP->execute();
        $stmtP->bind_param("ii", $id_j, $promovidos[$i+1]['equipes_id_equipe']); $stmtP->execute();
    }
}