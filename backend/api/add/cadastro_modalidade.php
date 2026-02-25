<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if(
    !isset($data['nome_modalidade'])  ||
    $data['nome_modalidade'] === null ||
    !isset($data['genero_modalidade']) ||
    !isset($data['categoria_modalidade']) ||
    !isset($data['max_inscrito_modalidade']) ||
    !isset($data['tipo_modalidade']) ||
    $data['tipo_modalidade'] === null
){
    echo json_encode([
        "success" => false,
        "message" => "Parâmetros insuficientes."
    ]);
    exit;
}

$nome = trim($data['nome_modalidade']);
$genero = $data['genero_modalidade'];
$categoria = $data['categoria_modalidade'];
$max_inscritos = (int) $data['max_inscrito_modalidade'];
$tipo = (int) $data['tipo_modalidade'];

if($genero != "FEM" && $genero != "MASC" && $genero != "MISTO"){
    echo json_encode(["success" => false, "message" => "Gênero inválido"]);
    exit;
}


$sql = "INSERT INTO modalidades 
(nome_modalidade, genero_modalidade, categoria_modalidade, 
max_inscrito_modalidade, tipos_modalidades_id_tipo_modalidade) 
VALUES 
('$nome', '$genero', '$categoria', $max_inscritos, $tipo)";


$res = $conn->query($sql);

if($res){
    echo json_encode([ "success" => true, "message" => "Modalidade inserido com sucesso."
    ]);
}else{
    echo json_encode(["success" => false, "message" => "Erro ao inserir.","error" => $conn->error
    ]);
}