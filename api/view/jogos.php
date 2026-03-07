<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_jogo']) ? $_GET['id_jogo'] : null;
$id_modalidade = isset($_GET['modalidades_id_modalidade']) ? $_GET['modalidades_id_modalidade'] : null;
$id_local = isset($_GET['locais_id_local']) ? $_GET['locais_id_local'] : null;

if ($id) {
    $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.id_jogo = $id";
} elseif ($id_modalidade) {
    $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.modalidades_id_modalidade = $id_modalidade";
} elseif ($id_local) {
    $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.locais_id_local = $id_local";
} else {
    $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local";
}

$res = $conn->query($sql);
$jogos = [];

if($res){
    while($row = $res->fetch_assoc()){
        $jogos[] = $row;
    }
}

echo json_encode($jogos);