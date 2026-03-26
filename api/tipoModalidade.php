<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':

        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if ($id) {
            $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades 
    WHERE tipos_modalidades.id_tipo_modalidade = $id";
        } else {
            $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades";
        }

        $res = $conn->query($sql);
        $tipo = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $tipo[] = $row;
            }
        }

        echo json_encode($tipo);
        break;

    
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_tipo_modalidade)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O campo nome_tipo_modalidade é obrigatório."
            ]);
            break;
        }

        $nome_tipo_modalidade = "'" . $conn->real_escape_string($data->nome_tipo_modalidade) . "'";

        $sql = "INSERT INTO tipos_modalidades (nome_tipo_modalidade) VALUES ($nome_tipo_modalidade)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Tipo de modalidade cadastrado com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execução da query: " . $conn->error
            ]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
