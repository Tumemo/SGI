<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id_interclasse = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;

if ($id_interclasse) {
    $sql = "SELECT 
                turmas.nome_turma, 
                SUM(pontuacoes.valor_pontuacao) AS total_pontos
            FROM pontuacoes
            INNER JOIN usuarios ON pontuacoes.usuarios_id_usuario = usuarios.id_usuario
            INNER JOIN turmas ON usuarios.turmas_id_turma = turmas.id_turma
            WHERE turmas.interclasses_id_interclasse = $id_interclasse
            GROUP BY turmas.id_turma
            ORDER BY total_pontos DESC";
} else {
    $sql = "SELECT 
                turmas.nome_turma, 
                SUM(pontuacoes.valor_pontuacao) AS total_pontos
            FROM pontuacoes
            INNER JOIN usuarios ON pontuacoes.usuarios_id_usuario = usuarios.id_usuario
            INNER JOIN turmas ON usuarios.turmas_id_turma = turmas.id_turma
            GROUP BY turmas.id_turma
            ORDER BY total_pontos DESC";
}

$res = $conn->query($sql);
$ranking = [];

if($res){
    while($row = $res->fetch_assoc()){
        $ranking[] = $row;
    }
}

echo json_encode($ranking);