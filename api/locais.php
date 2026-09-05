<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Interclasse\Application\LocalNaoEncontradoException;
use App\Interclasse\Application\LocalService;
use App\Interclasse\Application\LocalVinculadoException;
use App\Interclasse\Infrastructure\MysqliLocalRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new LocalService(new MysqliLocalRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function localPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            echo json_encode([
                'success' => true,
                'data' => $service->listar([
                    'id_local' => (int) ($_GET['id_local'] ?? 0),
                    'disponivel' => (string) ($_GET['disponivel'] ?? ''),
                    'busca' => trim((string) ($_GET['busca'] ?? '')),
                    'id_interclasse' => (int) ($_GET['id_interclasse'] ?? 0),
                ]),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $id = $service->criar(localPayload());
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Local cadastrado com sucesso!',
                'id_local' => $id,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(localPayload());
            echo json_encode(['success' => true, 'message' => 'Local atualizado com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            requerExclusao();
            $payload = localPayload();
            $service->excluir((int) ($payload['id_local'] ?? $_GET['id_local'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Local excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (LocalNaoEncontradoException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Local não encontrado.'], JSON_UNESCAPED_UNICODE);
} catch (LocalVinculadoException $exception) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Não é possível excluir este local pois existem jogos vinculados a ele.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em locais.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar o local.'], JSON_UNESCAPED_UNICODE);
}
