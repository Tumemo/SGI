<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$inputData = json_decode(file_get_contents('php://input'), true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

$novaSenha = (string) ($inputData['nova_senha'] ?? '');
$confirmarSenha = (string) ($inputData['confirmar_senha'] ?? '');

if (mb_strlen($novaSenha) < 6) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres.']);
    exit;
}

if ($novaSenha !== $confirmarSenha) {
    echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
    exit;
}

if ($novaSenha === '123') {
    echo json_encode(['success' => false, 'message' => 'Escolha uma senha diferente da senha padrão.']);
    exit;
}

$senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE usuarios SET senha_usuario = ? WHERE id_usuario = ? AND status_usuario = '1'");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro interno ao alterar a senha.']);
    exit;
}
$stmt->bind_param('si', $senhaHash, $id_usuario);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['exige_troca_senha'] = false;
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Não foi possível alterar a senha. Tente novamente.']);
}
$stmt->close();
