<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT nome_local, disponivel_local, carga_local FROM locais";
        $res = $conn->query($sql);

        if (!$res) {
            echo json_encode([
                "success" => false,
                "message" => "Erro ao buscar locais.",
                "error" => $conn->error
            ]);
            exit;
        }

        $results = [];

        while ($row = $res->fetch_assoc()) {
            $results[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => count($results) > 0
                ? "Locais exibidos com sucesso."
                : "Nenhum local encontrado.",
            "data" => $results
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        
        if (!isset($data->nome_local)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Dados incompletos. É necessário enviar pelo menos o nome_local."
            ]);
            break;
        }

      
        $nome_local = "'" . $conn->real_escape_string($data->nome_local) . "'";

        $disponivel_local = isset($data->disponivel_local) ? "'" . $conn->real_escape_string($data->disponivel_local) . "'" : "'1'";

       
        $carga_local = isset($data->carga_local) ? intval($data->carga_local) : "NULL";

        
        $sql = "INSERT INTO locais (nome_local, disponivel_local, carga_local) VALUES ($nome_local, $disponivel_local, $carga_local)";

       
        $res = $conn->query($sql);

        if ($res === TRUE) {
            http_response_code(200); 
            echo json_encode([
                "success" => true,
                "message" => "Local cadastrado com sucesso"
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
