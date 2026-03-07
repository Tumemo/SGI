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
    $sql = "SELECT usuarios.id_usuario, usuarios.sigla_usuario, usuarios.matricula_usuario, usuarios.nome_usuario, usuarios.nivel_usuario, usuarios.competidor_usuario, usuarios.mesario_usuario, usuarios.genero_usuario, usuarios.data_nasc_usuario, turmas.nome_turma 
    FROM usuarios 
    INNER JOIN turmas ON turmas.id_turma = usuarios.turmas_id_turma 
    WHERE usuarios.id_usuario = $id";
} else {
    $sql = "SELECT usuarios.id_usuario, usuarios.sigla_usuario, usuarios.matricula_usuario, usuarios.nome_usuario, usuarios.nivel_usuario, usuarios.competidor_usuario, usuarios.mesario_usuario, usuarios.genero_usuario, usuarios.data_nasc_usuario, turmas.nome_turma 
    FROM usuarios 
    INNER JOIN turmas ON turmas.id_turma = usuarios.turmas_id_turma";
}

$res = $conn->query($sql);
$usuarios = [];

if($res){
    while($row = $res->fetch_assoc()){
        $usuarios[] = $row;
    }
}

echo json_encode($usuarios);