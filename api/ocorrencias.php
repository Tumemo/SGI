<?php
require_once '../config/database.php';
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
        $data = json_decode(file_get_contents("php://input"), true);

        $titulo = trim($data['titulo_ocorrecia']);
        $descricao = trim($data['descricao_ocorrecia']);
        $data_ocorrencia = trim($data['data_ocorrecia']);
        $hora = trim($data['hora_ocorrecia']);
        $usuario = (int) $data['usuarios_id_usuario'];
        $penalidade = (int) $data['penalidade'];


        $sql = "INSERT INTO ocorrencias (ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia,
                         ocorrencias.hora_ocorrecia, ocorrencias.usuarios_id_usuario, ocorrencias.penalidade) VALUES ('$titulo', '$descricao', '$data_ocorrencia', '$hora', $usuario, $penalidade)";

        $res = $conn->query($sql);

        if ($res === TRUE) {
            echo json_encode(["message" => "Ocorrência cadastrada com sucesso!"]);
        } else {
            echo json_encode(["message" => "Erro ao cadastrar ocorrência: " . $conn->error]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
