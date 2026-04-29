<?php
session_start();
require_once '../config/db.php'; 
header('Content-Type: application/json; charset=utf-8');

// Recebe os dados do POST (JSON)
$data = json_decode(file_get_contents('php://input'));

$data_nasc_usuario = $data->data_nasc_usuario ?? null;
$senhaDigitada = $data->senha_usuario ?? null; // Senha que o usuário escreveu

if (!$data_nasc_usuario || !$senhaDigitada) {
    echo json_encode(["sucesso" => false, "erro" => "Campos obrigatórios vazios."]);
    exit;
}

try {
    // Buscamos o usuário pela matrícula (RA/RM)
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE data_nasc_usuario = ? LIMIT 1");
    $stmt->bind_param("s", $data_nasc_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    // LÓGICA DE VALIDAÇÃO DO HASH
    // password_verify compara o texto puro ($senhaDigitada) com o hash do banco ($usuario['senha_usuario'])
    if ($usuario && password_verify($senhaDigitada, $usuario['senha_usuario'])) {
        
        // Determina o tipo de acesso com base nas colunas da sua imagem
        $perfil = [
            "admin"      => (int)$usuario['nivel_usuario'] > 0,
            "mesario"    => (int)$usuario['mesario_usuario'] === 1,
            "competidor" => (int)$usuario['competidor_usuario'] === 1
        ];

        // Salva na sessão para proteger outras páginas
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nivel']      = $usuario['nivel_usuario'];

        echo json_encode([
            "sucesso" => true,
            "permissoes" => $perfil, // O Front-end usará isso para redirecionar
            "dados" => [
                "nome" => $usuario['nome_usuario'],
                "sigla" => $usuario['sigla_usuario']
            ]
        ]);
    } else {
        echo json_encode(["sucesso" => false, "erro" => "Dados inválidos."]);
    }
} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "erro" => "Erro no servidor."]);
}