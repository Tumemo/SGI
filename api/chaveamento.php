<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';
require_once __DIR__ . '/includes/individual_engine.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$data = json_decode(file_get_contents('php://input') ?: '{}');
$tipoModalidade = $data->tipo_modalidade ?? ($_GET['tipo_modalidade'] ?? null);

if ($tipoModalidade === 'individual') {
    switch ($method) {
        case 'GET':
            $idModalidade = isset($_GET['id_modalidade']) ? (int) $_GET['id_modalidade'] : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da modalidade é obrigatório.'], JSON_UNESCAPED_UNICODE);
                break;
            }

            $acao = $_GET['acao'] ?? 'ranking';

            if ($acao === 'participantes') {
                // Retorna os participantes aptos para salvar offline
                $participantes = sgi_ind_buscar_participantes($conn, $idModalidade);
                echo json_encode(['success' => true, 'participantes' => $participantes], JSON_UNESCAPED_UNICODE);
            } else {
                $resultado = sgi_ind_montar_json_ranking($conn, $idModalidade);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'POST':
            // Na arquitetura Offline-First, o POST entrega os dados limpos para preenchimento local
            $idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Informe o ID da modalidade.'], JSON_UNESCAPED_UNICODE);
                break;
            }

            try {
                $participantes = sgi_ind_buscar_participantes($conn, $idModalidade);
                echo json_encode([
                    'success' => true,
                    'message' => 'Carga de participantes gerada para processamento offline.',
                    'id_modalidade' => $idModalidade,
                    'participantes' => $participantes,
                ], JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            break;
    }
} else {

    switch ($method) {
        case 'GET':
            $idModalidade = isset($_GET['id_modalidade']) ? (int) $_GET['id_modalidade'] : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da modalidade é obrigatório.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $acao = $_GET['acao'] ?? 'arvore';
            if ($acao === 'historico') {
                $resultado = sgi_mm_montar_historico($conn, $idModalidade);
            } elseif ($acao === 'classificacao') {
                $resultado = sgi_mm_montar_historico($conn, $idModalidade);
                unset($resultado['confrontos']);
            } else {
                $resultado = sgi_mm_montar_json_arvore($conn, $idModalidade);
            }
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            $idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;

            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Informe o ID da modalidade.'], JSON_UNESCAPED_UNICODE);
                break;
            }

            try {
                // 1. Busca equipes validadas no banco de dados (Apenas Leitura)
                $equipes = sgi_mm_buscar_equipes_validadas($conn, $idModalidade);
                if (count($equipes) < 2) {
                    throw new RuntimeException('É necessário ao menos duas equipes ativas com competidores vinculados.');
                }

                // 2. Retorna os dados para hidratação offline do cliente
                echo json_encode([
                    'success' => true,
                    'message' => 'Carga de equipes obtida com sucesso. Gerenciamento de chaveamento liberado.',
                    'id_modalidade' => $idModalidade,
                    'total_equipes' => count($equipes),
                    'equipes' => $equipes,
                ], JSON_UNESCAPED_UNICODE);

            } catch (Throwable $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            break;
    }
}