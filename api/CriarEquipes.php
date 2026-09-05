<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/equipes_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido."], JSON_UNESCAPED_UNICODE);
    exit;
}

requerEscrita();
$data = json_decode(file_get_contents('php://input') ?: '{}', true);
$idInterclasse = is_array($data) ? (int) ($data['id_interclasse'] ?? 0) : 0;

if ($idInterclasse <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "O ID do interclasse é obrigatório."], JSON_UNESCAPED_UNICODE);
    exit;
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
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Falha ao gerar equipes padrão: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Não foi possível gerar as equipes padrão."], JSON_UNESCAPED_UNICODE);
}
