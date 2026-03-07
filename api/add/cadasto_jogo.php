<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

$json = json_decode(file_get_contents("php://input"));

// Validando os campos obrigatórios conforme a imagem
if (!isset($json->nome_jogo, $json->status_jogo, $json->modalidades_id_modalidade, $json->locais_id_local)) {
    echo json_encode(["success" => false, "message" => "Dados incompletos"]);
    exit;
}

$nome = $json->nome_jogo;
$data_hoje = date('Y-m-d'); 
$inicio = date('H:i:s');
// Note o nome da coluna conforme a imagem: terminno_jogo (com dois 'n')
$termino = "00:00:00"; 
$status = $json->status_jogo;
$modalidade = (int) $json->modalidades_id_modalidade;
$local = (int) $json->locais_id_local;

// SQL ajustado com os nomes exatos da imagem
$sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, terminno_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) 
        VALUES ('$nome', '$data_hoje', '$inicio', '$termino', '$status', '$modalidade', '$local')";

$res = $conn->query($sql);

if ($res) {
    echo json_encode(["success" => true, "message" => "Cadastro de jogo realizado com sucesso!"]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>