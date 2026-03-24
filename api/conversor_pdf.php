<?php
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pasta_pdf     = '../docs/lista_alunos/'; 
$pasta_destino = 'json_turmas/';

// Cria a pasta se ela não existir
if (!is_dir($pasta_destino)) {
    mkdir($pasta_destino, 0777, true);
}

// Busca todos os arquivos PDF
$arquivos = glob($pasta_pdf . "*.pdf");

if (count($arquivos) > 0) {
    foreach ($arquivos as $arquivo) {
        $alunos_da_turma = [];
        
        try {
            $pdf = $parser->parseFile($arquivo);
            $texto = $pdf->getText();
            $linhas = explode("\n", $texto);

            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if (empty($linha)) continue;

                // Regex para capturar o padrão: [Numero] [Nome] [Data] [RM]
                if (preg_match('/^(\d+)\s+[A-Z]/i', $linha)) {
                    
                    // Extrai Data de Nascimento (dd/mm/aaaa)
                    preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha, $m_data);
                    $data_nasc = $m_data[1] ?? "";
                    
                    // Extrai RM (ex: 123.456)
                    preg_match('/(\d+\.\d+)/', $linha, $m_rm);
                    $rm = $m_rm[1] ?? "";

                    // Limpa o nome: remove o número inicial e tudo após a data
                    $nome_sujo = preg_replace('/^\d+\s+/', '', $linha); 
                    $partes_nome = explode($data_nasc, $nome_sujo);
                    $nome_preliminar = $partes_nome[0] ?? "";
                    
                    // Remove siglas de estado ou classe no final (ex: SP ou 3A)
                    $nome = trim(preg_replace('/[A-Z0-9]{2}$/', '', trim($nome_preliminar)));

                    if (!empty($nome) && !empty($data_nasc)) {
                        $alunos_da_turma[] = [
                            'nome'            => mb_strtoupper($nome, 'UTF-8'),
                            'data_nascimento' => $data_nasc,
                            'rm'              => $rm
                        ];
                    }
                }
            }

            // Gera o arquivo JSON
            if (!empty($alunos_da_turma)) {
                $nomeJson = basename($arquivo, ".pdf") . '.json';
                
                // JSON_UNESCAPED_SLASHES: Para a data não ficar com barras invertidas (\/)
                $jsonFinal = json_encode($alunos_da_turma, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                file_put_contents($pasta_destino . $nomeJson, $jsonFinal);
                echo "Arquivo convertido: <b>$nomeJson</b><br>";
            }

        } catch (Exception $e) {
            echo "Erro ao processar " . basename($arquivo) . ": " . $e->getMessage() . "<br>";
        }
    }
    echo "<br><b>Conversão finalizada com sucesso!</b>";
} else {
    echo "Nenhum arquivo PDF encontrado na pasta $pasta_pdf";
}