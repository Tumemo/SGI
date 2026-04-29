<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Captura dados JSON (usado para ações que não enviam arquivos)
$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true);

$metodo = $_SERVER['REQUEST_METHOD'];

// Se for um FormData (cadastro com foto), os dados estarão em $_POST
$acao = $_POST['acao'] ?? $input_data['acao'] ?? $_REQUEST['acao'] ?? ''; 

function importarCompetidores($conn) {
    $caminho_json = 'json_turmas/info_alunos.json';
    
    if (!file_exists($caminho_json)) {
        return ["status" => "erro", "mensagem" => "Arquivo JSON não encontrado"];
    }

    $alunos = json_decode(file_get_contents($caminho_json), true);
    $sucessos = 0; $ignorados = 0; $erros = 0; 
    $cache_turmas = [];
    
    $sql = "INSERT IGNORE INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, foto_usuario, nivel_usuario, competidor_usuario, mesario_usuario, genero_usuario, data_nasc_usuario, status_usuario, turmas_id_turma) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql);
    $stmt_t = $conn->prepare("SELECT id_turma FROM turmas WHERE nome_turma = ?");
    
    foreach ($alunos as $aluno) {
        $nome_usuario = trim($aluno['nome'] ?? '');
        $nome_turma = trim($aluno['turma'] ?? '');
        $matricula_usuario = preg_replace('/[^0-9]/', '', $aluno['rm'] ?? '');
        
        $d = explode('/', $aluno['data_nascimento'] ?? '');
        $data_nasc_usuario = (count($d) === 3) ? "{$d[2]}-{$d[1]}-{$d[0]}" : null;
        $genero_usuario = (strtoupper($aluno['genero'] ?? '') === 'FEM') ? 'FEM' : 'MASC';
        
        if (!isset($cache_turmas[$nome_turma])) {
            $stmt_t->bind_param("s", $nome_turma);
            $stmt_t->execute();
            $row = $stmt_t->get_result()->fetch_assoc();
            $cache_turmas[$nome_turma] = $row['id_turma'] ?? null;
        }
        
        $id_turma = $cache_turmas[$nome_turma];
        if (!$id_turma) { $erros++; continue; }
        
        $senha_limpa = $matricula_usuario;
        $senha_hash = password_hash($senha_limpa, PASSWORD_DEFAULT);
        
        $sigla = 'RM'; $foto = 'default.jpg'; $nivel = '0'; $comp = '1'; $mes = '0'; $status = '1';
        
        $stmt_ins->bind_param("ssssssssssii", $sigla, $matricula_usuario, $nome_usuario, $senha_hash, $foto, $nivel, $comp, $mes, $genero_usuario, $data_nasc_usuario, $status, $id_turma);
        
        if ($stmt_ins->execute()) { 
            $stmt_ins->affected_rows > 0 ? $sucessos++ : $ignorados++;
        } else { $erros++; }
    }
    return ["status" => "sucesso", "novos" => $sucessos, "erros" => $erros];
}

switch($metodo) {
    case 'GET':
        if ($acao == 'listar_competidores') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario FROM usuarios WHERE status_usuario = '1'";            
            echo json_encode(["status" => "sucesso", "usuarios" => $conn->query($sql)->fetch_all(MYSQLI_ASSOC)]);
        } 
        break;

    case 'POST':
        if($acao == 'cadastrar_competidores') {
            echo json_encode(importarCompetidores($conn));
        } 
        else if ($acao == 'cadastrar_usuario') {
            // Se vier de FormData usa $_POST, se vier de JSON usa $input_data
            $dados = !empty($_POST) ? $_POST : $input_data;
            
            $nome = trim($dados['nome_usuario'] ?? '');
            $matricula = trim($dados['matricula_usuario'] ?? '');
            $senha_crua = $dados['senha_usuario'] ?? '';
            $data_nasc = trim($dados['data_nasc_usuario'] ?? '');
            $nivel = ($dados['is_admin_clicado'] == '1') ? '1' : '0';
            $mesario = ($nivel == '1') ? '1' : ($dados['is_mesario_clicado'] ?? '0');
            $competidor = ($nivel == '1') ? '0' : '1';
            $sigla = ($competidor == '0') ? 'SS' : ($dados['sigla_usuario'] ?? 'RM');
            
            $nome_foto_banco = 'default.jpg';

            // Lógica de Upload de Foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nome_foto_banco = "user_" . $matricula . "_" . time() . "." . $ext;
                $destino = "../uploads/fotosUsuarios/" . $nome_foto_banco;
                move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
            }

            if (!empty($nome) && !empty($matricula) && !empty($senha_crua)) {
                $senha_hash = password_hash($senha_crua, PASSWORD_DEFAULT);
                $status = '1';

                $sql = "INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, competidor_usuario, mesario_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssss", $nome, $matricula, $senha_hash, $nivel, $competidor, $mesario, $nome_foto_banco, $status, $data_nasc, $sigla);
                
                if ($stmt->execute()) {
                    echo json_encode(["status" => "sucesso", "mensagem" => "Usuário cadastrado!"]);
                } else {
                    echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
                }
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Campos incompletos"]);
            }
        }
        break;

    case "PUT":
        if (date('d/m') == '01/01') {
            $ano = date('Y');
            $sql = "UPDATE usuarios SET matricula_usuario = ($ano * 1000000) + matricula_usuario, nome_usuario = CONCAT(nome_usuario, ' (', $ano, ')'), senha_usuario = CONCAT(senha_usuario, '_old'), status_usuario = 0 WHERE status_usuario = 1";
            if ($conn->query($sql)) {
                echo json_encode(["status" => "sucesso", "mensagem" => "Reset concluído"]);
            }
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Apenas em 01/01"]);
        }
        break;
}
$conn->close();
?>