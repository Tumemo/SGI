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
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["success" => false, "message" => "Nenhum JSON recebido"]);
            exit;
        }

        $modalidade = (int) $data["modalidade_id_modalidade"];
        $usuario = (int) $data["usuarios_id_usuario1"];

        $sql = "INSERT INTO equipes(modalidade_id_modalidade, usuarios_id_usuario) 
        VALUES('$modalidade', '$usuario')";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
