<?php
require_once '../config/db.php';
require_once 'auth.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit(); }

switch ($method) {
    case 'GET':
        $idInterclasse = isset($_GET['id_interclasse']) ? (int) $_GET['id_interclasse'] : 0;
        if ($idInterclasse <= 0) {
            echo json_encode([]);
            break;
        }

        $sql = "SELECT ot.*, t.nome_turma, t.nome_fantasia_turma,
                       c.nome_categoria, u.nome_usuario
                FROM ocorrencias_turmas ot
                INNER JOIN turmas t ON t.id_turma = ot.turmas_id_turma
                INNER JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
                INNER JOIN usuarios u ON u.id_usuario = ot.usuarios_id_usuario
                WHERE ot.interclasses_id_interclasse = ?";
        $types = 'i';
        $params = [$idInterclasse];

        if (!empty($_GET['id_turma'])) {
            $sql .= " AND ot.turmas_id_turma = ?";
            $types .= 'i';
            $params[] = (int) $_GET['id_turma'];
        }

        $sql .= " ORDER BY ot.data_registro DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        requerEscrita();
        $data = json_decode(file_get_contents('php://input'));

        if (empty($data->turmas_id_turma) ||
            empty($data->interclasses_id_interclasse) || empty($data->titulo_ocorrencia) ||
            empty($data->data_ocorrencia)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $pontos = isset($data->pontos_descontados) ? (int) $data->pontos_descontados : 0;
        $descricao = $data->descricao_ocorrencia ?? '';
        $idUsuario = $data->usuarios_id_usuario ?? $_SESSION['id_usuario'] ?? null;

        $types = 'iissis';
        $params = [$data->turmas_id_turma, $data->interclasses_id_interclasse,
                   $data->titulo_ocorrencia, $descricao, $pontos, $data->data_ocorrencia];

        if ($idUsuario === null) {
            $types .= 's';
            $params[] = null;
        } else {
            $types .= 'i';
            $params[] = (int) $idUsuario;
        }

        $stmt = $conn->prepare(
            "INSERT INTO ocorrencias_turmas (turmas_id_turma, interclasses_id_interclasse,
             titulo_ocorrencia, descricao_ocorrencia, pontos_descontados, data_ocorrencia, usuarios_id_usuario)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Ocorrência registrada!", "id" => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $stmt->error]);
        }
        break;

    case 'DELETE':
        requerEscrita();
        $data = json_decode(file_get_contents('php://input'));
        $id = isset($data->id_ocorrencia_turma) ? (int) $data->id_ocorrencia_turma : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID obrigatório."]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM ocorrencias_turmas WHERE id_ocorrencia_turma = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Ocorrência removida!"]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Ocorrência não encontrada."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
