<?php

session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Recebe dados enviados em JSON ou via POST
$inputData = json_decode(file_get_contents('php://input'), true);

if (!is_array($inputData)) {
    $inputData = [];
}

$matricula = $inputData['matricula'] ?? ($_POST['matricula'] ?? '');
$senha     = $inputData['senha'] ?? ($_POST['senha'] ?? '');

// Validação dos campos
if (empty($matricula) || empty($senha)) {
    http_response_code(400);

    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Preencha todos os campos.'
    ]);

    exit;
}

// Verifica se existe conexão
if (!isset($conn)) {
    http_response_code(500);

    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Erro de conexão com o banco.'
    ]);

    exit;
}

// Busca usuário
$sql = "
    SELECT *
    FROM usuarios
    WHERE matricula_usuario = ?
      AND status_usuario = '1'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Erro ao preparar consulta.'
    ]);

    exit;
}

$stmt->bind_param("s", $matricula);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Verifica senha
if (
    $usuario &&
    password_verify($senha, $usuario['senha_usuario'])
) {

    $_SESSION['id']        = $usuario['id_usuario'];
    $_SESSION['nivel']     = (int)$usuario['nivel_usuario'];
    $_SESSION['nome']      = $usuario['nome_usuario'];
    $_SESSION['matricula'] = $usuario['matricula_usuario'];

    switch ($_SESSION['nivel']) {

        case 3:
            $destino = '../views/src/pages/alunos/home.php';
            break;

        case 0:
        case 1:
        case 2:
            $destino = '../views/src/pages/home.php';
            break;

        default:
            $destino = '../login.html';
            break;
    }

    echo json_encode([
        'status'   => 'sucesso',
        'redirect' => $destino
    ]);

} else {

    http_response_code(401);

    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Matrícula ou Senha incorretos.'
    ]);
}

$stmt->close();

exit;