<?php
session_start();
require_once "../config/db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Usuário não autenticado."
    ]);
    exit;
}

$sql = "SELECT nome_local, disponivel_local, carga_local FROM locais";
$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao buscar locais.",
        "error" => $conn->error
    ]);
    exit;
}

$results = [];

while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode([
    "success" => true,
    "message" => count($results) > 0 
        ? "Locais exibidos com sucesso." 
        : "Nenhum local encontrado.",
    "data" => $results
]);