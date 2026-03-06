<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$data_json = json_decode(file_get_contents("php://input"), true);

if (!isset($data_json['nome'], $data_json['turno'], $data_json['cat'], $data_json['nome_fantasia'])) {
    echo json_encode(["success" => false, "message" => "Dados incompletos"]);
    exit;
}

$nome = $data_json["nome"];
$turno = $data_json["turno"];
$cat = $data_json["cat"];
$nome_fantasia = $data_json["nome_fantasia"];
$interclase_id = isset($data_json["id_interclasse"]) ? $data_json["id_interclasse"] : null;


$sql = "INSERT INTO turmas (nome_turma, turno_turma, cat_turma, nome_fantasia_turma, interclasses_id_interclasse) 
        VALUES ('$nome', '$turno', '$cat', '$nome_fantasia', '$interclase_id')";

$res = $conn->query($sql);

if ($res) {
    echo json_encode(["success" => true, "message" => "Turma cadastrada com sucesso"]);
} else {
    echo json_encode(["success" => false, "message" => "Erro ao cadastrar turma: " . $conn->error]);
}

$conn->close();
?>