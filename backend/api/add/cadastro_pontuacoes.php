<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

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
?>