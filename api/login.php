<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$inputData = json_decode(file_get_contents('php://input'), true) ?? [];

$matricula = $inputData['matricula'] ?? $_POST['matricula'] ?? '';
$senha     = $inputData['senha'] ?? $_POST['senha'] ?? '';

if (empty($matricula) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
    exit;
}

// 1. Busca qualquer usuário ativo que possua a matrícula informada
$sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND status_usuario = '1' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $matricula);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// 2. Verifica se o usuário existe e se a senha (criptografada) é válida
if ($usuario && password_verify($senha, $usuario['senha_usuario'])) {
    
    // Grava os dados genéricos na sessão
    $_SESSION['id']    = $usuario['id_usuario'];
    $_SESSION['nivel'] = (int)$usuario['nivel_usuario'];
    $_SESSION['nome']  = $usuario['nome_usuario'];

    // 3. Define o redirecionamento com base no nível de usuário retornado do banco
    $destino = match($_SESSION['nivel']) {
        3       => '../views/src/pages/alunos/home.php', // Competidores
        0, 1    => '../views/src/pages/home.php',        // Admin e Colaboradores
        2       =>        '../views/src/pages/mesarios/home.php', // Mesários
        default => '../login.html'
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