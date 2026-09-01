<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/includes/interclasse_helper.php';
require_once __DIR__ . '/includes/cache_offline.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$inputData = json_decode(file_get_contents('php://input'), true) ?? [];

$matricula = $inputData['matricula'] ?? $_POST['matricula'] ?? '';
$senha     = $inputData['senha'] ?? $_POST['senha'] ?? '';

if (empty($matricula) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
    exit;
}

// 1. Contas internas não pertencem a uma edição e devem continuar tendo
// prioridade. Para competidores, a matrícula pode existir em mais de uma
// edição; escolhemos primeiro a linha da edição ativa.
$idInterclasseAtivo = buscarInterclasseAtivo($conn);
$sql = "SELECT u.*
        FROM usuarios u
        WHERE u.matricula_usuario = ?
          AND u.status_usuario = '1'
        ORDER BY
            CASE
                WHEN u.nivel_usuario IN ('0', '1', '2') THEN 0
                WHEN u.nivel_usuario = '3'
                     AND u.interclasses_id_interclasse = ? THEN 1
                WHEN u.nivel_usuario = '3' THEN 2
                ELSE 3
            END,
            u.id_usuario DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não foi possível processar o login.']);
    exit;
}
$idInterclasseAtivo = $idInterclasseAtivo ?? 0;
$stmt->bind_param('si', $matricula, $idInterclasseAtivo);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Verifica se o usuário existe e se a senha (criptografada) é válida
if ($usuario && password_verify($senha, $usuario['senha_usuario'])) {
    // Novo PHPSESSID e namespace opaco por usuário. A chave do IndexedDB não
    // expõe a sessão e permanece disponível ao mesmo operador após um novo
    // login, para que uma fila offline pendente não fique inacessível.
    session_regenerate_id(true);
    $_SESSION = [];

    $_SESSION['logado']       = true;
    $_SESSION['id']           = $usuario['id_usuario'];
    $_SESSION['id_usuario']   = $usuario['id_usuario'];
    $_SESSION['nivel']        = (int)$usuario['nivel_usuario'];
    $_SESSION['nome']         = $usuario['nome_usuario'];
    $_SESSION['matricula']    = $usuario['matricula_usuario'];
    $_SESSION['foto_usuario'] = $usuario['foto_usuario'] ?? null;
    sgi_definir_chave_cache_offline_usuario((int) $usuario['id_usuario'], (string) $usuario['senha_usuario']);

    // Mesário (nível 2) só opera na edição ativa no momento: fixa o id na
    // sessão para orientar o redirecionamento e as verificações de operação.
    if ($_SESSION['nivel'] === 2) {
        $_SESSION['id_interclasse'] = buscarInterclasseAtivo($conn);
    } elseif ($_SESSION['nivel'] === 3) {
        $_SESSION['id_interclasse'] = (int)($usuario['interclasses_id_interclasse'] ?? 0);
    }

    // Alunos (nível 3) que ainda usam a senha padrão precisam trocar no primeiro acesso
    $_SESSION['exige_troca_senha'] = ((int)$usuario['nivel_usuario'] === 3 && password_verify('123', $usuario['senha_usuario']));

    // 3. Define o redirecionamento com base no nível de usuário retornado do banco
    $destino = match($_SESSION['nivel']) {
        3       => '../views/src/pages/alunos/home.php',        // Competidores
        0, 1, 2 => '../views/src/pages/home.php',               // Admin, Colaborador, Mesário
        default => '../views/index.php'
    };

    echo json_encode([
        'status'   => 'sucesso',
        'redirect' => $destino
    ]);
} else {
    // Erro genérico por segurança (não diz se o que está errado é a senha ou a matrícula)
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Matrícula ou Senha incorretos.']);
}
exit;
