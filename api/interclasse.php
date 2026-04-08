<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function uploadRegulamento($file) {
    $diretorioDestino = "../uploads/regulamentos/";
    if (!is_dir($diretorioDestino)) mkdir($diretorioDestino, 0777, true);

    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extensao !== 'pdf') return ["success" => false, "message" => "O arquivo deve ser um PDF."];
    if ($file['error'] !== UPLOAD_ERR_OK) return ["success" => false, "message" => "Erro no upload do arquivo."];

    $novoNome = "reg_" . uniqid() . "." . $extensao;
    $caminhoCompleto = $diretorioDestino . $novoNome;

    return move_uploaded_file($file['tmp_name'], $caminhoCompleto)
        ? ["success" => true, "nome_arquivo" => $novoNome]
        : ["success" => false, "message" => "Falha ao salvar o arquivo."];
}

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosInterclasse();
        $querRegulamento = isset($_GET['regulamento']) && $_GET['regulamento'] === 'true';

        $colunas = $querRegulamento ? "id_interclasse, nome_interclasse, ano_interclasse, regulamento_interclasse" : "id_interclasse, nome_interclasse, ano_interclasse";

        $sql = "SELECT $colunas FROM interclasses WHERE 1=1" . $filtro['sql'];
        $sql .= " ORDER BY ano_interclasse DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // Agora o POST volta a ler JSON para criar o registro básico
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_interclasse) || !isset($data->ano_interclasse)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nome e Ano são obrigatórios."]);
            break;
        }

        $sql = "INSERT INTO interclasses (nome_interclasse, ano_interclasse) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $data->nome_interclasse, $data->ano_interclasse);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Interclasse criado! Use o PUT para adicionar o regulamento.", "id" => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'PUT':
        /* Nota importante: O PHP tem dificuldades em ler $_FILES com o método PUT puro.
           Para facilitar o teste no Postman, vamos usar o POST com um parâmetro de ID na URL.
           Ou manter como PUT mas enviar como form-data.
        */
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $arquivo = $_FILES['pdf_regulamento'] ?? null;

        if (!$id || !$arquivo) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID do interclasse e o arquivo PDF são obrigatórios."]);
            break;
        }

        $resultadoUpload = uploadRegulamento($arquivo);

        if ($resultadoUpload['success']) {
            $sql = "UPDATE interclasses SET regulamento_interclasse = ? WHERE id_interclasse = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $resultadoUpload['nome_arquivo'], $id);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Regulamento adicionado com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao atualizar banco: " . $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode($resultadoUpload);
        }
        break;
}