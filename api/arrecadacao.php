<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Modules\Interclasses\Application\ArrecadacaoHistoricoJaRemovidoException;
use App\Modules\Interclasses\Application\ArrecadacaoHistoricoNaoEncontradoException;
use App\Modules\Interclasses\Application\ArrecadacaoService;
use App\Modules\Interclasses\Infrastructure\MysqliArrecadacaoRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new ArrecadacaoService(new MysqliArrecadacaoRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}


requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function arrecadacaoPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            echo json_encode(
                $service->listar((int) ($_GET['id_interclasse'] ?? 0)),
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'POST':
            requerEscrita();
            iniciarSessao();
            $service->adicionarLote(arrecadacaoPayload(), (int) ($_SESSION['id'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Pontuações somadas com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            requerEscrita();
            $payload = arrecadacaoPayload();
            $service->remover(
                (int) ($payload['id_historico'] ?? 0),
                (int) ($payload['id_interclasse'] ?? 0),
            );
            echo json_encode(['success' => true, 'message' => 'Registro removido e pontos revertidos com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (ArrecadacaoHistoricoNaoEncontradoException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Registro não encontrado.'], JSON_UNESCAPED_UNICODE);
} catch (ArrecadacaoHistoricoJaRemovidoException $exception) {
    echo json_encode(['success' => false, 'message' => 'Este registro já foi removido anteriormente.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em arrecadacao.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a arrecadação.'], JSON_UNESCAPED_UNICODE);
}
