<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Modules\Interclasses\Application\PontuacaoNaoEncontradaException;
use App\Modules\Interclasses\Application\PontuacaoService;
use App\Modules\Interclasses\Infrastructure\MysqliPontuacaoRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new PontuacaoService(new MysqliPontuacaoRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function pontuacaoPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            echo json_encode($service->ranking(), JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(pontuacaoPayload());
            echo json_encode(['success' => true, 'message' => 'Pontuação atualizada com sucesso.'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (PontuacaoNaoEncontradaException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pontuação não encontrada.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em pontuacaoInterclasse.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a pontuação.'], JSON_UNESCAPED_UNICODE);
}
