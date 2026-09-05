<?php

declare(strict_types=1);

require_once '../config/db.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/idempotencia.php';

use App\Interclasse\Application\OcorrenciaTurmaNaoEncontradaException;
use App\Interclasse\Application\OcorrenciaTurmaService;
use App\Interclasse\Infrastructure\MysqliOcorrenciaTurmaRepository;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$service = new OcorrenciaTurmaService(new MysqliOcorrenciaTurmaRepository($conn));

/** @return array<string, mixed> */
function ocorrenciaTurmaPayload(): array
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
            $idInterclasse = (int) ($_GET['id_interclasse'] ?? 0);
            if ($idInterclasse <= 0) {
                echo json_encode([], JSON_UNESCAPED_UNICODE);
                break;
            }
            echo json_encode($service->listar([
                'id_interclasse' => $idInterclasse,
                'id_turma' => (int) ($_GET['id_turma'] ?? 0),
            ]), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerNivel([0, 1, 2]);
            $previous = sgi_buscar_resposta_idempotente($conn, 'ocorrencias_turmas.post');
            if ($previous !== null) {
                http_response_code($previous['status']);
                echo json_encode($previous['payload'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $data = ocorrenciaTurmaPayload();
            $activeId = garantirInterclasseAtivo($conn);
            if ((int) ($_SESSION['nivel'] ?? -1) === 2
                && !empty($data['interclasses_id_interclasse'])
                && (int) $data['interclasses_id_interclasse'] !== $activeId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Mesários só podem registrar ocorrências na edição ativa.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            if (!isset($data['usuarios_id_usuario']) && isset($_SESSION['id_usuario'])) {
                $data['usuarios_id_usuario'] = (int) $_SESSION['id_usuario'];
            }
            $id = $service->registrar($data);
            sgi_enviar_resposta_idempotente($conn, 'ocorrencias_turmas.post', 201, [
                'success' => true,
                'message' => 'Ocorrência registrada!',
                'id' => $id,
            ]);
            break;

        case 'DELETE':
            requerNivel([0, 1, 2]);
            garantirInterclasseAtivo($conn);
            $data = ocorrenciaTurmaPayload();
            $service->excluir((int) ($data['id_ocorrencia_turma'] ?? $_GET['id_ocorrencia_turma'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Ocorrência removida!'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (OcorrenciaTurmaNaoEncontradaException) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ocorrência não encontrada.'], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em ocorrencias_turmas.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a ocorrência da turma.'], JSON_UNESCAPED_UNICODE);
}
