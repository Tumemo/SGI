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


$id = isset($_GET['id_modalidade']) ? $_GET['id_modalidade'] : null;

if ($id) {
    $sql = "SELECT modalidades.id_modalidade, modalidades.nome_modalidade, modalidades.categoria_modalidade, modalidades.max_inscrito_modalidade, modalidades.tipos_modalidades_id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM modalidades 
    INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade 
    WHERE modalidades.id_modalidade = $id";
} else {
    $sql = "SELECT modalidades.id_modalidade, modalidades.nome_modalidade, modalidades.categoria_modalidade, modalidades.max_inscrito_modalidade, modalidades.tipos_modalidades_id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade 
    FROM modalidades 
    INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade";
}

$res = $conn->query($sql);
$modalidades = [];

if($res){
    while($row = $res->fetch_assoc()){
        $modalidades[] = $row;
    }
}

echo json_encode($modalidades);