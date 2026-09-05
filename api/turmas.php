<?php

declare(strict_types=1);

require_once '../config/db.php';
require_once 'auth.php';

use App\Modules\Interclasses\Application\TurmaDuplicadaException;
use App\Modules\Interclasses\Application\TurmaNaoEncontradaException;
use App\Modules\Interclasses\Application\TurmaService;
use App\Modules\Interclasses\Application\TurmaVinculadaException;
use App\Modules\Interclasses\Infrastructure\MysqliTurmaRepository;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$service = new TurmaService(new MysqliTurmaRepository($conn));

/** @return array<string, mixed> */
function turmaPayload(): array
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
            // Compatibilidade: sem edição explícita, a rota historicamente retorna lista vazia.
            if (!isset($_GET['id_interclasse']) || $_GET['id_interclasse'] === '') {
                echo json_encode([], JSON_UNESCAPED_UNICODE);
                break;
            }
            echo json_encode($service->listar([
                'id_interclasse' => (int) $_GET['id_interclasse'],
                'id_turma' => (int) ($_GET['id_turma'] ?? 0),
                'id_categoria' => (int) ($_GET['id_categoria'] ?? 0),
                'turno' => (string) ($_GET['turno'] ?? ''),
                'busca' => trim((string) ($_GET['busca'] ?? '')),
            ]), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $id = $service->criar(turmaPayload());
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Turma cadastrada com sucesso!',
                'id_turma' => $id,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(turmaPayload());
            echo json_encode(['success' => true, 'message' => 'Turma atualizada com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            requerExclusao();
            $payload = turmaPayload();
            $service->excluir((int) ($payload['id_turma'] ?? $_GET['id_turma'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Turma excluída com sucesso.'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (TurmaDuplicadaException) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Já existe uma turma com este nome e período nesta edição do interclasse.',
    ], JSON_UNESCAPED_UNICODE);
} catch (TurmaVinculadaException) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Não é possível excluir esta turma pois existem registros vinculados a ela.',
    ], JSON_UNESCAPED_UNICODE);
} catch (TurmaNaoEncontradaException) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Turma não encontrada.'], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em turmas.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a turma.'], JSON_UNESCAPED_UNICODE);
}
