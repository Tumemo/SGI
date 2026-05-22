<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosOcorrencias();

        $sql = "SELECT 
                    ocorrencias.id_ocorrencia, 
                    ocorrencias.titulo_ocorrencia, 
                    ocorrencias.descricao_ocorrencia, 
                    ocorrencias.data_ocorrencia, 
                    ocorrencias.hora_ocorrencia, 
                    ocorrencias.penalidade,
                    usuarios.nome_usuario,
                    usuarios.id_usuario
                FROM ocorrencias 
                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY ocorrencias.data_ocorrencia DESC, ocorrencias.hora_ocorrencia DESC";

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

        if (!isset($data->titulo_ocorrencia, $data->descricao_ocorrencia, $data->data_ocorrencia, $data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $sql = "INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia, usuarios_id_usuario, penalidade) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $data->titulo_ocorrencia,
            $data->descricao_ocorrencia,
            $data->data_ocorrencia,
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

        // Apenas o ID é estritamente obrigatório para localizar o registro
        if (!isset($data->id_ocorrencia)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da ocorrência é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        // Verificação dinâmica de cada campo
        if (isset($data->titulo_ocorrencia)) {
            $campos[] = "titulo_ocorrencia = ?";
            $params[] = $data->titulo_ocorrencia;
            $types .= "s"; // string
        }

        if (isset($data->descricao_ocorrencia)) {
            $campos[] = "descricao_ocorrencia = ?";
            $params[] = $data->descricao_ocorrencia;
            $types .= "s"; 
        }
        if (isset($data->status_ocorrencia)) {
            $campos[] = "status_ocorrencia = ?";
            $params[] = $data->status_ocorrencia;
            $types .= "s"; 
        }

        if (isset($data->penalidade)) {
            $campos[] = "penalidade = ?";
            $params[] = $data->penalidade;
            $types .= "i"; 
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado fornecido para atualização."]);
            break;
        }

        $sql = "UPDATE ocorrencias SET " . implode(", ", $campos) . " WHERE id_ocorrencia = ?";
        

        $params[] = $data->id_ocorrencia;
        $types .= "i";

        $stmt = $conn->prepare($sql);
    
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao atualizar: " . $conn->error]);
        }
        break;
}
