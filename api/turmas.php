<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
header('Content-Type: application/json');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        // Exige o id_interclasse — sem ele, retorna vazio
        if (!isset($_GET['id_interclasse']) || $_GET['id_interclasse'] === '') {
            echo json_encode([]);
            break;
        }

        $idInterclasse = intval($_GET['id_interclasse']);

        $filtro = aplicarFiltrosTurmas();

        $sql = "SELECT 
                    turmas.id_turma, 
                    turmas.nome_turma, 
                    turmas.turno_turma, 
                    turmas.nome_fantasia_turma, 
                    turmas.categorias_id_categoria,
                    interclasses.nome_interclasse,
                    categorias.nome_categoria
                FROM turmas 
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
                WHERE turmas.interclasses_id_interclasse = ? AND categorias.interclasses_id_interclasse = ?"
                . $filtro['sql'];

        $sql .= " ORDER BY turmas.nome_turma ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            break;
        }

        $bindParams = array_merge([$idInterclasse, $idInterclasse], $filtro['params']);
        $bindTypes = 'ii' . $filtro['types'];

        $bindArgs = [$bindTypes];
        for ($i = 0; $i < count($bindParams); $i++) {
            $bindArgs[] = &$bindParams[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        unset($bindArgs);

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            break;
        }
        $res = $stmt->get_result();
        if (!$res) {
            echo json_encode(["success" => false, "message" => "Erro ao obter resultados."]);
            break;
        }
        
        $resultados = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($resultados);
        break;

    case 'POST':
        requerEscrita();
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->interclasses_id_interclasse, $data->categorias_id_categoria, $data->nome_turma)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados obrigatórios ausentes."]);
            break;
        }

        $nomeTurma = trim((string) $data->nome_turma);
        $turno = isset($data->turno_turma) ? trim((string) $data->turno_turma) : null;
        $interclasseId = (int) $data->interclasses_id_interclasse;
        $categoriaId = (int) $data->categorias_id_categoria;

        $checkSql = "SELECT id_turma FROM turmas WHERE interclasses_id_interclasse = ? AND nome_turma = ?";
        if ($turno === null) {
            $checkSql .= " AND turno_turma IS NULL";
        } else {
            $checkSql .= " AND turno_turma = ?";
        }
        $checkSql .= " LIMIT 1";

        $stmtCheck = $conn->prepare($checkSql);
        if (!$stmtCheck) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Falha ao validar turma: " . $conn->error]);
            break;
        }

        if ($turno === null) {
            $stmtCheck->bind_param("is", $interclasseId, $nomeTurma);
        } else {
            $stmtCheck->bind_param("iss", $interclasseId, $nomeTurma, $turno);
        }
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["success" => false, "message" => "Já existe uma turma com este nome e período nesta edição do interclasse."]);
            $stmtCheck->close();
            break;
        }
        $stmtCheck->close();

        $turno = $data->turno_turma ?? null;
        $fantasia = $data->nome_fantasia_turma ?? null;

        $sql = "INSERT INTO turmas (interclasses_id_interclasse, categorias_id_categoria, nome_turma, turno_turma, nome_fantasia_turma, status_turma) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iissss",
            $data->interclasses_id_interclasse,
            $data->categorias_id_categoria,
            $data->nome_turma,
            $turno,
            $fantasia,
            $data->status_turma
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
        requerEscrita();
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
        if (isset($data->status_turma)) {
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

    case 'DELETE':
        requerExclusao();
        $idTurma = isset($_GET['id_turma']) ? (int) $_GET['id_turma'] : 0;
        if ($idTurma <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da turma é obrigatório.']);
            break;
        }

        $stmt = $conn->prepare('DELETE FROM turmas WHERE id_turma = ? LIMIT 1');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $conn->error]);
            break;
        }

        $stmt->bind_param('i', $idTurma);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Turma excluída com sucesso.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Turma não encontrada.']);
            }
        } else {
            $erroCodigo = $stmt->errno;

            if ($erroCodigo === 1451) {
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false,
                    'message' => 'Não é possível excluir esta turma pois existem registros (como alunos, modalidades ou jogos) vinculados a ela.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro interno do banco: ' . $stmt->error]);
            }
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
