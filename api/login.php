<?php
session_start();

require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$dados = json_decode(file_get_contents('php://input'), true);

$matricula = $dados['matricula_usuario'] ?? null;
$senha = $dados['senha_usuario'] ?? null;

if (!$matricula || !$senha) {
    echo json_encode(["erro" => "Matrícula e senha são obrigatórios."]);
    exit;
}

try {
    $sql = "SELECT * FROM Perfil WHERE matricula_usuario = :matricula LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':matricula', $matricula);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['senha_usuario'] === $senha) {
        
        $_SESSION['logado'] = true;
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
        $_SESSION['nivel_usuario'] = $usuario['nivel_usuario']; 

        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Login realizado com sucesso!",
            "dados" => [
                "id" => $usuario['id_usuario'],
                "nome" => $usuario['nome_usuario'],
                "nivel" => $usuario['nivel_usuario'],
                "sigla" => $usuario['sigla_usuario']
            ]
        ]);
    } else {
        echo json_encode([
            "sucesso" => false, 
            "erro" => "Matrícula ou senha incorretos."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(["erro" => "Erro no servidor: " . $e->getMessage()]);
}