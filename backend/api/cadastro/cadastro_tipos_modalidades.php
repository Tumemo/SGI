<?php
require_once "../../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);


if( !isset($data['user_id']) ||  !isset($data['nome_tipo_modalidade']) ){
    echo json_encode([ "success" => false,  "message" => "Parâmetros insuficientes."]);
    exit;
}
$tipo = $data['nome_tipo_modalidade'];

$sql = "INSERT INTO tipos_modalidades (nome_tipo_modalidade) 
        VALUES ('$tipo')";

$res = $conn->query($sql);
if($res){
    echo json_encode([ "success" => true, "message" => "Tipo de modalidade inserida com sucesso.", "insert_id" => $conn->insert_id]);
}else{
    echo json_encode(["success" => false, "message" => "Erro ao inserir.","error" => $conn->error
    ]);
}