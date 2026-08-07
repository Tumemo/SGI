<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/equipes_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido."]);
    exit();
}

requerEscrita();
$data = json_decode(file_get_contents("php://input"));
$idInterclasse = isset($data->id_interclasse) ? (int) $data->id_interclasse : 0;

if ($idInterclasse <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "O ID do interclasse é obrigatório."]);
    exit();
}

try {
    // Garante a Equipe Padrão ("{Modalidade} - 1") de cada turma/modalidade da edição,
    // respeitando o vínculo de categoria (turma e modalidade na mesma categoria).
    $resultado = sgi_gerar_equipes_padrao_interclasse($conn, $idInterclasse);

    echo json_encode([
        "success" => true,
        "message" => "Processamento concluído.",
        "equipes_criadas" => $resultado['criadas'],
        "erros" => $resultado['erros']
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}