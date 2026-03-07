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


$id_usuario = isset($_GET['usuarios_id_usuario']) ? $_GET['usuarios_id_usuario'] : null;

if ($id_usuario) {
    $sql = "SELECT ocorrencias.id_ocorrecia, ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia, ocorrencias.hora_ocorrecia, usuarios.nome_usuario, ocorrencias.penalidade 
    FROM ocorrencias 
    INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
    WHERE ocorrencias.usuarios_id_usuario = $id_usuario";
} else {
    $sql = "SELECT ocorrencias.id_ocorrecia, ocorrencias.titulo_ocorrecia, ocorrencias.descricao_ocorrecia, ocorrencias.data_ocorrecia, ocorrencias.hora_ocorrecia, usuarios.nome_usuario, ocorrencias.penalidade 
    FROM ocorrencias 
    INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario";
}

$res = $conn->query($sql);
$ocorrencias = [];

if($res){
    while($row = $res->fetch_assoc()){
        $ocorrencias[] = $row;
    }
}

echo json_encode($ocorrencias);