<?php
require_once '../config/db.php';
require_once 'filtros.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':

        $filtro = aplicarFiltrosEquipes();

        $sql = "SELECT 
                    equipes.id_equipe, 
                    modalidades.nome_modalidade, 
                    usuarios.nome_usuario AS representante, 
                    turmas.nome_turma,
                    turmas.nome_fantasia_turma,
                    interclasses.nome_interclasse
                FROM equipes 
                INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
                INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
                INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                WHERE 1=1" . $filtro['sql'];

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $equipes = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode($equipes);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->modalidades_id_modalidade, $data->usuarios_id_usuario1, $data->turmas_id_turma)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Dados incompletos para cadastro de equipe."
            ]);
            break;
        }

        $sql = "INSERT INTO equipes (modalidades_id_modalidade, usuarios_id_usuario1, turmas_id_turma) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param("iii", $data->modalidades_id_modalidade, $data->usuarios_id_usuario1, $data->turmas_id_turma);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Equipe cadastrada com sucesso!"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro ao cadastrar: " . $conn->error
            ]);
        }
        break;
case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_equipe)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da equipe é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->modalidades_id_modalidade)) {
            $campos[] = "modalidades_id_modalidade = ?";
            $params[] = $data->modalidades_id_modalidade;
            $types .= "i";
        }
        if (isset($data->usuarios_id_usuario1)) {
            $campos[] = "usuarios_id_usuario1 = ?";
            $params[] = $data->usuarios_id_usuario1;
            $types .= "i";
        }
        if (isset($data->turmas_id_turma)) {
            $campos[] = "turmas_id_turma = ?";
            $params[] = $data->turmas_id_turma;
            $types .= "i";
        }
        if(isset($data->status_equipe)){
            $campos[] = "status_equipe = ?";
            $params[] = $data->status_equipe;
            $types .= "s";   
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum campo enviado para atualização."]);
            break;
        }

        $sql = "UPDATE equipes SET " . implode(", ", $campos) . " WHERE id_equipe = ?";
        $params[] = $data->id_equipe;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Equipe atualizada com sucesso!"]);
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
