<?php
session_start();
require_once "../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nome'], $data['turno'], $data['cat'], $data['nome_fantasia'])) {
    echo json_encode(["success" => false, "message" => "Dados incompletos"]);
    exit;
}

$nome = $data["nome"];
$turno = $data["turno"];
$cat = $data["cat"];
$nome_fantasia = $data["nome_fantasia"];

$sql = "INSERT INTO turmas (nome_turma, turno_turma, cat_turma, nome_fantasia_turma) VALUES ('$nome', '$turno', '$cat', '$nome_fantasia')";

$stmt = $conn->prepare($sql);

$stmt = $conn->prepare($sql);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Turma cadastrada com sucesso"]);
} else {
    echo json_encode(["success" => false,"message" => "Erro ao cadastrar turma"]);
}

$stmt->close();
$conn->close();