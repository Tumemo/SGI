<?php
session_start();
require_once "../../config/db.php";
header("Content-Type: application/json");

$id = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;

if ($id) {
    $sql = "SELECT interclasses.id_interclasse, interclasses.nome_interclasse, interclasses.ano_interclasse 
    FROM interclasses 
    WHERE interclasses.id_interclasse = $id";
} else {
    $sql = "SELECT interclasses.id_interclasse, interclasses.nome_interclasse, interclasses.ano_interclasse 
    FROM interclasses";
}

$res = $conn->query($sql);
$interclasse = [];

if($res){
    while($row = $res->fetch_assoc()){
        $interclasse[] = $row;
    }
}

echo json_encode($interclasse);