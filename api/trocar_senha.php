<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/auth.php';

use App\Autenticacao\Application\SenhaService;
use App\Autenticacao\Infrastructure\MysqliSenhaRepository;

header('Content-Type: application/json; charset=utf-8');

/** @return array<string, mixed> */
function trocaSenhaPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

requerNivel([0, 1, 2, 3]);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

iniciarSessao();
$idUsuario = (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
$payload = trocaSenhaPayload();

try {
    (new SenhaService(new MysqliSenhaRepository($conn)))->trocar(
        $idUsuario,
        (string) ($payload['nova_senha'] ?? ''),
        (string) ($payload['confirmar_senha'] ?? ''),
    );
    $_SESSION['exige_troca_senha'] = false;
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!'], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code($idUsuario > 0 ? 200 : 401);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em trocar_senha.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível alterar a senha. Tente novamente.'], JSON_UNESCAPED_UNICODE);
}
