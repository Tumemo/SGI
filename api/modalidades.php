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
                    modalidades.categorias_id_categoria,
                    tipos_modalidades.nome_tipo_modalidade,
                    tipos_modalidades.id_tipo_modalidade,
                    categorias.nome_categoria
                FROM modalidades
                INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria";

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

        if (!isset($data->nome_modalidade, $data->genero_modalidade, $data->tipos_modalidades_id_tipo_modalidade, $data->categorias_id_categoria)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $genero = strtoupper(trim($data->genero_modalidade));
        $max_inscritos = $data->max_inscrito_modalidade ?? 0;

        $sql = "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii",
            $data->nome_modalidade,
            $genero,
            $max_inscritos,
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

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}