<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Função para "instalar" (fazer upload) do PDF no servidor
function uploadRegulamento($file)
{
    $diretorioDestino = "../uploads/regulamentos/"; // Pasta onde ficarão os PDFs

    if (!is_dir($diretorioDestino)) {
        mkdir($diretorioDestino, 0777, true);
    }

    $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (strtolower($extensao) !== 'pdf') {
        return ["success" => false, "message" => "O arquivo deve ser um PDF."];
    }

    $novoNome = "reg_" . uniqid() . "." . $extensao;
    $caminhoCompleto = $diretorioDestino . $novoNome;

    if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        return ["success" => true, "nome_arquivo" => $novoNome];
    }

    return ["success" => false, "message" => "Falha ao salvar o arquivo no servidor."];
}

switch ($method) {
    case 'GET':
        $id = isset($_GET['id_interclasse']) ? intval($_GET['id_interclasse']) : null;
        $querRegulamento = isset($_GET['regulamento']) ? $_GET['regulamento'] : null;

        if ($id && $querRegulamento === 'true') {
            $sql = "SELECT regulamento_interclasse FROM interclasses WHERE id_interclasse = $id";
        } elseif ($id) {

            // $sql = "SELECT id_interclasse, nome_interclasse, ano_interclasse, regulamento_interclasse FROM interclasses WHERE id_interclasse = $id";
            $sql = "SELECT id_interclasse, nome_interclasse, ano_interclasse FROM interclasses WHERE id_interclasse = $id";
        } else {
            // Caso 3: Não passou ID, traz todos da tabela
            // $sql = "SELECT id_interclasse, nome_interclasse, ano_interclasse,  regulamento_interclasse  FROM interclasses";

            $sql = "SELECT id_interclasse, nome_interclasse, ano_interclasse FROM interclasses";
        }

        $res = $conn->query($sql);
        $interclasses = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $interclasses[] = $row;
            }
        }
        echo json_encode($interclasses);
        break;

    case 'POST':
        // Quando enviamos arquivos, usamos $_POST e $_FILES
        $nome = isset($_POST['nome_interclasse']) ? $_POST['nome_interclasse'] : null;
        $ano = isset($_POST['ano_interclasse']) ? $_POST['ano_interclasse'] : null;
        $arquivo = isset($_FILES['pdf_regulamento']) ? $_FILES['pdf_regulamento'] : null;

        if (!$nome || !$ano || !$arquivo) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nome, ano e arquivo PDF são obrigatórios."]);
            break;
        }

        // Tenta salvar o arquivo físico
        $resultadoUpload = uploadRegulamento($arquivo);

        if ($resultadoUpload['success']) {
            $nomeArquivoParaBanco = $resultadoUpload['nome_arquivo'];

            // Proteção contra SQL Injection
            $nome_sql = $conn->real_escape_string($nome);
            $ano_sql = $conn->real_escape_string($ano);
            $pdf_sql = $conn->real_escape_string($nomeArquivoParaBanco);

            $sql = "INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse) 
                    VALUES ('$nome_sql', '$ano_sql', '$pdf_sql')";

            if ($conn->query($sql) === TRUE) {
                echo json_encode(["success" => true, "message" => "Interclasse cadastrado e PDF instalado com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao gravar no banco: " . $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode($resultadoUpload);
        }
        break;

    case 'PUT':

        break;
}
