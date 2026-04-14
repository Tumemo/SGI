<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosTiposModalidades();

        // SQL Base
        $sql = "SELECT id_tipo_modalidade, nome_tipo_modalidade FROM tipos_modalidades WHERE 1=1" . $filtro['sql'];
        $sql .= " ORDER BY nome_tipo_modalidade ASC";

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $dados = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode($dados);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_tipo_modalidade) || empty(trim($data->nome_tipo_modalidade))) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O campo nome_tipo_modalidade é obrigatório."
            ]);
            break;
        }

        $sql = "INSERT INTO tipos_modalidades (nome_tipo_modalidade) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $data->nome_tipo_modalidade);

        if ($stmt->execute()) {
            http_response_code(201); // Created
            echo json_encode([
                "success" => true,
                "message" => "Tipo de modalidade cadastrado com sucesso!",
                "id_tipo_modalidade" => $conn->insert_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro ao salvar: " . $conn->error
            ]);
        }
        break;
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_tipo_modalidade, $data->nome_tipo_modalidade)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID e Nome são obrigatórios."]);
            break;
        }

        $sql = "UPDATE tipos_modalidades SET nome_tipo_modalidade = ? WHERE id_tipo_modalidade = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $data->nome_tipo_modalidade, $data->id_tipo_modalidade);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Tipo de modalidade atualizado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
