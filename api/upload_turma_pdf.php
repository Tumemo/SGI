<?php
// Recebe upload de PDF de turma e dispara o conversor
ob_start();
header('Content-Type: application/json');

$resposta = [
    'success' => false,
    'message' => ''
];

try {
    // Verifica existência do arquivo
    if (!isset($_FILES['pdf_arquivo'])) {
        throw new Exception('Nenhum arquivo enviado. Campo esperado: pdf_arquivo');
    }

    $file = $_FILES['pdf_arquivo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo. Código: ' . $file['error']);
    }

    // Campos POST
    $id_turma = isset($_POST['id_turma']) ? (int)$_POST['id_turma'] : 0;
    $id_interclasse = isset($_POST['id_interclasse']) ? (int)$_POST['id_interclasse'] : 0;
    $id_categoria = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 0;

    if ($id_turma <= 0) {
        throw new Exception('Campo id_turma inválido.');
    }

    // Valida extensão simples (não confie apenas nisso em produção)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Apenas arquivos PDF são permitidos.');
    }

    // Pasta de destino: ../docs/lista_alunos/
    $destDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'lista_alunos' . DIRECTORY_SEPARATOR;
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0777, true)) {
            throw new Exception('Falha ao criar pasta de destino.');
        }
        chmod($destDir, 0777);
    }

    $destFilename = 'turma_' . $id_turma . '.pdf';
    $destPath = $destDir . $destFilename;

    // Move o arquivo (sobrescreve se já existir)
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Falha ao salvar o arquivo no servidor.');
    }

    // Dispara o conversor via requisição HTTP local para não ser interrompido pelo exit do conversor
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $converterUrl = $protocol . '://' . $host . $scriptDir . '/conversor_pdf.php';

    // Prepara POST para o conversor
    $postFields = http_build_query([
        'id_interclasse' => $id_interclasse,
        'id_categoria' => $id_categoria
        , 'id_turma' => $id_turma
    ]);

    $ch = curl_init($converterUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Se estiver em ambiente local sem certificado válido, pode ser necessário desabilitar SSL verify
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $convResponse = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($convResponse === false) {
        throw new Exception('Erro ao chamar o conversor: ' . $curlErr);
    }

    // Tenta decodificar resposta do conversor (se for JSON)
    $convJson = json_decode($convResponse, true);

    if (is_array($convJson) && isset($convJson['success']) && $convJson['success'] === true) {
        $resposta['success'] = true;
        $resposta['message'] = 'Arquivo da turma recebido e processado!';
    } else {
        // Se conversor retornou mensagem de erro, repassa para diagnóstico, mas sem dados sensíveis
        $convMsg = '';
        if (is_array($convJson) && isset($convJson['message'])) {
            $convMsg = $convJson['message'];
        } else {
            $convMsg = 'Resposta inesperada do conversor (HTTP ' . $httpCode . ').';
        }
        $resposta['message'] = 'Arquivo salvo, mas o conversor informou: ' . $convMsg;
    }

} catch (Exception $e) {
    $resposta['message'] = $e->getMessage();
}

// Limpa buffer e retorna JSON limpo
ob_end_clean();
echo json_encode($resposta);
exit;
