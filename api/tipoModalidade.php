<?php
require_once '../config/database.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':

        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if ($id) {
            $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades 
    WHERE tipos_modalidades.id_tipo_modalidade = $id";
        } else {
            $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades";
        }

        $res = $conn->query($sql);
        $tipo = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $tipo[] = $row;
            }
        }

        echo json_encode($tipo);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);


        if (!isset($data['nome_pontuacao'], $data['pontuacao'], $data['jogos_id_jogo'], $data['usuarios_id_usuario'])) {
            echo json_encode(["success" => false, "message" => "Dados incompletos ou JSON inválido"]);
            exit;
        }
        $nome = $data["nome_pontuacao"];
        $pontuacao = (int) $data["pontuacao"];
        $jogo = (int) $data["jogos_id_jogo"];
        $usuario = (int) $data["usuarios_id_usuario"];

        $sql = "INSERT INTO pontuacoes(nome_pontuacao, valor_pontuacao, jogos_id_jogo, usuarios_id_usuario) 
        VALUES('$nome', '$pontuacao', '$jogo', '$usuario')";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode(["success" => true, "message" => "Cadastro de pontuação realizado com sucesso!"]);
        } else {

            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
