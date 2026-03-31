<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // O Front-end envia o resultado consolidado da Session
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_partida) || !isset($data->resultado_final)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da partida e resultado final são obrigatórios."]);
            break;
        }

        $id_partida = intval($data->id_partida);
        $resultado = intval($data->resultado_final);

        // Atualiza o resultado na tabela partidas
        // E você pode aproveitar para mudar o status do jogo para 'Concluido' na tabela jogos
        $sql = "UPDATE partidas 
                INNER JOIN jogos ON partidas.jogos_id_jogo = jogos.id_jogo
                SET partidas.resultado_partida = ?, 
                    jogos.status_jogo = 'Concluido' 
                WHERE partidas.id_partida = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $resultado, $id_partida);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Partida encerrada e resultado salvo com sucesso!"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'GET':
        $id_jogo = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : null;

        if (!$id_jogo) {
            echo json_encode(["message" => "Informe o id_jogo"]);
            break;
        }

        // SELECT que traz TUDO: Dados do jogo + Nomes das Turmas
        $sql = "SELECT 
                    p.id_partida, 
                    p.equipes_id_equipe, 
                    p.resultado_partida,
                    j.nome_jogo, 
                    j.data_jogo, 
                    j.inicio_jogo, 
                    j.terminno_jogo,
                    j.status_jogo,
                    t.nome_turma
                FROM partidas p
                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                WHERE p.jogos_id_jogo = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_jogo);
        $stmt->execute();
        $res = $stmt->get_result();

        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;
}
