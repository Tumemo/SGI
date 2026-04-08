<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosOcorrencias();

        // SQL Base com INNER JOIN para saber quem é o usuário
        $sql = "SELECT 
                    ocorrencias.id_ocorrecia, 
                    ocorrencias.titulo_ocorrecia, 
                    ocorrencias.descricao_ocorrecia, 
                    ocorrencias.data_ocorrecia, 
                    ocorrencias.hora_ocorrecia, 
                    ocorrencias.penalidade,
                    usuarios.nome_usuario,
                    usuarios.id_usuario
                FROM ocorrencias 
                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY ocorrencias.data_ocorrecia DESC, ocorrencias.hora_ocorrecia DESC";

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

        // Validação de campos obrigatórios
        if (!isset($data->titulo_ocorrecia, $data->descricao_ocorrecia, $data->data_ocorrecia, $data->hora_ocorrecia, $data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $sql = "INSERT INTO ocorrencias (titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, hora_ocorrecia, usuarios_id_usuario, penalidade) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii",
            $data->titulo_ocorrecia,
            $data->descricao_ocorrecia,
            $data->data_ocorrecia,
            $data->hora_ocorrecia,
            $data->usuarios_id_usuario,
            $penalidade
        );

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Ocorrência registrada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'DELETE':
        // Adicionando um DELETE básico para poder remover erros de digitação
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM ocorrencias WHERE id_ocorrecia = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["success" => true]);
        }
        break;
}