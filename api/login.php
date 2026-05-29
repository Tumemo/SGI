<?php
session_start();
require_once __DIR__ . '/conexao.php'; 

header('Content-Type: application/json');

// Captura o JSON enviado pelo front-end
$inputData = json_decode(file_get_contents('php://input'), true) ?? [];

// Captura a ação principal
$acao = $inputData['acao'] ?? $_POST['acao'] ?? $_REQUEST['acao'] ?? '';

// --- 1. AÇÃO: LOGIN DE ALUNOS (COMPETIDORES) ---
if ($acao === 'login_competidores') {
    $senha = $inputData['senha_usuario'] ?? '';
    $dataNasc  = $inputData['data_nasc_usuario'] ?? '';

    // Regra RF05: Validação via RA e Data de Nascimento
    $sql = "SELECT * FROM usuarios WHERE senha_usuario = ? AND data_nasc_usuario = ? AND nivel_usuario = '3' AND status_usuario = '1' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $senha, $dataNasc);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario) {
        $_SESSION['id']    = $usuario['id_usuario'];
        $_SESSION['nivel'] = 3;
        $_SESSION['nome']  = $usuario['nome_usuario'];

        echo json_encode([
            'status'   => 'sucesso',
            'redirect' => '../views/src/pages/alunos/home.php' // Rota para pasta de alunos
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'erro', 'mensagem' => 'RA ou Data de Nascimento incorretos.']);
    }
    exit;
}

// --- 2. AÇÃO: LOGIN DE DEMAIS USUÁRIOS (ADMIN/COLAB/MESÁRIO) ---
if ($acao === 'login_gestao') {
    $matricula = $inputData['matricula'] ?? '';
    $senha     = $inputData['senha'] ?? '';

    // Níveis 0, 1 e 2 entram com Matrícula e Senha
    $sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND nivel_usuario IN ('0', '1', '2') AND status_usuario = '1' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $matricula);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha_usuario'])) {
        $_SESSION['id']    = $usuario['id_usuario'];
        $_SESSION['nivel'] = (int)$usuario['nivel_usuario'];
        $_SESSION['nome']  = $usuario['nome_usuario'];

        echo json_encode([
            'status'   => 'sucesso',
            'redirect_admin_colaborador' => $_SESSION['nivel'] === 0 || $_SESSION['nivel'] === 1 ? '../views/src/pages/home.php' : null,
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Matrícula ou Senha incorretos.']);
    }
    exit;
}


echo json_encode(['status' => 'erro', 'mensagem' => 'Ação "' . $acao . '" não reconhecida.']);