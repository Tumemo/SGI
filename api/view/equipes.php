<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_equipe']) ? $_GET['id_equipe'] : null;
$id_turma = isset($_GET['turmas_id_turma']) ? $_GET['turmas_id_turma'] : null;

if ($id) {
    $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE equipes.id_equipe = $id";
} elseif ($id_turma) {
    $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma 
    WHERE equipes.turmas_id_turma = $id_turma";
} else {
    $sql = "SELECT equipes.id_equipe, modalidades.nome_modalidade, usuarios.nome_usuario AS representante, turmas.nome_turma 
    FROM equipes 
    INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
    INNER JOIN usuarios ON usuarios.id_usuario = equipes.usuarios_id_usuario1 
    INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma";
}

$res = $conn->query($sql);
$equipes = [];

if($res){
    while($row = $res->fetch_assoc()){
        $equipes[] = $row;
    }
}

echo json_encode($equipes);