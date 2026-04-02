<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true);

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $input_data['acao'] ?? $_REQUEST['acao'] ?? ''; 

// Função auxiliar para cadastrar os competidores do JSON local
function importarCompetidores($conn) {
    $caminho_json = 'json_turmas/info_alunos.json';
    
    if (!file_exists($caminho_json)) {
        return ["status" => "erro", "mensagem" => "Arquivo JSON de alunos não encontrado"];
    }

    $alunos = json_decode(file_get_contents($caminho_json), true);
    $sucessos = 0; $ignorados = 0; $erros = 0; 
    $lista_nomes_cadastrados = [];
    $cache_turmas = [];
    
    $stmt_ins = $conn->prepare("INSERT IGNORE INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, foto_ususario, nivel_usuario, competidor_usuario, mesario_usuario, genero_usuario, data_nasc_usuario, turmas_id_turma) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_t = $conn->prepare("SELECT id_turma FROM turmas WHERE nome_turma = ?");
    
    foreach ($alunos as $aluno) {
        $nome_usuario = trim($aluno['nome'] ?? '');
        $nome_turma = trim($aluno['turma'] ?? '');
        $matricula_usuario = preg_replace('/[^0-9.]/', '', $aluno['rm'] ?? '');
        
        $d = explode('/', $aluno['data_nascimento'] ?? '');
        $data_nasc_usuario = (count($d) === 3) ? "{$d[2]}-{$d[1]}-{$d[0]}" : null;
        $genero_usuario = (strtoupper($aluno['genero'] ?? '') === 'FEM') ? 'FEM' : 'MASC';
        
        if (!isset($cache_turmas[$nome_turma])) {
            $stmt_t->bind_param("s", $nome_turma);
            $stmt_t->execute();
            $row = $stmt_t->get_result()->fetch_assoc();
            $cache_turmas[$nome_turma] = $row['id_turma'] ?? null;
        }
        
        $turmas_id_turma = $cache_turmas[$nome_turma];
        if (!$turmas_id_turma) { $erros++; continue; }
        
        // Supondo que $data_nasc_usuario seja "2000-12-30"
        $partes = explode('-', $data_nasc_usuario); 

        // $partes[0] = "2000" (Ano)
        // $partes[1] = "12"   (Mês)
        // $partes[2] = "30"   (Dia)

        $senha_limpa = $partes[2] . $partes[1] . $partes[0]; // Resultado: "30122000"

        $senha_usuario = password_hash($senha_limpa, PASSWORD_DEFAULT);
        $sigla_usuario = 'RM'; $foto_ususario = 'default.jpg'; $nivel_usuario = '0'; $competidor_usuario = '1'; $mesario_usuario = '0';
        
        $stmt_ins->bind_param("ssssssssssi", $sigla_usuario, $matricula_usuario, $nome_usuario, $senha_usuario, $foto_ususario, $nivel_usuario, $competidor_usuario, $mesario_usuario, $genero_usuario, $data_nasc_usuario, $turmas_id_turma);
        
        if ($stmt_ins->execute()) { 
            if ($stmt_ins->affected_rows > 0) {
                $sucessos++;
                $lista_nomes_cadastrados[] = $nome_usuario;
            } else { $ignorados++; }
        } else { $erros++; }
    }
    $stmt_ins->close(); $stmt_t->close();
    
    return [
        "status" => "sucesso", 
        "quantidade_novos" => $sucessos, 
        "quantidade_ignorados" => $ignorados, 
        "quantidade_erros" => $erros,
        "alunos_inseridos" => $lista_nomes_cadastrados
    ];
}

switch($metodo) {
    case 'GET':
        if ($acao == 'listar_competidores') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, turmas_id_turma FROM usuarios WHERE nivel_usuario = '0'";
            $res = $conn->query($sql);
            echo json_encode(["status" => "sucesso", "usuarios" => $res->fetch_all(MYSQLI_ASSOC)]);
        } 
        else if ($acao == 'cadastrar_competidores') {
            // PERMITE CADASTRAR VIA NAVEGADOR PARA TESTE
            echo json_encode(importarCompetidores($conn));
        }
        else if ($acao == 'listar_admins') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario FROM usuarios WHERE nivel_usuario = '2'";
            echo json_encode(["status" => "sucesso", "usuarios" => $conn->query($sql)->fetch_all(MYSQLI_ASSOC)]);
        }
        else {
            echo json_encode(["status" => "erro", "mensagem" => "Ação GET inválida"]);
        }
        break;

    case 'POST':
        if($acao == 'cadastrar_competidores') {
            echo json_encode(importarCompetidores($conn));
        } 
        else if ($acao == 'cadastrar_admin') {
            $nome_usuario = trim($input_data['nome_usuario'] ?? '');
            $matricula_usuario = trim($input_data['matricula_usuario'] ?? '');
            $senha_crua = $input_data['senha_usuario'] ?? '';
            
            $nivel_usuario = $input_data['nivel_usuario'] ?? '2'; 
            $competidor_usuario = $input_data['competidor_usuario'] ?? '0'; 
            $mesario_usuario = $input_data['mesario_usuario'] ?? '0';
            $foto_ususario = 'admin.jpg';
            
            if (!empty($nome_usuario) && !empty($matricula_usuario) && !empty($senha_crua)) {
                $senha_usuario = password_hash($senha_crua, PASSWORD_DEFAULT);
                $stmt_prof = $conn->prepare("INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, competidor_usuario, mesario_usuario, foto_ususario) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_prof->bind_param("sssssss", $nome_usuario, $matricula_usuario, $senha_usuario, $nivel_usuario, $competidor_usuario, $mesario_usuario, $foto_ususario);
                
                if ($stmt_prof->execute()) { 
                    echo json_encode(["status" => "sucesso", "mensagem" => "OK", "nome" => $nome_usuario]); 
                } else { 
                    echo json_encode(["status" => "erro", "mensagem" => $stmt_prof->error]); 
                }
                $stmt_prof->close();
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos"]);
            }
        }

        break;

    
}

$conn->close();
?>