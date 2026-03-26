<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':


        $id_usuario = isset($_GET['usuarios_id_usuario']) ? $_GET['usuarios_id_usuario'] : null;

        if ($id_usuario) {
            $sql = "SELECT ocorrencias.id_ocorrecia, ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia, ocorrencias.hora_ocorrecia, usuarios.nome_usuario, ocorrencias.penalidade 
    FROM ocorrencias 
    INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
    WHERE ocorrencias.usuarios_id_usuario = $id_usuario";
        } else {
            $sql = "SELECT ocorrencias.id_ocorrecia, ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia, ocorrencias.hora_ocorrecia, usuarios.nome_usuario, ocorrencias.penalidade 
    FROM ocorrencias 
    INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario";
        }

        $res = $conn->query($sql);
        $ocorrencias = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ocorrencias[] = $row;
            }
        }

        echo json_encode($ocorrencias);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo_ocorrecia) || !isset($data->descricao_ocorrecia) || !isset($data->data_ocorrecia) || !isset($data->hora_ocorrecia) || !isset($data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, hora_ocorrecia e usuarios_id_usuario son obrigatorios."
            ]);
            break;
        }

        $titulo_ocorrecia = "'" . $conn->real_escape_string($data->titulo_ocorrecia) . "'";
        $descricao_ocorrecia = "'" . $conn->real_escape_string($data->descricao_ocorrecia) . "'";
        $data_ocorrecia = "'" . $conn->real_escape_string($data->data_ocorrecia) . "'";
        $hora_ocorrecia = "'" . $conn->real_escape_string($data->hora_ocorrecia) . "'";

        $id_usuario = intval($data->usuarios_id_usuario);
        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $sql = "INSERT INTO ocorrencias (titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, hora_ocorrecia, usuarios_id_usuario, penalidade) 
                VALUES ($titulo_ocorrecia, $descricao_ocorrecia, $data_ocorrecia, $hora_ocorrecia, $id_usuario, $penalidade)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Ocorrencia rexistrada con éxito"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execución da query: " . $conn->error
            ]);
        }
        break;
    case 'PUT':
        break;

    case 'PATCH':
        break;
}
