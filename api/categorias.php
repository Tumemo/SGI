<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Interclasse\Application\CategoriaInativaException;
use App\Interclasse\Application\CategoriaNaoEncontradaException;
use App\Interclasse\Application\CategoriaService;
use App\Interclasse\Infrastructure\MysqliCategoriaRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new CategoriaService(new MysqliCategoriaRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function categoriaPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

try {
    switch ($method) {
        case 'GET':
            echo json_encode($service->listar([
                'id_categoria' => (int) ($_GET['id_categoria'] ?? 0),
                'id_interclasse' => (int) ($_GET['id_interclasse'] ?? 0),
                'busca' => trim((string) ($_GET['busca'] ?? '')),
            ]), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $id = $service->criar(categoriaPayload());
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Categoria cadastrada com sucesso!',
                'id_categoria' => $id,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            requerEscrita();
            $service->atualizar(categoriaPayload());
            echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            requerExclusao();
            $service->excluir((int) (categoriaPayload()['id_categoria'] ?? $_GET['id_categoria'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Categoria excluída com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (CategoriaNaoEncontradaException $exception) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Categoria não encontrada.'], JSON_UNESCAPED_UNICODE);
} catch (CategoriaInativaException $exception) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Esta categoria está desativada e não pode ser alterada.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em categorias.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a categoria.'], JSON_UNESCAPED_UNICODE);
}
