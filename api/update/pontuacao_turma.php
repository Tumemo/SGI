<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'));

$id_turma = isset($data->id_turma) ? $data->id_turma : null;
$pontos_ganhos = isset($data->pontos) ? intval($data->pontos) : 0;

if (!$id_turma) {
    echo json_encode([
        "success" => false, 
        "message" => "ID da turma não informado."
    ]);
    exit;
}

$sql = "UPDATE turmas SET pontos_turma = pontos_turma + $pontos_ganhos WHERE id_turma = $id_turma";

if ($conn->query($sql)) {
    echo json_encode([
        "success" => true, 
        "message" => "Pontuação da turma atualizada com sucesso!",
        "pontos_adicionados" => $pontos_ganhos
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Erro ao atualizar: " . $conn->error
    ]);
}