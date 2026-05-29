<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$inputData = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = $inputData['acao'] ?? $_POST['acao'] ?? $_REQUEST['acao'] ?? '';

// --- LOGIN COMPETIDORES (Nível 3) ---
if ($acao === 'login_competidores') {
    $matricula = $inputData['matricula'] ?? '';
    $dataNasc  = $inputData['data_nascimento'] ?? '';

    $sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND data_nasc_usuario = ? AND nivel_usuario = '3' AND status_usuario = '1' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $matricula, $dataNasc);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario) {
        $_SESSION['id']    = $usuario['id_usuario'];
        $_SESSION['nivel'] = 3;
        $_SESSION['nome']  = $usuario['nome_usuario'];

        echo json_encode([
            'status'   => 'sucesso',
            'redirect' => '../views/src/pages/alunos/home.php' // Pasta exclusiva Alunos
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'erro', 'mensagem' => 'RA ou Data de Nascimento incorretos.']);
    }
    exit;
}

// --- LOGIN GESTÃO (Níveis 0, 1, 2) ---
if ($acao === 'login_gestao') {
    $matricula = $inputData['matricula'] ?? '';
    $senha     = $inputData['senha'] ?? '';

    $sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND nivel_usuario IN ('0', '1', '2') AND status_usuario = '1' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $matricula);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha_usuario'])) {
        $_SESSION['id']    = $usuario['id_usuario'];
        $_SESSION['nivel'] = (int)$usuario['nivel_usuario'];
        $_SESSION['nome']  = $usuario['nome_usuario'];

        // REDIRECIONAMENTO POR NÍVEL ESPECÍFICO
        $destino = match($_SESSION['nivel']) {
            0, 1    => '../views/src/pages/home.php',      // Admin e Colaboradores
            2       => '../views/src/pages/mesario.php',   // Mesários (página específica)
            default => '../login.html'
        };

        echo json_encode([
            'status'   => 'sucesso',
            'redirect' => $destino
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Matrícula ou Senha incorretos.']);
    }
    exit;
}