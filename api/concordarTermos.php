<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Modules\Acesso\Application\TermosService;
use App\Modules\Acesso\Infrastructure\MysqliTermosRepository;

header('Content-Type: application/json; charset=utf-8');

requerNivel([3]);
iniciarSessao();
$idUsuario = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? 0);
$service = new TermosService(new MysqliTermosRepository($conn));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $result = $service->consultar($idUsuario);
        if ($result === null) {
            echo json_encode(['success' => false, 'message' => 'Aluno não cadastrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => true, ...$result], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $result = $service->aceitar($idUsuario);
        $response = match ($result['status']) {
            'accepted' => [
                'success' => true,
                'message' => 'Termos aceitos com sucesso!',
                'exige_troca_senha' => $result['exige_troca_senha'] ?? false,
            ],
            'already_accepted' => [
                'success' => true,
                'message' => 'Usuário já aceitou os termos.',
                'exige_troca_senha' => $result['exige_troca_senha'] ?? false,
            ],
            'no_edition' => [
                'success' => false,
                'message' => 'Aluno não possui um Interclasse vinculado e não há edição ativa.',
            ],
            default => ['success' => false, 'message' => 'Aluno não cadastrado.'],
        };
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em concordarTermos.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar os termos.'], JSON_UNESCAPED_UNICODE);
}
