<?php
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pasta_pdf     = '../docs/lista_alunos/'; 
$pasta_destino = 'json_turmas/';

if (!is_dir($pasta_destino)) {
    mkdir($pasta_destino, 0777, true);
}

$arquivos = glob($pasta_pdf . "*.pdf");

if (count($arquivos) > 0) {
    foreach ($arquivos as $arquivo) {
        $alunos_da_turma = [];
        
        try {
            $pdf = $parser->parseFile($arquivo);
            $texto = $pdf->getText();
            $linhas = explode("\n", $texto);

            foreach ($linhas as $linha) {
                // Remove espaços extras, mas mantém a estrutura interna da linha
                $linha_limpa = trim($linha);
                if (empty($linha_limpa)) continue;

                // 1. Identifica se a linha começa com o número da chamada
                if (preg_match('/^(\d+)\s+/', $linha_limpa)) {
                    
                    // EXTRAÇÃO DA DATA (dd/mm/aaaa)
                    preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha_limpa, $m_data);
                    $data_nasc = $m_data[1] ?? "";
                    
                    // EXTRAÇÃO DO RM (Padrão: números com ponto, ex: 3.263)
                    preg_match('/(\d+\.\d+)/', $linha_limpa, $m_rm);
                    $rm = $m_rm[1] ?? "";

                    // --- NOVA LÓGICA DE GÊNERO (BASEADA NA POSIÇÃO DA DATA) ---
                    $genero = "MASC"; 
                    if (!empty($data_nasc)) {
                        // Procuramos a data na linha e pegamos o que vem DEPOIS dela
                        // No seu caso: 06/01/2009F3.263 -> o 'F' está logo após a data
                        $posicao_data = strpos($linha_limpa, $data_nasc);
                        $depois_da_data = substr($linha_limpa, $posicao_data + strlen($data_nasc), 5);
                        
                        // Verifica se existe a letra F ou M nesse trecho após a data
                        if (stripos($depois_da_data, 'F') !== false) {
                            $genero = "FEM";
                        } else {
                            $genero = "MASC";
                        }
                    }
                    // ---------------------------------------------------------

                    // LIMPEZA DO NOME
                    // Pegamos apenas o que vem antes da data ou de tabulações
                    $nome_bruto = preg_replace('/^\d+\s+/', '', $linha_limpa);
                    
                    // Se houver tabulação ou a data, cortamos ali para isolar o nome
                    $divisor = !empty($data_nasc) ? $data_nasc : "\t";
                    $partes = explode($divisor, $nome_bruto);
                    $nome_final = trim($partes[0]);
                    
                    // Remove siglas como "TR", "SP" que ficam grudadas no final do nome
                    $nome_final = preg_replace('/\s*[A-Z]{2}$/', '', $nome_final);

                    if (!empty($nome_final) && !empty($data_nasc)) {
                        $alunos_da_turma[] = [
                            'nome'            => mb_strtoupper($nome_final, 'UTF-8'),
                            'data_nascimento' => $data_nasc,
                            'rm'              => $rm,
                            'genero'          => $genero
                        ];
                    }
                }
            }

            if (!empty($alunos_da_turma)) {
                $nomeJson = basename($arquivo, ".pdf") . '.json';
                $jsonFinal = json_encode($alunos_da_turma, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($pasta_destino . $nomeJson, $jsonFinal);
                echo "Arquivo <b>$nomeJson</b> gerado com sucesso!<br>";
            }

        } catch (Exception $e) {
            echo "Erro no arquivo " . basename($arquivo) . ": " . $e->getMessage() . "<br>";
        }
    }
}
?>  