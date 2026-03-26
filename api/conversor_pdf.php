<?php
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pasta_pdf     = '../docs/lista_alunos/'; 
$pasta_destino = 'json_turmas/';
$arquivo_final = $pasta_destino . 'info_alunos.json';

// 1. Garante que a pasta de destino existe
if (!is_dir($pasta_destino)) {
    mkdir($pasta_destino, 0777, true);
}

// 2. Carrega os alunos já existentes (se o arquivo já existir)
$todos_os_alunos = [];
if (file_exists($arquivo_final)) {
    $conteudo_atual = file_get_contents($arquivo_final);
    $todos_os_alunos = json_decode($conteudo_atual, true) ?? [];
}

$arquivos_pdf = glob($pasta_pdf . "*.pdf");

if (count($arquivos_pdf) > 0) {
    foreach ($arquivos_pdf as $arquivo) {
        $nome_turma = basename($arquivo, ".pdf");
        
        try {
            $pdf = $parser->parseFile($arquivo);
            $texto = $pdf->getText();
            $linhas = explode("\n", $texto);

            foreach ($linhas as $linha) {
                $linha_limpa = trim($linha);
                if (empty($linha_limpa)) continue;

                // Regex para capturar a linha de dados do aluno
                if (preg_match('/^(\d+)\s+/', $linha_limpa)) {
                    preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha_limpa, $m_data);
                    $data_nasc = $m_data[1] ?? "";
                    
                    preg_match('/(\d+\.\d+)/', $linha_limpa, $m_rm);
                    $rm = $m_rm[1] ?? "";

                    // Lógica de Gênero
                    $genero = "MASC"; 
                    if (!empty($data_nasc)) {
                        $posicao_data = strpos($linha_limpa, $data_nasc);
                        $depois_da_data = substr($linha_limpa, $posicao_data + strlen($data_nasc), 5);
                        $genero = (stripos($depois_da_data, 'F') !== false) ? "FEM" : "MASC";
                    }

                    // Limpeza do Nome
                    $nome_bruto = preg_replace('/^\d+\s+/', '', $linha_limpa);
                    $divisor = !empty($data_nasc) ? $data_nasc : "\t";
                    $partes = explode($divisor, $nome_bruto);
                    $nome_final = trim($partes[0]);
                    $nome_final = preg_replace('/\s*[A-Z]{2}$/', '', $nome_final);

                    if (!empty($nome_final) && !empty($data_nasc)) {
                        // Adiciona ao array acumulador
                        $todos_os_alunos[] = [
                            'nome'            => mb_strtoupper($nome_final, 'UTF-8'),
                            'data_nascimento' => $data_nasc,
                            'rm'              => $rm,
                            'genero'          => $genero,
                            'turma'           => $nome_turma
                        ];
                    }
                }
            }
            echo "✅ PDF <b>$nome_turma</b> processado e adicionado à lista.<br>";

        } catch (Exception $e) {
            echo "❌ Erro ao ler $nome_turma: " . $e->getMessage() . "<br>";
        }
    }

    // 3. Salva a lista consolidada (novos + antigos) no arquivo fixo
    $jsonFinal = json_encode($todos_os_alunos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($arquivo_final, $jsonFinal);

    echo "<br>🚀 <b>Concluído!</b> Arquivo <code>$arquivo_final</code> atualizado com " . count($todos_os_alunos) . " alunos no total.";

} else {
    echo "⚠️ Nenhum PDF novo encontrado em $pasta_pdf";
}
?>