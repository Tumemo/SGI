<?php
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pasta_pdf     = '../docs/lista_alunos/'; 
$pasta_destino = 'json_turmas/';
$arquivo_final = $pasta_destino . 'info_alunos.json';

if (!is_dir($pasta_destino)) {
    mkdir($pasta_destino, 0777, true);
}

// 1. Carrega os alunos já existentes
$todos_os_alunos = [];
if (file_exists($arquivo_final)) {
    $conteudo_atual = file_get_contents($arquivo_final);
    $todos_os_alunos = json_decode($conteudo_atual, true) ?? [];
}

// Criamos um mapa (indexado pelo RM) para busca instantânea e evitar duplicados
// Isso transforma o RM na "chave" do array, garantindo unicidade absoluta.
$alunos_por_rm = [];
foreach ($todos_os_alunos as $aluno) {
    if (isset($aluno['rm'])) {
        $alunos_por_rm[$aluno['rm']] = $aluno;
    }
}

$quantidade_inicial = count($alunos_por_rm); 
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

                if (preg_match('/^(\d+)\s+/', $linha_limpa)) {
                    preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha_limpa, $m_data);
                    $data_nasc = $m_data[1] ?? "";
                    
                    preg_match('/(\d+\.\d+)/', $linha_limpa, $m_rm);
                    $rm = $m_rm[1] ?? "";

                    // Pula se o RM não for encontrado na linha
                    if (empty($rm)) continue;

                    $genero = "MASC"; 
                    if (!empty($data_nasc)) {
                        $posicao_data = strpos($linha_limpa, $data_nasc);
                        $depois_da_data = substr($linha_limpa, $posicao_data + strlen($data_nasc), 5);
                        $genero = (stripos($depois_da_data, 'F') !== false) ? "FEM" : "MASC";
                    }

                    $nome_bruto = preg_replace('/^\d+\s+/', '', $linha_limpa);
                    $divisor = !empty($data_nasc) ? $data_nasc : "\t";
                    $partes = explode($divisor, $nome_bruto);
                    $nome_final = trim($partes[0]);
                    $nome_final = preg_replace('/\s*[A-Z]{2}$/', '', $nome_final);

                    // VERIFICAÇÃO POR RM:
                    // Se o RM não existe no nosso mapa, adicionamos o novo aluno
                    if (!isset($alunos_por_rm[$rm])) {
                        $alunos_por_rm[$rm] = [
                            'nome'            => $nome_final,
                            'data_nascimento' => $data_nasc,
                            'rm'              => $rm,
                            'genero'          => $genero,
                            'turma'           => $nome_turma
                        ];
                    }
                }
            }
            echo "✅ PDF <b>$nome_turma</b> processado.<br>";

        } catch (Exception $e) {
            echo "❌ Erro ao ler $nome_turma: " . $e->getMessage() . "<br>";
        }
    }

    // Convertemos o mapa de volta para um array simples (lista) para salvar o JSON
    $lista_final = array_values($alunos_por_rm);
    $total_final = count($lista_final);
    $novos_adicionados = $total_final - $quantidade_inicial;

    $jsonFinal = json_encode($lista_final, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($arquivo_final, $jsonFinal);

    echo "<hr>";
    echo "🚀 <b>Processamento Concluído!</b><br>";
    echo "📥 Novos alunos únicos adicionados: <b>$novos_adicionados</b><br>";
    echo "📊 Total de alunos únicos no sistema: <b>$total_final</b>";

} else {
    echo "⚠️ Nenhum PDF novo encontrado em $pasta_pdf";
}
?>