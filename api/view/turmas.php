<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_turma']) ? $_GET['id_turma'] : null;
$id_interclasse = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;
$ver_ranking = isset($_GET['ranking']) ? true : false;

// Base da Query com todas as colunas necessárias, incluindo a nova 'pontos_turma'
$sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, turmas.cat_turma, 
               turmas.nome_fantasia_turma, turmas.pontos_turma, interclasses.nome_interclasse 
        FROM turmas 
        INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse";

// Mantendo as opções de filtro anteriores
if ($id) {
    $sql .= " WHERE turmas.id_turma = $id";
} elseif ($id_interclasse) {
    $sql .= " WHERE turmas.interclasses_id_interclasse = $id_interclasse";
}

// Adicionando a nova opção de ordenação por ranking (maior pontuação primeiro)
if ($ver_ranking) {
    $sql .= " ORDER BY turmas.pontos_turma DESC";
}

$res = $conn->query($sql);
$turmas = [];

if($res){
    while($row = $res->fetch_assoc()){
        $turmas[] = $row;
    }
}

echo json_encode($turmas);