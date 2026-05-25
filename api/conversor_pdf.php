<?php
// 1. Inclusões essenciais para a automação
require_once '../vendor/autoload.php';
require_once '../config/db.php';
require_once __DIR__ . '/includes/importador_competidores.php';
require_once __DIR__ . '/includes/usuario_validacao.php';

use Smalot\PdfParser\Parser;

// --- CONFIGURAÇÃO PARA AJAX ---
ob_start(); // Previne que qualquer aviso ou echo quebre o JSON
header('Content-Type: application/json');

$resposta = [
    "success" => false,
    "message" => ""
];

try {
    $parser = new Parser();
    $pasta_pdf     = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'lista_alunos' . DIRECTORY_SEPARATOR; 
    $pasta_destino = __DIR__ . DIRECTORY_SEPARATOR . 'json_turmas' . DIRECTORY_SEPARATOR;
    $arquivo_final = $pasta_destino . 'info_alunos.json';

    if (!is_dir($pasta_destino)) {
        mkdir($pasta_destino, 0777, true);
    }

    $todos_os_alunos = [];
    if (file_exists($arquivo_final)) {
        $conteudo_atual = file_get_contents($arquivo_final);
        $todos_os_alunos = json_decode($conteudo_atual, true) ?? [];
    }

    $alunos_por_rm = [];
    foreach ($todos_os_alunos as $aluno) {
        if (!is_array($aluno) || !isset($aluno['rm'])) {
            continue;
        }
        $turmaKey = trim((string) ($aluno['turma'] ?? ''));
        $rmNorm = sgi_normalizar_ra($aluno['rm']);
        if ($rmNorm === '') {
            continue;
        }
        $alunos_por_rm[$turmaKey . '|' . $rmNorm] = $aluno;
    }

    $id_turma_post = isset($_POST['id_turma']) ? (int) $_POST['id_turma'] : 0;
    $nome_turma_vinculo = null;
    if ($id_turma_post > 0) {
        $stTurma = $conn->prepare('SELECT nome_turma FROM turmas WHERE id_turma = ? LIMIT 1');
        if ($stTurma) {
            $stTurma->bind_param('i', $id_turma_post);
            $stTurma->execute();
            $rowTurma = $stTurma->get_result()->fetch_assoc();
            $stTurma->close();
            $nome_turma_vinculo = isset($rowTurma['nome_turma']) ? (string) $rowTurma['nome_turma'] : null;
        }
    }

    $arquivo_turma = $id_turma_post > 0 ? $pasta_pdf . 'turma_' . $id_turma_post . '.pdf' : null;
    $arquivos_pdf = ($arquivo_turma && is_file($arquivo_turma))
        ? [$arquivo_turma]
        : glob($pasta_pdf . "*.pdf");

    if (count($arquivos_pdf) > 0) {
        foreach ($arquivos_pdf as $arquivo) {
            $nome_turma = $nome_turma_vinculo ?? basename($arquivo, ".pdf");
            
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

                        $rmNorm = sgi_normalizar_ra($rm);
                        if ($rmNorm === '') {
                            continue;
                        }
                        $chaveRm = $nome_turma . '|' . $rmNorm;
                        if (!isset($alunos_por_rm[$chaveRm])) {
                            $alunos_por_rm[$chaveRm] = [
                                'nome'            => $nome_final,
                                'data_nascimento' => $data_nasc,
                                'rm'              => $rm,
                                'genero'          => $genero,
                                'turma'           => $nome_turma
                            ];
                        }
                    }
                }
                // PDF $nome_turma processed silently (no direct output)

            } catch (Exception $e) {
                // Error reading this PDF; keep processing but don't echo HTML
                $processError = "Erro ao ler $nome_turma: " . $e->getMessage();
            }
        }
        $lista_final = array_values($alunos_por_rm);
        $jsonFinal = json_encode($lista_final, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($arquivo_final, $jsonFinal);

        // --- SINCRONIZAÇÃO COM O BANCO ---
        // Use os IDs vindos diretamente do POST (enviados via FormData no frontend)
        $id_interclasse_post = isset($_POST['id_interclasse']) ? (int)$_POST['id_interclasse'] : 0;
        $id_categoria_post = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 0;
        $id_turma_post = isset($_POST['id_turma']) ? (int)$_POST['id_turma'] : null;

        $resultadoBD = importarCompetidores($conn, $id_interclasse_post, $id_categoria_post, $id_turma_post);

        if (isset($resultadoBD['status']) && $resultadoBD['status'] === 'sucesso') {
            $resposta['success'] = true;
            $cadastrados = isset($resultadoBD['cadastrados']) ? (int)$resultadoBD['cadastrados'] : 0;
            $resposta['message'] = "Importação concluída: $cadastrados registros inseridos.";
        } else {
            $mens = $resultadoBD['mensagem'] ?? 'Erro desconhecido';
            $resposta['message'] = "Falha na integração com banco: " . $mens;
        }

    } else {
        $resposta['message'] = "Nenhum PDF novo encontrado.";
    }

} catch (Exception $e) {
    $resposta['message'] = "Erro crítico: " . $e->getMessage();
}

// Limpa qualquer saída HTML/resíduos e entrega apenas o JSON
ob_end_clean();
echo json_encode($resposta);
exit;
?>