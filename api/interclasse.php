<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function uploadRegulamento($file)
{
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
        $id = $_GET['id'] ?? null;

        if ($id) {
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
            if (isset($_POST['status_interclasse'])) {
                $campos[] = "status_interclasse = ?";
                $params[] = $_POST['status_interclasse'];
                $types .= "s";
            }
            if (isset($data->valor_item_arrecadacao)) {
                $campos[] = "valor_item_arrecadacao = ?";
                $params[] = $data->valor_item_arrecadacao;
                $types .= "i";
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
                $new_interclass_id = $conn->insert_id;

                $conn->begin_transaction();

                // Cria categorias I e II para este interclasse, se ainda não existirem
                $categoria_i_id = null;
                $categoria_ii_id = null;
                $categoria_select = $conn->prepare("SELECT id_categoria, nome_categoria FROM categorias WHERE interclasses_id_interclasse = ? AND nome_categoria IN ('Categoria I', 'Categoria II')");
                $categoria_select->bind_param("i", $new_interclass_id);
                $categoria_select->execute();
                $categoria_select->bind_result($categoria_id_temp, $categoria_nome_temp);
                while ($categoria_select->fetch()) {
                    if ($categoria_nome_temp === 'Categoria I') {
                        $categoria_i_id = $categoria_id_temp;
                    } elseif ($categoria_nome_temp === 'Categoria II') {
                        $categoria_ii_id = $categoria_id_temp;
                    }
                }
                $categoria_select->close();
                if (!$categoria_i_id) {
                    $categoria_insert = $conn->prepare("INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES ('Categoria I', '1', ?)");
                    $categoria_insert->bind_param("i", $new_interclass_id);
                    if (!$categoria_insert->execute()) {
                        $conn->rollback();
                        echo json_encode(["success" => false, "message" => "Falha ao criar Categoria I: " . $conn->error]);
                        break;
                    }
                    $categoria_i_id = $conn->insert_id;
                }
                if (!$categoria_ii_id) {
                    $categoria_insert = $conn->prepare("INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES ('Categoria II', '1', ?)");
                    $categoria_insert->bind_param("i", $new_interclass_id);
                    if (!$categoria_insert->execute()) {
                        $conn->rollback();
                        echo json_encode(["success" => false, "message" => "Falha ao criar Categoria II: " . $conn->error]);
                        break;
                    }
                    $categoria_ii_id = $conn->insert_id;
                }

                // Verifica tipo de modalidade existente
                $tipo_modalidade_id = null;
                $tipo_select = $conn->query("SELECT id_tipo_modalidade FROM tipos_modalidades ORDER BY id_tipo_modalidade LIMIT 1");
                if ($tipo_select && $tipo_select->num_rows > 0) {
                    $row = $tipo_select->fetch_assoc();
                    $tipo_modalidade_id = $row['id_tipo_modalidade'];
                } else {
                    $conn->rollback();
                    echo json_encode(["success" => false, "message" => "Não há tipo de modalidade cadastrado para usar como padrão."]);
                    break;
                }

                $turmas_sql = "INSERT INTO turmas 
                (nome_turma, turno_turma, status_turma, interclasses_id_interclasse, categorias_id_categoria) 
                VALUES 
                ('6EF', 'manha', '1', $new_interclass_id, $categoria_i_id),
                ('7EF', 'manha', '1', $new_interclass_id, $categoria_i_id),
                ('8EF', 'manha', '1', $new_interclass_id, $categoria_i_id),
                ('9EF', 'manha', '1', $new_interclass_id, $categoria_ii_id),
                ('1EMA', 'manha', '1', $new_interclass_id, $categoria_ii_id),
                ('2EMA', 'manha', '1', $new_interclass_id, $categoria_ii_id),
                ('3EMA', 'manha', '1', $new_interclass_id, $categoria_ii_id)";
                if (!$conn->query($turmas_sql)) {
                    $conn->rollback();
                    echo json_encode(["success" => false, "message" => "Falha ao inserir turmas: " . $conn->error]);
                    break;
                }

                $modalidades_sql = "INSERT INTO modalidades 
                (nome_modalidade, genero_modalidade, max_inscrito_modalidade, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse) 
                VALUES 
                ('Futsal', 'MISTO', 12, '1', $tipo_modalidade_id, $categoria_i_id, $new_interclass_id),
                ('Queimada', 'MISTO', 15, '1', $tipo_modalidade_id, $categoria_i_id, $new_interclass_id),
                ('Volei', 'MISTO', 10, '1', $tipo_modalidade_id, $categoria_i_id, $new_interclass_id),
                ('Corrida', 'MISTO', 1, '1', $tipo_modalidade_id, $categoria_i_id, $new_interclass_id),
                ('Futsal', 'MISTO', 12, '1', $tipo_modalidade_id, $categoria_ii_id, $new_interclass_id),
                ('Queimada', 'MISTO', 15, '1', $tipo_modalidade_id, $categoria_ii_id, $new_interclass_id),
                ('Volei', 'MISTO', 10, '1', $tipo_modalidade_id, $categoria_ii_id, $new_interclass_id),
                ('Corrida', 'MISTO', 1, '1', $tipo_modalidade_id, $categoria_ii_id, $new_interclass_id)";
                if (!$conn->query($modalidades_sql)) {
                    $conn->rollback();
                    echo json_encode(["success" => false, "message" => "Falha ao inserir modalidades: " . $conn->error]);
                    break;
                }

                $conn->commit();
                echo json_encode(["success" => true, "id" => $new_interclass_id]);
            }
        }
        break;
}
