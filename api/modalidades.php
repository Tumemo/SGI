<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = isset($_GET['id_modalidade']) ? $_GET['id_modalidade'] : null;

        if ($id) {
            $sql = "SELECT modalidades.id_modalidade, modalidades.nome_modalidade, modalidades.categoria_modalidade, modalidades.max_inscrito_modalidade, modalidades.tipos_modalidades_id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM modalidades 
    INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade 
    WHERE modalidades.id_modalidade = $id";
        } else {
            $sql = "SELECT modalidades.id_modalidade, modalidades.nome_modalidade, modalidades.categoria_modalidade, modalidades.max_inscrito_modalidade, modalidades.tipos_modalidades_id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM modalidades 
    INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade";
        }

        $res = $conn->query($sql);
        $modalidades = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $modalidades[] = $row;
            }
        }

        echo json_encode($modalidades);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_modalidade) || !isset($data->genero_modalidade) || !isset($data->tipos_modalidades_id_tipo_modalidade)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos nome_modalidade, genero_modalidade e tipos_modalidades_id_tipo_modalidade são obrigatórios."
            ]);
            break;
        }

        $nome_modalidade = "'" . $conn->real_escape_string($data->nome_modalidade) . "'";
        $genero_modalidade = "'" . $conn->real_escape_string($data->genero_modalidade) . "'";
        $id_tipo_modalidade = intval($data->tipos_modalidades_id_tipo_modalidade);

        $categoria_modalidade = isset($data->categoria_modalidade) ? "'" . $conn->real_escape_string($data->categoria_modalidade) . "'" : "NULL";
        $max_inscrito_modalidade = isset($data->max_inscrito_modalidade) ? intval($data->max_inscrito_modalidade) : "NULL";

        $sql = "INSERT INTO modalidades (nome_modalidade, genero_modalidade, categoria_modalidade, max_inscrito_modalidade, tipos_modalidades_id_tipo_modalidade) 
                VALUES ($nome_modalidade, $genero_modalidade, $categoria_modalidade, $max_inscrito_modalidade, $id_tipo_modalidade)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Modalidade cadastrada com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execução da query: " . $conn->error
            ]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
