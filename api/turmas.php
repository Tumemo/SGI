<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosTurmas();

        $sql = "SELECT 
                    turmas.id_turma, 
                    turmas.nome_turma, 
                    turmas.turno_turma, 
                    turmas.nome_fantasia_turma, 
                    interclasses.nome_interclasse,
                    categorias.nome_categoria
                FROM turmas 
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY turmas.nome_turma ASC";

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

        if (!isset($data->interclasses_id_interclasse, $data->categorias_id_categoria, $data->nome_turma)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados obrigatórios ausentes."]);
            break;
        }

        $turno = $data->turno_turma ?? null;
        $fantasia = $data->nome_fantasia_turma ?? null;

        $sql = "INSERT INTO turmas (interclasses_id_interclasse, categorias_id_categoria, nome_turma, turno_turma, nome_fantasia_turma) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss",
            $data->interclasses_id_interclasse,
            $data->categorias_id_categoria,
            $data->nome_turma,
            $turno,
            $fantasia
        );

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Turma cadastrada com sucesso!",
                "id_turma" => $conn->insert_id
            ]);
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