<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
header('Content-Type: text/html; charset=utf-8');

$acao = isset($_GET['acao']) ? $_GET['acao'] : null;

if ($acao === 'cadastrar_competidores') {
    // 1. Definir o arquivo e extrair o nome da turma
    $nome_arquivo = '3EM A.json'; 
    $caminho_json = __DIR__ . '/json_turmas/' . $nome_arquivo;
    
    // Remove a extensão ".json" para buscar o nome puro no banco
    $nome_turma_limpo = str_replace('.json', '', $nome_arquivo);

    echo "<h3>Iniciando processamento da turma: <b>$nome_turma_limpo</b></h3>";

    // 2. Buscar o ID da turma no banco de dados dinamicamente
    $sql_turma = "SELECT id_turma FROM turmas WHERE nome_turma = ?";
    $stmt_t = $conn->prepare($sql_turma);
    $stmt_t->bind_param("s", $nome_turma_limpo);
    $stmt_t->execute();
    $res_t = $stmt_t->get_result();
    $dados_turma = $res_t->fetch_assoc();

    if (!$dados_turma) {
        die("❌ ERRO: A turma '<b>$nome_turma_limpo</b>' não foi encontrada na tabela 'turmas'. Cadastre-a primeiro no sistema.");
    }

    $id_turma = $dados_turma['id_turma'];
    echo "✅ Turma encontrada! ID interno: <b>$id_turma</b><br><br>";

    // 3. Processar o JSON
    if (!file_exists($caminho_json)) {
        die("❌ Erro: Arquivo $caminho_json não encontrado.");
    }

    $alunos = json_decode(file_get_contents($caminho_json), true);
    $sucessos = 0;
    $erros = [];

    // 4. Preparar o INSERT dos usuários
    $sql = "INSERT INTO usuarios (
        sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, 
        foto_ususario, nivel_usuario, competidor_usuario, mesario_usuario, 
        genero_usuario, data_nasc_usuario, turmas_id_turma
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    foreach ($alunos as $aluno) {
        // Formatação de Data
        $d = explode('/', $aluno['data_nascimento']);
        $data_f = (count($d) == 3) ? $d[2] . '-' . $d[1] . '-' . $d[0] : null;

        // Configurações Padrão
        $sigla = 'RM';
        $foto = 'default.jpg';
        $nivel = '0';
        $comp = '1';
        $mes = '0';
        $senha = str_replace('-', '', $data_f); // Senha inicial é a data

        $stmt->bind_param(
            "ssssssssssi",
            $sigla, $aluno['rm'], $aluno['nome'], $senha,
            $foto, $nivel, $comp, $mes,
            $aluno['genero'], $data_f, $id_turma
        );

        if ($stmt->execute()) {
            $sucessos++;
        } else {
            $erros[] = "Erro no aluno " . $aluno['nome'] . ": " . $stmt->error;
        }
    }

    echo "📊 <b>Resultado:</b> $sucessos cadastrados com sucesso.";
    if (count($erros) > 0) {
        echo "<h4>Erros:</h4><ul>";
        foreach ($erros as $e) echo "<li>$e</li>";
        echo "</ul>";
    }
}
?>