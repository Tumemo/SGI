<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$sql = "SELECT modalidades.id_modalidade, modalidades.nome_modalidade, modalidades.categoria_modalidade, modalidades.max_inscrito_modalidade,modalidades.tipos_modalidades_id_tipo_modalidade, tipos_modalidades.nome_tipo_modalidade
from modalidades inner join tipos_modalidades on tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade";
$res = $conn->query($sql);
$modalidades = [];

if($res){
    while($row = $res->fetch_assoc()){
        $modalidades[] = $row;
    }
}

echo json_encode($modalidades);
