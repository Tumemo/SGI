<?php
session_start();
require_once __DIR__ . '/conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $tipoLogin = $_POST['tipo_login'] ?? ''; // 'aluno' ou 'staff'

    if ($tipoLogin === 'aluno') {
        // Nível 3: Competidores entram com Data de Nascimento
        $dataNasc = $_POST['data_nascimento'] ?? '';
        $sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND data_nasc_usuario = ? AND nivel_usuario = '3' AND status_usuario = '1' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $matricula, $dataNasc);
    } else {
        // Níveis 0, 1, 2: Entram com Senha
        $senhaInput = $_POST['senha'] ?? '';
        $sql = "SELECT * FROM usuarios WHERE matricula_usuario = ? AND nivel_usuario IN ('0', '1', '2') AND status_usuario = '1' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $matricula);
    }

    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario) {
        $autorizado = ($tipoLogin === 'aluno') ?: password_verify($senhaInput, $usuario['senha_usuario']);

        if ($autorizado) {
            $_SESSION['id'] = $usuario['id_usuario'];
            $_SESSION['nivel'] = (int)$usuario['nivel_usuario'];
            $_SESSION['nome'] = $usuario['nome_usuario'];

            // Redirecionamento baseado no nível
            $destino = match($_SESSION['nivel']) {
                3 => 'src/pages/alunos/dashboard.php',
                default => 'src/pages/colaboradores.php',
            };

            echo json_encode(['status' => 'sucesso', 'redirect' => $destino]);
            exit;
        }
    }
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado.']);
}