<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true);
$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_POST['acao'] ?? $input_data['acao'] ?? $_REQUEST['acao'] ?? ''; 

function importarCompetidores($conn) {
    // 1. Descobrir qual é o Interclasse Ativo
    $sql_ativo = "SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' LIMIT 1";
    $res_ativo = $conn->query($sql_ativo);
    $interclasse = $res_ativo->fetch_assoc();

    if (!$interclasse) {
        return ["status" => "erro", "mensagem" => "Não existe nenhum Interclasse ativo."];
    }

    $id_interclasse_ativa = $interclasse['id_interclasse'];

    // 2. Carregar o JSON gerado pelo conversor
    $caminho_json = 'json_turmas/info_alunos.json';
    if (!file_exists($caminho_json)) {
        return ["status" => "erro", "mensagem" => "Arquivo JSON não encontrado."];
    }

    $alunos = json_decode(file_get_contents($caminho_json), true);
    $sucessos = 0; 
    $erros_detalhados = [];
    $cache_turmas = [];

    // 3. Processar Alunos e Turmas
    foreach ($alunos as $aluno) {
        $nome_turma_json = trim($aluno['turma'] ?? '');

        if (!isset($cache_turmas[$nome_turma_json])) {
            $stmt_t = $conn->prepare("SELECT id_turma FROM turmas WHERE nome_turma = ?");
            $stmt_t->bind_param("s", $nome_turma_json);
            $stmt_t->execute();
            $res_t = $stmt_t->get_result()->fetch_assoc();

            if ($res_t) {
                $id_turma_final = $res_t['id_turma'];
                // Atualiza o vínculo com o interclasse atual (RF02)
                $upd_t = $conn->prepare("UPDATE turmas SET interclasses_id_interclasse = ? WHERE id_turma = ?");
                $upd_t->bind_param("ii", $id_interclasse_ativa, $id_turma_final);
                $upd_t->execute();
                $cache_turmas[$nome_turma_json] = $id_turma_final;
            } else {
                // CORREÇÃO: Inserindo a categoria padrão (ID 4) para evitar erro de Foreign Key
                $id_categoria_padrao = 4; 
                $ins_t = $conn->prepare("INSERT INTO turmas (nome_turma, interclasses_id_interclasse, status_turma, categorias_id_categoria, turno_turma) VALUES (?, ?, '1', ?, 'Integral')");
                $ins_t->bind_param("sii", $nome_turma_json, $id_interclasse_ativa, $id_categoria_padrao);
                $ins_t->execute();
                $id_turma_final = $conn->insert_id;
                $cache_turmas[$nome_turma_json] = $id_turma_final;
            }
        } else {
            $id_turma_final = $cache_turmas[$nome_turma_json];
        }
        
        // 4. Inserção do Aluno
        $rm = preg_replace('/[^0-9]/', '', $aluno['rm'] ?? '');
        $nome = trim($aluno['nome'] ?? '');
        $senha = password_hash($rm, PASSWORD_DEFAULT);
        $genero = (isset($aluno['genero']) && strtoupper($aluno['genero']) == 'FEM') ? 'FEM' : 'MASC';
        $data_nasc = (isset($aluno['data_nascimento']) && !empty($aluno['data_nascimento'])) 
                     ? implode("-", array_reverse(explode("/", $aluno['data_nascimento']))) 
                     : date('Y-m-d');

        $sql_user = "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, competidor_usuario, mesario_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma) 
                     VALUES ('RM', ?, ?, ?, '0', '1', '0', ?, ?, 'default.jpg', '1', ?)";
        
        $stmt_u = $conn->prepare($sql_user);
        $stmt_u->bind_param("sssssi", $rm, $nome, $senha, $genero, $data_nasc, $id_turma_final);

        if ($stmt_u->execute()) {
            $sucessos++;
        } else {
            if ($conn->errno != 1062) { 
                $erros_detalhados[] = "Erro no RM $rm: " . $stmt_u->error;
            }
        }
    }

    return [
        "status" => "sucesso", 
        "interclasse_vinculado" => $id_interclasse_ativa,
        "cadastrados" => $sucessos,
        "erros" => $erros_detalhados
    ];
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
// $conn->close();
?>