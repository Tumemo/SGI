<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id_interclasse = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;

if ($id_interclasse) {
    $sql = "SELECT 
        (SELECT COUNT(*) FROM turmas WHERE interclasses_id_interclasse = $id_interclasse) AS total_turmas,
        (SELECT COUNT(*) FROM usuarios INNER JOIN turmas ON usuarios.turmas_id_turma = turmas.id_turma WHERE turmas.interclasses_id_interclasse = $id_interclasse) AS total_usuarios,
        (SELECT COUNT(*) FROM equipes INNER JOIN turmas ON equipes.turmas_id_turma = turmas.id_turma WHERE turmas.interclasses_id_interclasse = $id_interclasse) AS total_equipes,
        (SELECT COUNT(*) FROM jogos 
            INNER JOIN modalidades ON jogos.modalidades_id_modalidade = modalidades.id_modalidade 
            INNER JOIN equipes ON equipes.modalidades_id_modalidade = modalidades.id_modalidade
            INNER JOIN turmas ON equipes.turmas_id_turma = turmas.id_turma
            WHERE turmas.interclasses_id_interclasse = $id_interclasse) AS total_jogos_relacionados,
        (SELECT nome_interclasse FROM interclasses WHERE id_interclasse = $id_interclasse) AS nome_evento";
} else {
    $sql = "SELECT 
        0 AS total_turmas, 
        0 AS total_usuarios, 
        0 AS total_equipes, 
        0 AS total_jogos_relacionados, 
        'Nenhuma interclasse selecionada' AS nome_evento";
}

$res = $conn->query($sql);
$dashboard = [];

if($res){
    $dashboard = $res->fetch_assoc();
}

echo json_encode([
    "success" => true,
    "data" => $dashboard
]);