<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Nenhum JSON recebido"]);
    exit;
}

$modalidade = (int) $data["modalidade_id_modalidade"];
$usuario = (int) $data["usuarios_id_usuario1"];

$sql = "INSERT INTO equipes(modalidade_id_modalidade, usuarios_id_usuario) 
        VALUES('$modalidade', '$usuario')";

$res = $conn->query($sql);

if ($res) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>