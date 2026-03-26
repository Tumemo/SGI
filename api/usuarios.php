<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
header('Content-Type: text/html; charset=utf-8');

$caminho_json = 'json_turmas/info_alunos.json';

if (!file_exists($caminho_json)) {
    die("❌ Erro: O arquivo <b>$caminho_json</b> não existe. Gere o JSON primeiro!");
}

$alunos = json_decode(file_get_contents($caminho_json), true);

if (empty($alunos)) {
    die("⚠️ O arquivo JSON está vazio.");
}

$sucessos = 0;
$erros = [];
$cache_turmas = []; // Armazena IDs de turmas para evitar consultas repetitivas

// 1. Preparar a Query de Inserção de Usuário
$sql_ins = "INSERT INTO usuarios (
    sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, 
    foto_ususario, nivel_usuario, competidor_usuario, mesario_usuario, 
    genero_usuario, data_nasc_usuario, turmas_id_turma
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_ins = $conn->prepare($sql_ins);

echo "<h2>🚀 Iniciando Importação do Arquivo Único</h2>";

foreach ($alunos as $aluno) {
    $nome_turma = $aluno['turma'];

    // 2. Lógica de Cache para ID da Turma
    // Se ainda não buscamos o ID dessa turma nesta execução, buscamos agora
    if (!isset($cache_turmas[$nome_turma])) {
        $stmt_t = $conn->prepare("SELECT id_turma FROM turmas WHERE nome_turma = ?");
        $stmt_t->bind_param("s", $nome_turma);
        $stmt_t->execute();
        $res_t = $stmt_t->get_result();
        $dados_turma = $res_t->fetch_assoc();
        
        // Guarda o ID no cache (ou null se não encontrar)
        $cache_turmas[$nome_turma] = $dados_turma['id_turma'] ?? null;
    }

    $id_turma_atual = $cache_turmas[$nome_turma];

    if (!$id_turma_atual) {
        $erros[] = "Turma '<b>$nome_turma</b>' não encontrada no banco (Aluno: {$aluno['nome']})";
        continue;
    }

    // 3. Tratamento de Dados do Aluno
    $d = explode('/', $aluno['data_nascimento']);
    $data_f = (count($d) === 3) ? "{$d[2]}-{$d[1]}-{$d[0]}" : null;
    
    $sigla = 'RM';
    $foto = 'default.jpg';
    $nivel = '0';
    $comp = '1';
    $mes = '0';
    $senha = str_replace('-', '', $data_f); // Ex: 20090901

    // 4. Executar Inserção
    $stmt_ins->bind_param(
        "ssssssssssi",
        $sigla, $aluno['rm'], $aluno['nome'], $data_f,
        $foto, $nivel, $comp, $mes,
        $aluno['genero'], $data_f, $id_turma_atual
    );

    if ($stmt_ins->execute()) {
        $sucessos++;
    } else {
        $erros[] = "Erro ao inserir {$aluno['nome']}: " . $stmt_ins->error;
    }
}

// 5. Relatório Final
echo "<p style='color: green;'>✅ <b>$sucessos</b> alunos importados com sucesso!</p>";

if (!empty($erros)) {
    echo "<h3>❌ Problemas encontrados:</h3><ul>";
    foreach ($erros as $erro) echo "<li>$erro</li>";
    echo "</ul>";
}

$conn->close();
?>