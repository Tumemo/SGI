<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id_partida = isset($_GET['id_partida']) ? intval($_GET['id_partida']) : null;
        $id_jogo = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : null;
        $id_equipe = isset($_GET['id_equipe']) ? intval($_GET['id_equipe']) : null;

        $sql = "SELECT p.id_partida, p.jogos_id_jogo, p.equipes_id_equipe, p.resultado_partida, j.nome_jogo 
                FROM partidas p
                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo";

        $conditions = [];
        
        if ($id_partida) {
            $conditions[] = "p.id_partida = $id_partida";
        }
        if ($id_jogo) {
            $conditions[] = "p.jogos_id_jogo = $id_jogo";
        }
        if ($id_equipe) {
            $conditions[] = "p.equipes_id_equipe = $id_equipe";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $res = $conn->query($sql);
        $partidas = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $partidas[] = $row;
            }
            echo json_encode($partidas);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro na consulta: " . $conn->error]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->jogos_id_jogo) || !isset($data->equipes_id_equipe)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos jogos_id_jogo e equipes_id_equipe são obrigatórios."
            ]);
            break;
        }

        $id_jogo = intval($data->jogos_id_jogo);
        $id_equipe = intval($data->equipes_id_equipe);
        
        $resultado_partida = isset($data->resultado_partida) ? intval($data->resultado_partida) : 0;

        $sql = "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida) VALUES ($id_jogo, $id_equipe, $resultado_partida)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Partida cadastrada com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execução da query: " . $conn->error
            ]);
        }
        break;
}