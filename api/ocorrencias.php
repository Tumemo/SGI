<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosOcorrencias();

        $sql = "SELECT 
                    ocorrencias.id_ocorrecia, 
                    ocorrencias.titulo_ocorrecia, 
                    ocorrencias.descricao_ocorrecia, 
                    ocorrencias.data_ocorrecia, 
                    ocorrencias.hora_ocorrecia, 
                    ocorrencias.penalidade,
                    usuarios.nome_usuario,
                    usuarios.id_usuario
                FROM ocorrencias 
                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY ocorrencias.data_ocorrecia DESC, ocorrencias.hora_ocorrecia DESC";

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo_ocorrecia, $data->descricao_ocorrecia, $data->data_ocorrecia, $data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $sql = "INSERT INTO ocorrencias (titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, usuarios_id_usuario, penalidade) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $data->titulo_ocorrecia,
            $data->descricao_ocorrecia,
            $data->data_ocorrecia,
            $data->usuarios_id_usuario,
            $penalidade
        );

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Ocorrência registrada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_ocorrecia, $data->titulo_ocorrecia, $data->descricao_ocorrecia, $data->penalidade)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $sql = "UPDATE ocorrencias SET titulo_ocorrecia = ?, descricao_ocorrecia = ?, penalidade = ? WHERE id_ocorrecia = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $data->titulo_ocorrecia, $data->descricao_ocorrecia, $data->penalidade, $data->id_ocorrecia);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência atualizada!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;
}
