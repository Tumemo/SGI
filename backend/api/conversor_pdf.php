<?php
require '../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pasta_pdf    = '../../docs/lista_alunos/'; 
$arquivos = glob($pasta_pdf . "*.pdf");
$alunos = [];

if(count($arquivos) > 0) {
    foreach($arquivos as $arquivo) {

        $pdf = $parser->parseFile($arquivo);
        $texto = $pdf->getText();

        $linhas = explode("\n", $texto);

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if (empty($linha)) continue;

            if (preg_match('/^(\d+)\s+[A-Z]/i', $linha)) {
                
                preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha, $m_data);
                $data_nasc = $m_data[1] ?? "";
                preg_match('/(\d+\.\d+)/', $linha, $m_rm);
                $rm = $m_rm[1] ?? "";

                preg_match('/^(\d+)/', $linha, $m_num);
                $numero = $m_num[1] ?? "";

                $nome_sujo = preg_replace('/^\d+\s+/', '', $linha); 
                $nome_sujo = explode($data_nasc, $nome_sujo)[0];
                $nome = trim(preg_replace('/[A-Z]{2}$/', '', trim($nome_sujo)));

                if (!empty($nome) && !empty($data_nasc)) {
                    $alunos[] = [
                        'numero'          => $numero,
                        'nome'            => $nome,
                        'data_nascimento' => $data_nasc,
                        'rm'              => $rm
                    ];
                }
            }
        }
    }
}


$jsonFinal = json_encode($alunos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents('alunos.json', $jsonFinal);

header('Content-Type: application/json');
echo $jsonFinal;