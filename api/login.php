<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/cache_offline.php';

use App\Shared\Http\SessionManager;
use App\Shared\Http\CsrfGuard;

SessionManager::start();

use App\Modules\Acesso\Application\LoginService;
use App\Modules\Acesso\Infrastructure\MysqliInterclasseRepository;
use App\Modules\Acesso\Infrastructure\MysqliUsuarioRepository;

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$inputData = json_decode(file_get_contents('php://input'), true) ?? [];
$matricula = trim((string) ($inputData['matricula'] ?? $_POST['matricula'] ?? ''));
$senha = (string) ($inputData['senha'] ?? $_POST['senha'] ?? '');

if ($matricula === '' || $senha === '') {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
    exit;
}

try {
    $service = new LoginService(
        new MysqliUsuarioRepository($conn),
        new MysqliInterclasseRepository($conn),
    );
    $authenticated = $service->autenticar($matricula, $senha);
} catch (Throwable $exception) {
    error_log('Falha no login: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não foi possível processar o login.']);
    exit;
}

if ($authenticated === null) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Matrícula ou Senha incorretos.']);
    exit;
}

$usuario = $authenticated['usuario'];
$nivel = (int) $usuario['nivel_usuario'];

session_regenerate_id(true);
$_SESSION = [];
$_SESSION['logado'] = true;
$_SESSION['id'] = (int) $usuario['id_usuario'];
$_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
$_SESSION['nivel'] = $nivel;
$_SESSION['nome'] = $usuario['nome_usuario'];
$_SESSION['matricula'] = $usuario['matricula_usuario'];
$_SESSION['foto_usuario'] = $usuario['foto_usuario'] ?? null;
sgi_definir_chave_cache_offline_usuario((int) $usuario['id_usuario'], (string) $usuario['senha_usuario']);

if ($nivel === 2) {
    $_SESSION['id_interclasse'] = $authenticated['interclasse_ativo'];
} elseif ($nivel === 3) {
    $_SESSION['id_interclasse'] = (int) ($usuario['interclasses_id_interclasse'] ?? 0);
}

$_SESSION['exige_troca_senha'] = $authenticated['exige_troca_senha'];

$destino = match ($nivel) {
    3 => '../views/src/pages/alunos/home.php',
    0, 1, 2 => '../views/src/pages/home.php',
    default => '../views/index.php',
};

echo json_encode([
    'status' => 'sucesso',
    'redirect' => $destino,
    'csrf_token' => CsrfGuard::token(),
]);
