<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosCategorias();


        $sql = "SELECT DISTINCT 
                    categorias.id_categoria, 
                    categorias.nome_categoria 
                FROM categorias
                INNER JOIN modalidades ON modalidades.categorias_id_categoria = categorias.id_categoria
                INNER JOIN interclasses ON interclasses.id_interclasse = modalidades.interclasses_id_interclasse
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY categorias.nome_categoria ASC";

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_categoria) || empty(trim($data->nome_categoria))) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O campo nome_categoria é obrigatório."
            ]);
            break;
        }

        $sql = "INSERT INTO categorias (nome_categoria, status_categoria) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $data->nome_categoria, $data->status_categoria);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Categoria cadastrada com sucesso!",
                "id_categoria" => $conn->insert_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro ao salvar: " . $conn->error
            ]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        // Validação básica do ID
        if (!isset($data->id_categoria)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da categoria é obrigatório."]);
            break;
        }

        // 1. PRIMEIRO: Verificar o status atual no banco de dados
        $checkSql = "SELECT status_categoria FROM categorias WHERE id_categoria = ?";
        $stmtCheck = $conn->prepare($checkSql);
        $stmtCheck->bind_param("i", $data->id_categoria);
        $stmtCheck->execute();
        $res = $stmtCheck->get_result()->fetch_assoc();

        if (!$res) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Categoria não encontrada."]);
            break;
        }

        // 2. REGRA DE NEGÓCIO: Se o status já for '0', não permite alteração
        // Isso impede "deletar" o que já está deletado ou editar dados históricos
        if ($res['status_categoria'] === '0') {
            http_response_code(403); // Proibido
            echo json_encode([
                "success" => false, 
                "message" => "Esta categoria está desativada e não pode ser alterada."
            ]);
            break;
        }

        // 3. PREPARAÇÃO DA ATUALIZAÇÃO DINÂMICA
        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->nome_categoria)) {
            $campos[] = "nome_categoria = ?";
            $params[] = trim($data->nome_categoria);
            $types .= "s";
        }

        if (isset($data->status_categoria)) {
            $campos[] = "status_categoria = ?";
            $params[] = $data->status_categoria;
            $types .= "s"; // Enum tratado como string
        }

        if (empty($campos)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nenhum dado enviado para atualizar."]);
            break;
        }

        // Montagem final do SQL
        $sql = "UPDATE categorias SET " . implode(", ", $campos) . " WHERE id_categoria = ?";
        $params[] = $data->id_categoria;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Categoria atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro no servidor: " . $conn->error]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
