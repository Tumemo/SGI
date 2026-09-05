<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Modules\Interclasses\Application\ModalidadeNaoEncontradaException;
use App\Modules\Interclasses\Application\ModalidadeService;
use App\Modules\Interclasses\Infrastructure\MysqliModalidadeRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new ModalidadeService(new MysqliModalidadeRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function modalidadePayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            $filters = [
                'id_interclasse' => (int) ($_GET['id_interclasse'] ?? 0),
                'id_modalidade' => (int) ($_GET['id_modalidade'] ?? 0),
                'id_categoria' => (int) ($_GET['id_categoria'] ?? 0),
                'id_tipo_modalidade' => (int) ($_GET['id_tipo_modalidade'] ?? 0),
                'genero' => trim((string) ($_GET['genero'] ?? '')),
                'id_turma' => (int) ($_GET['id_turma'] ?? 0),
            ];
            if (isset($_GET['ano'])) {
                $filters['ano'] = $_GET['ano'];
            }
            echo json_encode($service->listar($filters), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $id = $service->criar(modalidadePayload());
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Modalidade criada!', 'id' => $id, 'id_modalidade' => $id], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(modalidadePayload());
            echo json_encode(['success' => true, 'message' => 'Modalidade atualizada com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            requerEscrita();
            $payload = modalidadePayload();
            $service->excluir((int) ($payload['id_modalidade'] ?? $_GET['id_modalidade'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Modalidade excluída com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (ModalidadeNaoEncontradaException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Modalidade não encontrada.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em modalidades.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a modalidade.'], JSON_UNESCAPED_UNICODE);
}
