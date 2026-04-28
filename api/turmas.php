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

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_turma)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da turma é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->interclasses_id_interclasse)) {
            $campos[] = "interclasses_id_interclasse = ?";
            $params[] = $data->interclasses_id_interclasse;
            $types .= "i";
        }
        if (isset($data->nome_turma)) {
            $campos[] = "nome_turma = ?";
            $params[] = $data->nome_turma;
            $types .= "s";
        }
        if (isset($data->turno_turma)) {
            $campos[] = "turno_turma = ?";
            $params[] = $data->turno_turma;
            $types .= "s";
        }
        if (isset($data->nome_fantasia_turma)) {
            $campos[] = "nome_fantasia_turma = ?";
            $params[] = $data->nome_fantasia_turma;
            $types .= "s";
        }
        if (isset($data->categorias_id_categoria)) {
            $campos[] = "categorias_id_categoria = ?";
            $params[] = $data->categorias_id_categoria;
            $types .= "i";
        }
        if (isset($data->status_turma)){
            $campos[] = "status_turma = ?";
            $params[] = $data->status_turma;
            $types .= "s";
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum campo enviado para atualização."]);
            break;
        }

        $sql = "UPDATE turmas SET " . implode(", ", $campos) . " WHERE id_turma = ?";
        $params[] = $data->id_turma;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Turma atualizada com sucesso!"]);
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