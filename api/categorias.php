<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id_categoria = isset($_GET['id_categoria']) ? intval($_GET['id_categoria']) : null;

        $sql = "SELECT id_categoria, nome_categoria FROM categorias";

        if ($id_categoria) {
            $sql .= " WHERE id_categoria = $id_categoria";
        }

        $res = $conn->query($sql);
        $categorias = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $categorias[] = $row;
            }
            echo json_encode($categorias);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro na consulta: " . $conn->error]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_categoria)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O campo nome_categoria é obrigatório."
            ]);
            break;
        }

        $nome_categoria = "'" . $conn->real_escape_string($data->nome_categoria) . "'";

        $sql = "INSERT INTO categorias (nome_categoria) VALUES ($nome_categoria)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Categoria cadastrada com sucesso"
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