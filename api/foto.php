<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'user_id inválido']);
        exit;
    }

    $st = $conn->prepare('SELECT foto_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = \'1\' LIMIT 1');
    $st->bind_param('i', $userId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    $foto = $row['foto_usuario'] ?? '';

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'foto_usuario' => $foto
    ]);
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'mensagem' => 'Não autorizado.']);
        exit;
    }

    $id = (int)$_SESSION['id'];

    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensagem' => 'Erro no upload do arquivo.']);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensagem' => 'Formato inválido. Use JPG, PNG, GIF ou WebP.']);
        exit;
    }

    $nomeArquivo = 'user_' . $id . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/fotosUsuarios';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $destino = $uploadDir . '/' . $nomeArquivo;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao salvar arquivo.']);
        exit;
    }

    $st = $conn->prepare('UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?');
    $st->bind_param('si', $nomeArquivo, $id);
    $st->execute();
    $st->close();

    echo json_encode([
        'success' => true,
        'mensagem' => 'Foto atualizada!',
        'arquivo' => $nomeArquivo
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
