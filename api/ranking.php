<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Interclasse\Application\RankingService;
use App\Interclasse\Infrastructure\MysqliRankingRepository;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$service = new RankingService(new MysqliRankingRepository($conn));

/** @return array<string, mixed> */
function rankingPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

requerNivel([0, 1, 2, 3]);

try {
    switch ($method) {
        case 'GET':
            iniciarSessao();
            $nivel = $_SESSION['nivel_usuario'] ?? $_SESSION['nivel'] ?? $_SESSION['usuario_nivel']
                ?? $_SESSION['nivel_acesso'] ?? $_SESSION['perfil'] ?? 99;
            $isAdmin = is_numeric($nivel)
                ? in_array((int) $nivel, [0, 1], true)
                : strtolower((string) $nivel) === 'admin';
            $dados = $service->listar([
                'id_turma' => (int) ($_GET['id_turma'] ?? 0),
                'id_interclasse' => (int) ($_GET['id_interclasse'] ?? 0),
                'id_categoria' => (int) ($_GET['id_categoria'] ?? 0),
                'turno' => (string) ($_GET['turno'] ?? ''),
                'busca' => trim((string) ($_GET['busca'] ?? '')),
            ]);
            if ($dados !== [] && !$isAdmin) {
                $status = $dados[0]['status_interclasse'] ?? 'ativo';
                if (in_array($status, ['ativo', '1', 1, true], true)) {
                    echo json_encode([
                        'bloqueado' => true,
                        'message' => 'O ranking deste interclasse está restrito apenas para os administradores.',
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }
            }
            echo json_encode($dados, JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $updated = $service->atualizar(rankingPayload());
            echo json_encode([
                'success' => true,
                'message' => $updated
                    ? 'Turma atualizada com sucesso!'
                    : 'Nenhuma alteração realizada (dados idênticos ou ID inexistente).',
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em ranking.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar o ranking.'], JSON_UNESCAPED_UNICODE);
}
