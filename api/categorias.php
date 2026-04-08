<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosCategorias();


        $sql = "SELECT id_categoria, nome_categoria FROM categorias WHERE 1=1" . $filtro['sql'];
        $sql .= " ORDER BY nome_categoria ASC";

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

        if (!isset($data->nome_categoria) || empty(trim($data->nome_categoria))) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O campo nome_categoria é obrigatório."
            ]);
            break;
        }

        $sql = "INSERT INTO categorias (nome_categoria) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $data->nome_categoria);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Categoria cadastrada com sucesso!",
                "id_categoria" => $conn->insert_id
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
        if (isset($data->id_categoria, $data->nome_categoria)) {
            $sql = "UPDATE categorias SET nome_categoria = ? WHERE id_categoria = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $data->nome_categoria, $data->id_categoria);
            $stmt->execute();
            echo json_encode(["success" => true]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}