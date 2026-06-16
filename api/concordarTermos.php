<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Sessão expirada ou usuário não autenticado."
    ]);
    exit;
}

$id_usuario = $_SESSION['id'];

$sql = "SELECT u.id_usuario, u.interclasses_id_interclasse, ui.aceito_termo 
        FROM usuarios u
        LEFT JOIN usuarios_has_interclasses ui ON u.id_usuario = ui.usuarios_id_usuario
        WHERE u.id_usuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    if ($usuario['aceito_termo'] === 'sim') {
        echo json_encode([
            "success" => true,
            "message" => "Usuário já aceitou os termos."
        ]);
    } else {
        
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

} else {
    echo json_encode([
        "success" => false,
        "message" => "Aluno não cadastrado."
    ]);
}