<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

require_once '../config/db.php';
session_start();

// Aceita as chaves de sessão mais comuns para evitar 401 indevido
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Sessão expirada ou usuário não autenticado."
    ]);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

// Consulta dados do usuário
$sql = "SELECT u.id_usuario, u.interclasses_id_interclasse, ui.aceito_termo 
        FROM usuarios u
        LEFT JOIN usuarios_has_interclasses ui 
               ON u.id_usuario = ui.usuarios_id_usuario 
              AND u.interclasses_id_interclasse = ui.interclasses_id_interclasse
        WHERE u.id_usuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Aluno não cadastrado."
    ]);
    exit;
}

$usuario = $result->fetch_assoc();

// SE FOR GET: Apenas checa o aceite
if ($metodo === 'GET') {
    $jaAceitou = ($usuario['aceito_termo'] === 'sim');
    echo json_encode([
        "success" => true,
        "termo_aceito" => $jaAceitou
    ]);
    exit;
}

// SE FOR POST: Processa a gravação
if ($metodo === 'POST') {
    if (empty($usuario['interclasses_id_interclasse'])) {
        echo json_encode([
            "success" => false,
            "message" => "Aluno não possui um Interclasse vinculado."
        ]);
        exit;
    }

    if ($usuario['aceito_termo'] === 'sim') {
        echo json_encode([
            "success" => true,
            "message" => "Usuário já aceitou os termos."
        ]);
        exit;
    }

    $id_interclasse = $usuario['interclasses_id_interclasse'];
    $data_hora_atual = date('Y-m-d H:i:s');
    $status_termo = 'Ativo';

    if (is_null($usuario['aceito_termo'])) {
        $sql_acao = "INSERT INTO usuarios_has_interclasses 
                     (usuarios_id_usuario, interclasses_id_interclasse, dt_hr_aceita, aceito_termo, status_termo) 
                     VALUES (?, ?, ?, 'sim', ?)";
        
        $stmt_acao = $conn->prepare($sql_acao);
        $stmt_acao->bind_param('iiss', $id_usuario, $id_interclasse, $data_hora_atual, $status_termo);
    } else {
        $sql_acao = "UPDATE usuarios_has_interclasses 
                     SET aceito_termo = 'sim', dt_hr_aceita = ?, status_termo = ? 
                     WHERE usuarios_id_usuario = ? AND interclasses_id_interclasse = ?";
        
        $stmt_acao = $conn->prepare($sql_acao);
        $stmt_acao->bind_param('ssii', $data_hora_atual, $status_termo, $id_usuario, $id_interclasse);
    }

    if ($stmt_acao->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Termos aceitos com sucesso!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Erro ao salvar o aceite dos termos no banco de dados."
        ]);
    }
}