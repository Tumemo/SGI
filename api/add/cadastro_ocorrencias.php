<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

$titulo = trim($data['titulo_ocorrecia']);
$descricao = trim($data['descricao_ocorrecia']);
$data_ocorrencia = trim($data['data_ocorrecia']);
$hora = trim($data['hora_ocorrecia']);
$usuario = (int) $data['usuarios_id_usuario'];
$penalidade = (int) $data['penalidade'];


$sql = "INSERT INTO ocorrencias (ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia,
                         ocorrencias.hora_ocorrecia, ocorrencias.usuarios_id_usuario, ocorrencias.penalidade) VALUES ('$titulo', '$descricao', '$data_ocorrencia', '$hora', $usuario, $penalidade)";

$res = $conn->query($sql);

if ($res === TRUE) {
    echo json_encode(["message" => "Ocorrência cadastrada com sucesso!"]);
} else {
    echo json_encode(["message" => "Erro ao cadastrar ocorrência: " . $conn->error]);
}