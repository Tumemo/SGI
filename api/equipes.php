<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':

        $id = isset($_GET['id_equipe']) ? $_GET['id_equipe'] : null;
        $id_turma = isset($_GET['turmas_id_turma']) ? $_GET['turmas_id_turma'] : null;

        if ($id) {
            $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE equipes.id_equipe = $id";
        } elseif ($id_turma) {
            $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE equipes.turmas_id_turma = $id_turma";
        } else {
            $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma";
        }

        $res = $conn->query($sql);
        $equipes = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $equipes[] = $row;
            }
        }

        echo json_encode($equipes);

        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->modalidades_id_modalidade) || !isset($data->usuarios_id_usuario1) || !isset($data->turmas_id_turma)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos modalidades_id_modalidade, usuarios_id_usuario1 e turmas_id_turma são obrigatórios."
            ]);
            break;
        }

        $id_modalidade = intval($data->modalidades_id_modalidade);
        $id_usuario = intval($data->usuarios_id_usuario1);
        $id_turma = intval($data->turmas_id_turma);

        $sql = "INSERT INTO equipes (modalidades_id_modalidade, usuarios_id_usuario1, turmas_id_turma) VALUES ($id_modalidade, $id_usuario, $id_turma)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Equipa cadastrada com sucesso"
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
