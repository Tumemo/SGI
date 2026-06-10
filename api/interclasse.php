<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once __DIR__ . '/includes/locais_padrao.php';
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
        $detalheEdicao = !empty($_GET['id_interclasse']);
        $colunas = ($querRegulamento || $detalheEdicao) ? '*' : 'id_interclasse, nome_interclasse, ano_interclasse';
        $sql = "SELECT $colunas FROM interclasses WHERE 1=1" . $filtro['sql'] . " ORDER BY ano_interclasse DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($filtro['params'])) $stmt->bind_param($filtro['types'], ...$filtro['params']);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $id = $_GET['id'] ?? null;

        if ($id) {
            // CORREÇÃO: Coleta os dados de $_POST de forma consistente para formulários multipart/form-data
            $dados = $_POST; 

            $campos = [];
            $params = [];
            $types = "";

            if (isset($dados['nome_interclasse'])) {
                $campos[] = "nome_interclasse = ?";
                $params[] = $dados['nome_interclasse'];
                $types .= "s";
            }
            if (isset($dados['ano_interclasse'])) {
                $campos[] = "ano_interclasse = ?";
                $params[] = $dados['ano_interclasse'];
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
            if (isset($dados['status_interclasse'])) {
                $campos[] = "status_interclasse = ?";
                $params[] = $dados['status_interclasse'];
                $types .= "s";
            }
            if (isset($dados['status_interclasse']) && $dados['status_interclasse'] === '1') {
                $deactivate = $conn->prepare(
                    "UPDATE interclasses SET status_interclasse = '0' WHERE id_interclasse != ? AND status_interclasse = '1'"
                );
                if (!$deactivate) {
                    echo json_encode(["success" => false, "message" => "Falha ao desativar interclasses anteriores: " . $conn->error]);
                    break;
                }
                $deactivate->bind_param("i", $id);
                if (!$deactivate->execute()) {
                    echo json_encode(["success" => false, "message" => "Falha ao desativar interclasses anteriores: " . $deactivate->error]);
                    $deactivate->close();
                    break;
                }
                $deactivate->close();
            }
            if (isset($dados['valor_item_arrecadacao'])) {
                $campos[] = "valor_item_arrecadacao = ?";
                $params[] = $dados['valor_item_arrecadacao'];
                $types .= "i";
            }
            if (isset($dados['ponto_1_lugar'])) {
                $campos[] = "ponto_1_lugar = ?";
                $params[] = (int) $dados['ponto_1_lugar'];
                $types .= "i";
            }
            if (isset($dados['ponto_2_lugar'])) {
                $campos[] = "ponto_2_lugar = ?";
                $params[] = (int) $dados['ponto_2_lugar'];
                $types .= "i";
            }
            if (isset($dados['ponto_3_lugar'])) {
                $campos[] = "ponto_3_lugar = ?";
                $params[] = (int) $dados['ponto_3_lugar'];
                $types .= "i";
            }

            if (empty($campos)) {
                echo json_encode(["success" => false, "message" => "Nenhum campo fornecido para atualização."]);
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
                echo json_encode(["success" => false, "message" => $stmt->error]);
            }
        }  else {
            // ALTERADO: Agora lê de $_POST por causa do FormData (envio de arquivos)
            $nome_interclasse = $_POST['nome_interclasse'] ?? null;
            $ano_interclasse = $_POST['ano_interclasse'] ?? null;

            if (!$nome_interclasse || !$ano_interclasse) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Dados incompletos. Nome e Ano são obrigatórios."]);
                break;
            }
            
            // Verifica se enviou regulamento já na criação
            $nome_regulamento = '';
            if (isset($_FILES['pdf_regulamento']) && $_FILES['pdf_regulamento']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadRegulamento($_FILES['pdf_regulamento']);
                if ($upload['success']) {
                    $nome_regulamento = $upload['nome_arquivo'];
                } else {
                    echo json_encode(["success" => false, "message" => $upload['message']]);
                    break;
                }
            }

            // Inserindo com o regulamento (se houver) e status ativo '1'
            $sql = "INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse) VALUES (?, ?, ?, '1')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nome_interclasse, $ano_interclasse, $nome_regulamento);
            
            if ($stmt->execute()) {
                $new_interclass_id = $conn->insert_id;

                $conn->begin_transaction();

                $categoria_i_id = null;
                $categoria_ii_id = null;
                
                $categoria_insert = $conn->prepare("INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, '1', ?)");
                
                $cat_i_nome = "Categoria I - Inter " . $new_interclass_id; 
                $categoria_insert->bind_param("si", $cat_i_nome, $new_interclass_id);
                if (!$categoria_insert->execute()) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Falha Categoria I"]); break; }
                $categoria_i_id = $conn->insert_id;

                $cat_ii_nome = "Categoria II - Inter " . $new_interclass_id;
                $categoria_insert->bind_param("si", $cat_ii_nome, $new_interclass_id);
                if (!$categoria_insert->execute()) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Falha Categoria II"]); break; }
                $categoria_ii_id = $conn->insert_id;
                $categoria_insert->close();

                $tipo_mata_mata_id = null;
                $tipo_individual_id = null;
                $tipo_select = $conn->query("SELECT id_tipo_modalidade, nome_tipo_modalidade FROM tipos_modalidades");
                while ($row = $tipo_select->fetch_assoc()) {
                    if ($row['nome_tipo_modalidade'] === 'Mata-Mata') $tipo_mata_mata_id = (int)$row['id_tipo_modalidade'];
                    if ($row['nome_tipo_modalidade'] === 'Individual') $tipo_individual_id = (int)$row['id_tipo_modalidade'];
                }

                if (!$tipo_mata_mata_id) {
                    $conn->query("INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade) VALUES ('Mata-Mata', '1')");
                    $tipo_mata_mata_id = $conn->insert_id;
                }
                if (!$tipo_individual_id) {
                    $conn->query("INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade) VALUES ('Individual', '1')");
                    $tipo_individual_id = $conn->insert_id;
                }

                $turmas_sql = "INSERT INTO turmas 
                (nome_turma, turno_turma, nome_fantasia_turma, status_turma, interclasses_id_interclasse, categorias_id_categoria) 
                VALUES 
                ('6EF', 'manha', 'Sexto Ano', '1', $new_interclass_id, $categoria_i_id),
                ('7EF', 'manha', 'Sétimo Ano', '1', $new_interclass_id, $categoria_i_id),
                ('8EF', 'manha', 'Oitavo Ano', '1', $new_interclass_id, $categoria_i_id),
                ('9EF', 'manha', 'Nono Ano', '1', $new_interclass_id, $categoria_ii_id),
                ('1EMA', 'manha', '1º Ano Médio', '1', $new_interclass_id, $categoria_ii_id),
                ('2EMA', 'manha', '2º Ano Médio', '1', $new_interclass_id, $categoria_ii_id),
                ('3EMA', 'manha', '3º Ano Médio', '1', $new_interclass_id, $categoria_ii_id)";
                
                if (!$conn->query($turmas_sql)) {
                    $conn->rollback();
                    echo json_encode(["success" => false, "message" => "Falha ao inserir turmas: " . $conn->error]);
                    break;
                }

                $modalidades_sql = "INSERT INTO modalidades
                (nome_modalidade, genero_modalidade, max_inscrito_modalidade, status_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria, interclasses_id_interclasse)
                VALUES
                ('Futsal', 'MISTO', 12, '1', $tipo_mata_mata_id, $categoria_i_id, $new_interclass_id),
                ('Queimada', 'MISTO', 15, '1', $tipo_mata_mata_id, $categoria_i_id, $new_interclass_id),
                ('Volei', 'MISTO', 10, '1', $tipo_mata_mata_id, $categoria_i_id, $new_interclass_id),
                ('Corrida', 'MISTO', 2, '1', $tipo_individual_id, $categoria_i_id, $new_interclass_id),
                ('Futsal', 'MISTO', 12, '1', $tipo_mata_mata_id, $categoria_ii_id, $new_interclass_id),
                ('Queimada', 'MISTO', 15, '1', $tipo_mata_mata_id, $categoria_ii_id, $new_interclass_id),
                ('Volei', 'MISTO', 10, '1', $tipo_mata_mata_id, $categoria_ii_id, $new_interclass_id),
                ('Corrida', 'MISTO', 2, '1', $tipo_individual_id, $categoria_ii_id, $new_interclass_id)";
                
                if (!$conn->query($modalidades_sql)) {
                    $conn->rollback();
                    echo json_encode(["success" => false, "message" => "Falha ao inserir modalidades: " . $conn->error]);
                    break;
                }

                if (function_exists('sgi_criar_locais_padrao_interclasse')) {
                    sgi_criar_locais_padrao_interclasse($conn, $new_interclass_id);
                }
                
                $conn->commit();
                echo json_encode(["success" => true, "id" => $new_interclass_id]);
            } else {
                echo json_encode(["success" => false, "message" => $stmt->error]);
            }
        }
    }
