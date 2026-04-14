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
    $novoNome = "reg_" . uniqid() . "." . $extensao;
    $caminhoCompleto = $diretorioDestino . $novoNome;
    return move_uploaded_file($file['tmp_name'], $caminhoCompleto) 
        ? ["success" => true, "nome_arquivo" => $novoNome] 
        : ["success" => false, "message" => "Falha ao salvar arquivo."];
}

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosInterclasse();
        $querRegulamento = isset($_GET['regulamento']) && $_GET['regulamento'] === 'true';
        $colunas = $querRegulamento ? "*" : "id_interclasse, nome_interclasse, ano_interclasse";
        $sql = "SELECT $colunas FROM interclasses WHERE 1=1" . $filtro['sql'] . " ORDER BY ano_interclasse DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($filtro['params'])) $stmt->bind_param($filtro['types'], ...$filtro['params']);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
    case 'PUT':
        $id = $_GET['id'] ?? null;

        if ($id) {
            // Se for PUT, o PHP não preenche $_POST automaticamente. 
            // Para testes rápidos, usamos $_REQUEST ou garantimos o envio via POST no Postman.
            $dados = ($method === 'PUT') ? $_GET : $_POST; 
            
            $campos = [];
            $params = [];
            $types = "";

            if (isset($_POST['nome_interclasse'])) {
                $campos[] = "nome_interclasse = ?";
                $params[] = $_POST['nome_interclasse'];
                $types .= "s";
            }
            if (isset($_POST['ano_interclasse'])) {
                $campos[] = "ano_interclasse = ?";
                $params[] = $_POST['ano_interclasse'];
                $types .= "s";
            }
            if (isset($_FILES['pdf_regulamento']) && $_FILES['pdf_regulamento']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadRegulamento($_FILES['pdf_regulamento']);
                if ($upload['success']) {
                    $campos[] = "regulamento_interclasse = ?";
                    $params[] = $upload['nome_arquivo'];
                    $types .= "s";
                }
            }

            if (empty($campos)) {
                echo json_encode(["success" => false, "message" => "Dica: Use o método POST no Postman para enviar form-data corretamente."]);
                break;
            }

            $sql = "UPDATE interclasses SET " . implode(", ", $campos) . " WHERE id_interclasse = ?";
            $params[] = $id;
            $types .= "i";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Atualizado com sucesso!"]);
            } else {
                echo json_encode(["success" => false, "message" => $conn->error]);
            }
        } else {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->nome_interclasse, $data->ano_interclasse)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Dados incompletos."]);
                break;
            }
            $sql = "INSERT INTO interclasses (nome_interclasse, ano_interclasse) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $data->nome_interclasse, $data->ano_interclasse);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "id" => $conn->insert_id]);
            }
        }
        break;
}