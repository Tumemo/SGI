<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

// if (!isset($_SESSION['user_id'])) {
//     echo json_encode([
//         "success" => false,
//         "message" => "Usuário não autenticado."
//     ]);
//     exit;
// }


$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades 
    WHERE tipos_modalidades.id_tipo_modalidade = $id";
} else {
    $sql = "SELECT tipos_modalidades.id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM tipos_modalidades";
}

$res = $conn->query($sql);
$tipo = [];

if($res){
    while($row = $res->fetch_assoc()){
        $tipo[] = $row;
    }
}

echo json_encode($tipo);