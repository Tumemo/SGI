<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido."]);
    exit;
}

$nomeTurma = trim($_POST['nome_turma'] ?? '');
if (!$nomeTurma || !isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Arquivo PDF e nome da turma são obrigatórios."]);
    exit;
}

$ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Somente arquivos PDF são aceitos."]);
    exit;
}

$nomeSeguro = preg_replace('/[^a-zA-Z0-9 _-]/', '', $nomeTurma);
$nomeSeguro = preg_replace('/\s+/', ' ', $nomeSeguro);
$nomeArquivo = trim($nomeSeguro) . '.pdf';
$pastaRaiz = dirname(__FILE__, 4); // Vai até vsls:/
$destino = $pastaRaiz . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'lista_alunos' . DIRECTORY_SEPARATOR . $nomeArquivo;

// Criar diretório se não existir
$pastaDestino = dirname($destino);
if (!is_dir($pastaDestino)) {
    mkdir($pastaDestino, 0777, true);
}

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Falha ao salvar o PDF."]);
    exit;
}

ob_start();
include $pastaRaiz . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'conversor_pdf.php';
$saidaConversor = trim(ob_get_clean() ?? '');

echo json_encode([
    "success" => true,
    "message" => "PDF enviado e processado.",
    "log" => $saidaConversor
]);
