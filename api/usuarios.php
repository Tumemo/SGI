<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

$acao = isset($_GET['acao']) ? $_GET['acao'] : null;

switch ($method) {
    case 'GET':
        // Lógica de listagem se necessário
        break;

    case 'POST':
        if ($acao == 'cadastrar_competidores') {
            $pasta_json = 'json_turmas/';
            $arquivos = glob($pasta_json . "*.json");
            $total_importado = 0;

            if (count($arquivos) > 0) {
                foreach ($arquivos as $arquivo_caminho) {
                    $conteudo = file_get_contents($arquivo_caminho);
                    $lista_alunos = json_decode($conteudo, true);

                    if (!empty($lista_alunos)) {
                        // Preparamos a query uma única vez fora do loop de alunos para performance
                        $sql = "INSERT INTO usuarios (
                                    matricula_usuario, 
                                    nome_usuario, 
                                    senha_usuario, 
                                    nivel_usuario, 
                                    competidor_usuario, 
                                    mesario_usuario, 
                                    data_nasc_usuario,
                                    turmas_id_turma
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                        if ($stmt = $conn->prepare($sql)) {
                            foreach ($lista_alunos as $aluno) {
                                // Trata a data
                                $dateObj = DateTime::createFromFormat('d/m/Y', $aluno['data_nascimento']);
                                $data_sql = $dateObj ? $dateObj->format('Y-m-d') : null;

                                // Valores padrão para os campos ENUM e Turma
                                $senha = '';
                                $nivel = '0';
                                $competidor = '1';
                                $mesario = '0';

                                // "sssssssi" -> 7 strings e 1 inteiro (i) para a turma
                                $stmt->bind_param(
                                    "sssssssi", 
                                    $aluno['rm'], 
                                    $aluno['nome'], 
                                    $senha, 
                                    $nivel, 
                                    $competidor, 
                                    $mesario, 
                                    $data_sql, 
                                    $id_turma
                                );

                                if ($stmt->execute()) {
                                    $total_importado++;
                                }
                            }
                            $stmt->close();
                        }
                    }
                }
                echo json_encode(["status" => "sucesso", "mensagem" => "Importação concluída. $total_importado alunos cadastrados."]);
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Nenhum arquivo JSON encontrado."]);
            }
        }
        break;

    case 'PUT':
    case 'PATCH':
        break;
}