<?php
require_once "../../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if( !isset($data['user_id'])  ||  !isset($data['nome_jogo']) ||  !isset($data['data_jogo']) ) {
    echo json_encode([ "success" => false,  "message" => "Parâmetros insuficientes."]);
    exit;
}
$nome = trim($data['nome_jogo']);
$data_jogo = $data['data_jogo'];
$inicio = $data['inicio_jogo'] ?? null;
$fim = $data['fim_jogo'] ?? null;
$status = $data['status_jogo'] ?? 'Agendado';
$modalidae = (int) $data['modalidade_jogo'];
$local = (int) $data['local_jogo'];


$sql = "INSERT INTO jogos (jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo,jogos.status_jogo, jogos.modalidades_id_modalidade, jogos.locais_id_local)
 VALUES ('$nome', '$data_jogo', '$inicio', '$status', $modalidae, $local)";

$res = $conn->query($sql);

if($res){
    echo json_encode([ "success" => true, "message" => "jogo inserido com sucesso."
    ]);
}else{
    echo json_encode(["success" => false, "message" => "Erro ao inserir o jogo.","error" => $conn->error
    ]);
}
 
