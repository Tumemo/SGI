<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_turma']) ? $_GET['id_turma'] : null;
$id_interclasse = isset($_GET['interclasses_id_interclasse']) ? $_GET['interclasses_id_interclasse'] : null;

if ($id) {
    $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, turmas.cat_turma, turmas.nome_fantasia_turma, interclasses.nome_interclasse 
    FROM turmas 
    INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse 
    WHERE turmas.id_turma = $id";
} elseif ($id_interclasse) {
    $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, turmas.cat_turma, turmas.nome_fantasia_turma, interclasses.nome_interclasse 
    FROM turmas 
    INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse 
    WHERE turmas.interclasses_id_interclasse = $id_interclasse";
} else {
    $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, turmas.cat_turma, turmas.nome_fantasia_turma, interclasses.nome_interclasse 
    FROM turmas 
    INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse";
}

$res = $conn->query($sql);
$turmas = [];

if($res){
    while($row = $res->fetch_assoc()){
        $turmas[] = $row;
    }
}

echo json_encode($turmas);