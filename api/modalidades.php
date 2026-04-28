<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosModalidades();

        $sql = "SELECT DISTINCT 
                    modalidades.id_modalidade, 
                    modalidades.nome_modalidade, 
                    modalidades.genero_modalidade,
                    modalidades.max_inscrito_modalidade, 
                    modalidades.status_modalidade,
                    modalidades.categorias_id_categoria,
                    tipos_modalidades.nome_tipo_modalidade,
                    tipos_modalidades.id_tipo_modalidade,
                    categorias.nome_categoria,
                    modalidades.interclasses_id_interclasse,
                    interclasses.nome_interclasse 
                    FROM modalidades
                    INNER JOIN tipos_modalidades 
                    ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade
                    INNER JOIN categorias 
                    ON categorias.id_categoria = modalidades.categorias_id_categoria
                    INNER JOIN interclasses 
                    ON interclasses.id_interclasse = modalidades.interclasses_id_interclasse";

        if (isset($_GET['ano'])) {
            $sql .= " INNER JOIN jogos ON jogos.modalidades_id_modalidade = modalidades.id_modalidade";
        }

        $sql .= " WHERE 1=1" . $filtro['sql'];

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

        if (!isset($data->nome_modalidade, $data->genero_modalidade, $data->tipos_modalidades_id_tipo_modalidade, $data->status_modalidade, $data->categorias_id_categoria)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $genero = strtoupper(trim($data->genero_modalidade));
        $max_inscritos = $data->max_inscrito_modalidade ?? 0;

        $sql = "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, tipos_modalidades_id_tipo_modalidade, status_modalidade, categorias_id_categoria) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiii",
            $data->nome_modalidade,
            $data->genero_modalidade,
            $max_inscritos,
            $data->status_modalidade,
            $data->tipos_modalidades_id_tipo_modalidade,
            $data->categorias_id_categoria
        );

        try {
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Modalidade criada!", "id" => $conn->insert_id]);
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Erro de integridade (verifique IDs de categoria/tipo): " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        // O ID da modalidade é obrigatório para a atualização
        if (!isset($data->id_modalidade)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da modalidade é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        // Verificação dinâmica de cada campo do seu SQL
        if (isset($data->nome_modalidade)) {
            $campos[] = "nome_modalidade = ?";
            $params[] = $data->nome_modalidade;
            $types .= "s";
        }

        if (isset($data->genero_modalidade)) {
            $campos[] = "genero_modalidade = ?";
            $params[] = $data->genero_modalidade;
            $types .= "s";
        }

        if (isset($data->max_inscrito_modalidade)) {
            $campos[] = "max_inscrito_modalidade = ?";
            $params[] = $data->max_inscrito_modalidade;
            $types .= "i";
        }

        if (isset($data->status_modalidade)) {
            $campos[] = "status_modalidade = ?";
            $params[] = $data->status_modalidade;
            $types .= "s"; 
        }

        if (isset($data->tipos_modalidades_id_tipo_modalidade)) {
            $campos[] = "tipos_modalidades_id_tipo_modalidade = ?";
            $params[] = $data->tipos_modalidades_id_tipo_modalidade;
            $types .= "i";
        }

        if (isset($data->categorias_id_categoria)) {
            $campos[] = "categorias_id_categoria = ?";
            $params[] = $data->categorias_id_categoria;
            $types .= "i";
        }

        if (isset($data->interclasses_id_interclasse)) {
            $campos[] = "interclasses_id_interclasse = ?";
            $params[] = $data->interclasses_id_interclasse;
            $types .= "i";
        }

        // Verifica se houve alguma alteração enviada
        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado fornecido para atualização."]);
            break;
        }

        // Monta a Query SQL
        $sql = "UPDATE modalidades SET " . implode(", ", $campos) . " WHERE id_modalidade = ?";
        
        // Adiciona o ID para o WHERE
        $params[] = $data->id_modalidade;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        
        // Descompacta os parâmetros
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Modalidade atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro no banco: " . $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}