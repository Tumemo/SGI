<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
header('Content-Type: text/html; charset=utf-8');
// atualiza
echo 'ola';
$acao = isset($_GET['acao']) ? $_GET['acao'] : null;

if ($acao === 'cadastrar_competidores') {
    $caminho_json = __DIR__ . '/json_turmas/3EM A.json';
    $id_turma = 7; // Confirmado pelo seu diagnóstico
    
    $alunos = json_decode(file_get_contents($caminho_json), true);
    $sucessos = 0;
    $erros = [];

    // Verifique se os nomes das colunas abaixo estão EXATAMENTE iguais ao seu banco
    $sql = "INSERT INTO usuarios (
        sigla_usuario, 
        matricula_usuario, 
        nome_usuario, 
        senha_usuario, 
        foto_ususario, 
        nivel_usuario, 
        competidor_usuario, 
        mesario_usuario, 
        genero_usuario, 
        data_nasc_usuario, 
        turmas_id_turma
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    foreach ($alunos as $aluno) {
        // Tratamento de data: 06/01/2009 -> 2009-01-06
        $d = explode('/', $aluno['data_nascimento']);
        $data_f = $d[2] . '-' . $d[1] . '-' . $d[0];

        $sigla = 'RM';
        $foto = 'default.jpg';
        $nivel = '0';
        $comp = '1';
        $mes = '0';

        $stmt->bind_param(
            "ssssssssssi",
            $sigla, $aluno['rm'], $aluno['nome'], $data_f,
            $foto, $nivel, $comp, $mes,
            $aluno['genero'], $data_f, $id_turma
        );

        if ($stmt->execute()) {
            $sucessos++;
        } else {
            $erros[] = "Erro no aluno " . $aluno['nome'] . ": " . $stmt->error;
        }
    }

    echo "<h2>Resultado da Importação</h2>";
    echo "✅ Sucessos: $sucessos <br>";
    if (count($erros) > 0) {
        echo "❌ Erros detectados:<br><ul>";
        foreach ($erros as $erro) echo "<li>$erro</li>";
        echo "</ul>";
    }
}
?>