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

    case "POST":
        if (!isset($data['user_id']) ||  !isset($data['local_nome']) ||  !isset($data['capacidade_local'])) {
            echo json_encode(["success" => false,  "message" => "Parâmetros insuficientes."]);
            exit;
        }

        $nome_local = $data['local_nome'];
        $disponivel_local = 1;
        $capacidade_local = $data['capacidade_local'];

        $sql = "INSERT INTO locais (nome_local, disponivel_local, carga_local) 
        VALUES ('$nome_local', $disponivel_local, $capacidade_local)";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode([
                "success" => true,
                "message" => "Local inserido com sucesso.",
                "insert_id" => $conn->insert_id
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Erro ao inserir.",
                "error" => $conn->error
            ]);
        }
        break;
    
    
}
