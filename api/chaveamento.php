<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        $idModalidade = isset($_GET['id_modalidade']) ? (int) $_GET['id_modalidade'] : 0;
        if ($idModalidade <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da modalidade é obrigatório.'], JSON_UNESCAPED_UNICODE);
            break;
        }
        $arvore = sgi_mm_montar_json_arvore($conn, $idModalidade);
        echo json_encode($arvore, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input') ?: '{}');
        $idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;

        if ($idModalidade <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Informe o ID da modalidade.'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $conn->begin_transaction();
        try {
            $equipes = sgi_mm_buscar_equipes_validadas($conn, $idModalidade);
            if (count($equipes) < 2) {
                throw new RuntimeException('É necessário ao menos duas equipes ativas com competidores vinculados (elenco).');
            }

            $res = sgi_mm_criar_chaveamento_inicial($conn, $idModalidade, $equipes);

            foreach ($res['bye_jogos'] as $idBye) {
                sgi_chaveamento_processar_avanco($conn, (int) $idBye);
            }

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Chaveamento mata-mata gerado.',
                'jogos_criados' => $res['jogos_criados'],
                'bye_inicial' => count($res['bye_jogos']),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $conn->rollback();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
        break;
}
