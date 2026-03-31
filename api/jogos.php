<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 1. Captura de filtros
        $id = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : null;
        $id_modalidade = isset($_GET['modalidades_id_modalidade']) ? intval($_GET['modalidades_id_modalidade']) : null;
        $id_local = isset($_GET['locais_id_local']) ? intval($_GET['locais_id_local']) : null;
        $data = isset($_GET['data']) ? $_GET['data'] : null;

        // 2. SQL Base (Único para todos os casos)
        $sql = "SELECT 
                    jogos.id_jogo, 
                    jogos.nome_jogo, 
                    jogos.data_jogo, 
                    jogos.inicio_jogo, 
                    jogos.terminno_jogo, 
                    jogos.status_jogo, 
                    modalidades.nome_modalidade, 
                    locais.nome_local 
                FROM jogos 
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local";

        // 3. Construção dinâmica do WHERE
        $conditions = [];
        $params = [];
        $types = "";

        if ($id) {
            $conditions[] = "jogos.id_jogo = ?";
            $params[] = $id;
            $types .= "i";
        }
        if ($id_modalidade) {
            $conditions[] = "jogos.modalidades_id_modalidade = ?";
            $params[] = $id_modalidade;
            $types .= "i";
        }
        if ($id_local) {
            $conditions[] = "jogos.locais_id_local = ?";
            $params[] = $id_local;
            $types .= "i";
        }
        if ($data) {
            $conditions[] = "jogos.data_jogo = ?";
            $params[] = $data;
            $types .= "s";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC";

        // 4. Execução Segura
        $stmt = $conn->prepare($sql);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // Aqui deve ser o cadastro do JOGO, não da ocorrência!
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_jogo, $data->data_jogo, $data->modalidades_id_modalidade, $data->locais_id_local)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados do jogo incompletos."]);
            break;
        }

        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, terminno_jogo, modalidades_id_modalidade, locais_id_local, status_jogo) 
                VALUES (?, ?, ?, ?, ?, ?, 'Agendado')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", 
            $data->nome_jogo, 
            $data->data_jogo, 
            $data->inicio_jogo, 
            $data->terminno_jogo, 
            $data->modalidades_id_modalidade, 
            $data->locais_id_local
        );

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Jogo agendado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;
}