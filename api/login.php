<?php

session_start();

header('Content-Type: application/json');

require_once '../config/db.php';

try {

    $dados = json_decode(
        file_get_contents('php://input'),
        true
    );

    $matricula = trim($dados['matricula'] ?? '');
    $senha     = trim($dados['senha'] ?? '');

    if (empty($matricula) || empty($senha)) {

        http_response_code(400);

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Preencha todos os campos.'
        ]);

        exit;
    }

    $sql = "
        SELECT
            id_usuario,
            nome_usuario,
            matricula_usuario,
            senha_usuario,
            nivel_usuario
        FROM usuarios
        WHERE matricula_usuario = ?
        AND status_usuario = '1'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $matricula);

    $stmt->execute();

    $resultado = $stmt->get_result();

    $usuario = $resultado->fetch_assoc();

    if (!$usuario) {

        http_response_code(401);

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Matrícula ou senha inválida.'
        ]);

        exit;
    }

    if (!password_verify(
        $senha,
        $usuario['senha_usuario']
    )) {

        http_response_code(401);

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Matrícula ou senha inválida.'
        ]);

        exit;
    }

    $_SESSION['id_usuario'] =
        $usuario['id_usuario'];

    $_SESSION['nome_usuario'] =
        $usuario['nome_usuario'];

    $_SESSION['nivel_usuario'] =
        $usuario['nivel_usuario'];

    switch ($usuario['nivel_usuario']) {

        case '0':
        case '1':
            $redirect =
                '../views/src/pages/home.php';
            break;

        case '2':
            $redirect =
                '../views/src/pages/mesarios/home.php';
            break;

        case '3':
            $redirect =
                '../views/src/pages/alunos/home.php';
            break;

        default:
            $redirect = '../index.php';
    }

    echo json_encode([
        'status' => 'sucesso',
        'redirect' => $redirect
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro interno.',
        'debug' => $e->getMessage()
    ]);
}