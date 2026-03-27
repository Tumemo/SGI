<?php
// Exibição de erros (Apenas para ambiente de desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
header('Content-Type: text/html; charset=utf-8');

// --- CONFIGURAÇÕES DE ARQUIVOS ---
$caminho_json = 'json_turmas/info_alunos.json';
$arquivo_log = 'log_importacao.txt'; // O log será salvo na mesma pasta deste script

// Função para gravar os logs no arquivo .txt
function registrarLog($mensagem, $arquivo) {
    $dataHora = date('d/m/Y H:i:s');
    // Adiciona a mensagem ao final do arquivo (FILE_APPEND)
    file_put_contents($arquivo, "[$dataHora] $mensagem" . PHP_EOL, FILE_APPEND);
}

registrarLog("--- INICIANDO NOVA IMPORTAÇÃO ---", $arquivo_log);

if (!file_exists($caminho_json)) {
    $msg = "Erro Crítico: O arquivo $caminho_json não existe.";
    registrarLog($msg, $arquivo_log);
    die("❌ <b>$msg</b>");
}

$conteudo_json = file_get_contents($caminho_json);
$alunos = json_decode($conteudo_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $msg = "Erro ao ler o JSON: " . json_last_error_msg();
    registrarLog($msg, $arquivo_log);
    die("⚠️ <b>$msg</b>");
}

if (empty($alunos)) {
    registrarLog("Arquivo JSON vazio.", $arquivo_log);
    die("⚠️ O arquivo JSON está vazio.");
}

$lista_sucessos = [];
$lista_ignorados = [];
$lista_erros = [];
$cache_turmas = [];

// Usando INSERT IGNORE para não travar o script se o aluno já existir
$sql_ins = "INSERT IGNORE INTO usuarios (
    sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, 
    foto_ususario, nivel_usuario, competidor_usuario, mesario_usuario, 
    genero_usuario, data_nasc_usuario, turmas_id_turma
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_ins = $conn->prepare($sql_ins);

$stmt_t = $conn->prepare("SELECT id_turma FROM turmas WHERE nome_turma = ?");

echo "<h2>🚀 Relatório de Importação</h2>";

foreach ($alunos as $aluno) {
    $nome_aluno = trim($aluno['nome'] ?? 'Desconhecido');
    $nome_turma = trim($aluno['turma'] ?? '');
    
    $rm_bruto = $aluno['rm'] ?? '';
    $rm_limpo = preg_replace('/[^0-9]/', '', $rm_bruto);
    
    $data_nasc_bruta = $aluno['data_nascimento'] ?? '';
    $d = explode('/', $data_nasc_bruta);
    $data_f = (count($d) === 3) ? "{$d[2]}-{$d[1]}-{$d[0]}" : null;
    
    $genero_bruto = $aluno['genero'] ?? 'Outro';
    $genero_raw = strtoupper(substr($genero_bruto, 0, 1));
    $genero = ($genero_raw === 'M') ? 'MASC' : 'FEM';

    // Busca de Turma com Cache
    if (!isset($cache_turmas[$nome_turma])) {
        $stmt_t->bind_param("s", $nome_turma);
        $stmt_t->execute();
        $res_t = $stmt_t->get_result();
        $dados_turma = $res_t->fetch_assoc();
        $cache_turmas[$nome_turma] = $dados_turma['id_turma'] ?? null;
    }

    $id_turma_atual = $cache_turmas[$nome_turma];

    if (!$id_turma_atual) {
        $msg = "Turma '$nome_turma' não encontrada no banco.";
        $lista_erros[] = "❌ <b>$nome_aluno</b>: $msg";
        registrarLog("ERRO - $nome_aluno (RM $rm_limpo): $msg", $arquivo_log);
        continue;
    }

    // Hash da senha
    $senha_hash = password_hash($rm_limpo, PASSWORD_DEFAULT);

    // Padrões
    $sigla = 'RM';
    $foto = 'default.jpg';
    $nivel = '0';
    $comp = '1';
    $mes = '0';

    $stmt_ins->bind_param(
        "ssssssssssi",
        $sigla, $rm_limpo, $nome_aluno, $senha_hash, 
        $foto, $nivel, $comp, $mes, $genero, $data_f, $id_turma_atual
    );

    // Executa e verifica o que aconteceu de fato
    if ($stmt_ins->execute()) {
        if ($stmt_ins->affected_rows > 0) {
            // Linha inserida no banco
            $lista_sucessos[] = "✅ <b>$nome_aluno</b>";
            registrarLog("SUCESSO - Inserido: $nome_aluno (RM $rm_limpo)", $arquivo_log);
        } else {
            // Comando executou, mas afetou 0 linhas (INSERT IGNORE pulou porque já existia)
            $lista_ignorados[] = "⏭️ <b>$nome_aluno</b> (RM: $rm_limpo)";
            registrarLog("IGNORADO - Já existe: $nome_aluno (RM $rm_limpo)", $arquivo_log);
        }
    } else {
        // Erro real do MySQL (ex: campo faltando, tipo de dado errado, etc)
        $erro_banco = $stmt_ins->error;
        $lista_erros[] = "⚠️ <b>$nome_aluno</b>: Erro -> $erro_banco";
        registrarLog("ERRO MYSQL - $nome_aluno (RM $rm_limpo): $erro_banco", $arquivo_log);
    }
}

$stmt_ins->close();
$stmt_t->close();
$conn->close();

registrarLog("--- FIM DA IMPORTAÇÃO ---", $arquivo_log);

// --- EXIBIÇÃO NA TELA ---
echo "<h3>📊 Resumo da Execução: " . count($alunos) . " alunos lidos</h3>";
echo "<p style='color: green; font-weight: bold;'>✅ Novos Inseridos: " . count($lista_sucessos) . "</p>";
echo "<p style='color: #b8860b; font-weight: bold;'>⏭️ Já Existiam (Ignorados): " . count($lista_ignorados) . "</p>";
echo "<p style='color: red; font-weight: bold;'>❌ Falhas Reais: " . count($lista_erros) . "</p>";

echo "<hr><p><i>Um relatório completo foi salvo no arquivo <b>$arquivo_log</b> na mesma pasta deste script.</i></p>";

if (!empty($lista_erros)) {
    echo "<h4 style='color: red;'>Erros detalhados:</h4><ul style='color: #666;'><li>" . implode("</li><li>", $lista_erros) . "</li></ul>";
}
if (!empty($lista_ignorados)) {
    echo "<h4 style='color: #b8860b;'>Alunos Ignorados (Já no banco):</h4><ul style='color: #666;'><li>" . implode("</li><li>", $lista_ignorados) . "</li></ul>";
}
?>