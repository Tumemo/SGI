<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosLocais();

        $sql = "SELECT id_local, nome_local, disponivel_local, carga_local FROM locais WHERE 1=1" . $filtro['sql'];
        $sql .= " ORDER BY nome_local ASC";

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $dados = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => $dados
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_local)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O nome do local é obrigatório."]);
            break;
        }

        $disponivel = $data->disponivel_local ?? '1';
        $carga = isset($data->carga_local) ? intval($data->carga_local) : null;

        $sql = "INSERT INTO locais (nome_local, disponivel_local, carga_local) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param("ssi", $data->nome_local, $disponivel, $carga);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Local cadastrado com sucesso!",
                "id_local" => $conn->insert_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

case 'PUT':
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id_local)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "O ID do local é obrigatório."]);
        break;
    }

    $campos = [];
    $params = [];
    $types = "";
    if (isset($data->nome_local)) {
        $campos[] = "nome_local = ?";
        $params[] = $data->nome_local;
        $types .= "s"; 
    }
    if (isset($data->status_local)) {
        $campos[] = "status_local = ?";
        $params[] = $data->status_local;
        $types .= "s";
    }
    if (isset($data->disponivel_local)) {
        $campos[] = "disponivel_local = ?";
        $params[] = $data->disponivel_local;
        $types .= "i";
    }


    if (empty($campos)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nenhum campo enviado para atualização."]);
        break;
    }

    // Monta a SQL dinamicamente
    $sql = "UPDATE locais SET " . implode(", ", $campos) . " WHERE id_local = ?";
    
    // Adiciona o ID ao final dos parâmetros e tipos
    $params[] = $data->id_local;
    $types .= "i"; // 'i' para integer

    $stmt = $conn->prepare($sql);
    
    // O operador '...' (splat operator) desempacota o array de parâmetros
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Local atualizado com sucesso!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao atualizar: " . $conn->error]);
    }
    break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
