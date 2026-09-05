<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Modules\Interclasses\Application\TipoModalidadeNaoEncontradoException;
use App\Modules\Interclasses\Application\TipoModalidadeService;
use App\Modules\Interclasses\Infrastructure\MysqliTipoModalidadeRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new TipoModalidadeService(new MysqliTipoModalidadeRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function tipoModalidadePayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            echo json_encode($service->listar([
                'id_tipo_modalidade' => (int) ($_GET['id_tipo_modalidade'] ?? 0),
                'busca' => trim((string) ($_GET['busca'] ?? '')),
            ]), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $id = $service->criar(tipoModalidadePayload());
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de modalidade cadastrado com sucesso!',
                'id_tipo_modalidade' => $id,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(tipoModalidadePayload());
            echo json_encode(['success' => true, 'message' => 'Tipo de modalidade atualizado com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (TipoModalidadeNaoEncontradoException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tipo de modalidade não encontrado.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em tipoModalidade.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar o tipo de modalidade.'], JSON_UNESCAPED_UNICODE);
}
