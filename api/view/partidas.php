<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_partida']) ? $_GET['id_partida'] : null;
$id_jogo = isset($_GET['jogos_id_jogo']) ? $_GET['jogos_id_jogo'] : null;
$id_equipe = isset($_GET['equipes_id_equipe']) ? $_GET['equipes_id_equipe'] : null;

if ($id) {
    $sql = "SELECT partidas.id_partida, partidas.resultado__partida, jogos.nome_jogo, equipes.id_equipe, turmas.nome_turma 
    FROM partidas 
    INNER JOIN jogos ON jogos.id_jogo = partidas.jogos_id_jogo 
    INNER JOIN equipes ON equipes.id_equipe = partidas.equipes_id_equipe 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE partidas.id_partida = $id";
} elseif ($id_jogo) {
    $sql = "SELECT partidas.id_partida, partidas.resultado__partida, jogos.nome_jogo, equipes.id_equipe, turmas.nome_turma 
    FROM partidas 
    INNER JOIN jogos ON jogos.id_jogo = partidas.jogos_id_jogo 
    INNER JOIN equipes ON equipes.id_equipe = partidas.equipes_id_equipe 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE partidas.jogos_id_jogo = $id_jogo";
} elseif ($id_equipe) {
    $sql = "SELECT partidas.id_partida, partidas.resultado__partida, jogos.nome_jogo, equipes.id_equipe, turmas.nome_turma 
    FROM partidas 
    INNER JOIN jogos ON jogos.id_jogo = partidas.jogos_id_jogo 
    INNER JOIN equipes ON equipes.id_equipe = partidas.equipes_id_equipe 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE partidas.equipes_id_equipe = $id_equipe";
} else {
    $sql = "SELECT partidas.id_partida, partidas.resultado__partida, jogos.nome_jogo, equipes.id_equipe, turmas.nome_turma 
    FROM partidas 
    INNER JOIN jogos ON jogos.id_jogo = partidas.jogos_id_jogo 
    INNER JOIN equipes ON equipes.id_equipe = partidas.equipes_id_equipe 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma";
}

$res = $conn->query($sql);
$partidas = [];

if($res){
    while($row = $res->fetch_assoc()){
        $partidas[] = $row;
    }
}

echo json_encode($partidas);