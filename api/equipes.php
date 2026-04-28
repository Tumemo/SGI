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
                    equipes.status_equipe,
                    modalidades.nome_modalidade, 
                    turmas.nome_turma,
                    interclasses.nome_interclasse
                FROM equipes 
                INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
                INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                WHERE 1=1" . $filtro['sql'];

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

        // Pegamos a ação enviada pelo Front-end (ex: 'criar_equipe' ou 'adicionar_usuarios')
        $acao = $data->acao ?? '';

        if ($acao === 'criar_equipe') {
            if (!isset($data->modalidades_id_modalidade, $data->turmas_id_turma)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Dados incompletos para criar equipa."]);
                break;
            }

            $sql = "INSERT INTO equipes (modalidades_id_modalidade, turmas_id_turma, status_equipe) VALUES (?, ?, '1')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $data->modalidades_id_modalidade, $data->turmas_id_turma);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Equipa criada!", "id_equipe" => $conn->insert_id]);
            } else {
                echo json_encode(["success" => false, "message" => $conn->error]);
            }
        } elseif ($acao === 'adicionar_usuarios') {
            // Lógica para vincular usuários à equipe (equipes_has_usuarios)
            if (!isset($data->id_equipe, $data->usuarios)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID da equipa e lista de utilizadores são obrigatórios."]);
                break;
            }

            // $data->usuarios deve ser um array de IDs: [1, 5, 10]
            $id_equipe = $data->id_equipe;
            $erro = false;

            foreach ($data->usuarios as $id_usuario) {
                $sql = "INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $id_equipe, $id_usuario);
                if (!$stmt->execute()) {
                    $erro = true;
                }
            }

            if (!$erro) {
                echo json_encode(["success" => true, "message" => "Utilizadores adicionados à equipa com sucesso!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Ocorreu um erro ao adicionar alguns utilizadores."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Ação não definida ou inválida."]);
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
        if (isset($data->status_equipe)) {
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
