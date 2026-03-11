<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id_jogo = isset($_GET['id_jogo']) ? $_GET['id_jogo'] : null;

if ($id_jogo) {
    $sql = "SELECT pontuacoes.id_pontuacao, pontuacoes.nome_pontuacao, pontuacoes.valor_pontuacao, jogos.nome_jogo, usuarios.nome_usuario AS registrado_por 
    FROM pontuacoes 
    INNER JOIN jogos ON jogos.id_jogo = pontuacoes.jogos_id_jogo 
    INNER JOIN usuarios ON usuarios.id_usuario = pontuacoes.usuarios_id_usuario 
    WHERE pontuacoes.jogos_id_jogo = $id_jogo";
} else {
    $sql = "SELECT pontuacoes.id_pontuacao, pontuacoes.nome_pontuacao, pontuacoes.valor_pontuacao, jogos.nome_jogo, usuarios.nome_usuario AS registrado_por 
    FROM pontuacoes 
    INNER JOIN jogos ON jogos.id_jogo = pontuacoes.jogos_id_jogo 
    INNER JOIN usuarios ON usuarios.id_usuario = pontuacoes.usuarios_id_usuario";
}

$res = $conn->query($sql);
$pontuacoes = [];

if($res){
    while($row = $res->fetch_assoc()){
        $pontuacoes[] = $row;
    }
}

echo json_encode($pontuacoes);