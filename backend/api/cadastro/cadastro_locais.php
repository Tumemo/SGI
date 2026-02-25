<?php
require_once "../../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);


if( !isset($data['user_id']) ||  !isset($data['local_nome']) ||  !isset($data['capacidade_local']) ){
    echo json_encode([ "success" => false,  "message" => "Parâmetros insuficientes."]);
    exit;
}

$nome_local = $data['local_nome'];
$disponivel_local = 1; 
$capacidade_local = $data['capacidade_local'];

$sql = "INSERT INTO locais (nome_local, disponivel_local, carga_local) 
        VALUES ('$nome_local', $disponivel_local, $capacidade_local)";

$res = $conn->query($sql);

if($res){
    echo json_encode([ "success" => true, "message" => "Local inserido com sucesso.", "insert_id" => $conn->insert_id
    ]);
}else{
    echo json_encode(["success" => false, "message" => "Erro ao inserir.","error" => $conn->error
    ]);
}


