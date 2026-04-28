<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosJogos();

        $sql = "SELECT 
                    jogos.id_jogo, 
                    jogos.nome_jogo, 
                    jogos.data_jogo, 
                    jogos.inicio_jogo, 
                    jogos.terminno_jogo, 
                    jogos.status_jogo, 
                    modalidades.nome_modalidade, 
                    locais.nome_local,
                    categorias.nome_categoria
                FROM jogos 
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC";

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

        if (!isset($data->nome_jogo, $data->data_jogo, $data->modalidades_id_modalidade, $data->locais_id_local)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $inicio = $data->inicio_jogo ?? '00:00:00';
        $termino = $data->terminno_jogo ?? '00:00:00';
        $status = $data->status_jogo ?? 'Agendado';

        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, terminno_jogo, modalidades_id_modalidade, locais_id_local, status_jogo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiis",
            $data->nome_jogo,
            $data->data_jogo,
            $inicio,
            $termino,
            $data->modalidades_id_modalidade,
            $data->locais_id_local,
            $status
        );

        try {
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Jogo cadastrado com sucesso!",
                    "id" => $conn->insert_id
                ]);
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(400); // Bad Request
            echo json_encode([
                "success" => false,
                "message" => "Erro de integridade: Verifique se o ID da Modalidade ou do Local existem.",
                "detalhes" => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_jogo)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID do jogo é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->nome_jogo)) {
            $campos[] = "nome_jogo = ?";
            $params[] = $data->nome_jogo;
            $types .= "s";
        }
        if (isset($data->data_jogo)) {
            $campos[] = "data_jogo = ?";
            $params[] = $data->data_jogo;
            $types .= "s";
        }
        if (isset($data->inicio_jogo)) {
            $campos[] = "inicio_jogo = ?";
            $params[] = $data->inicio_jogo;
            $types .= "s";
        }
        if (isset($data->termino_jogo)) {
            $campos[] = "termino_jogo = ?";
            $params[] = $data->termino_jogo;
            $types .= "s";
        }
        if (isset($data->status_jogo)) {
            $campos[] = "status_jogo = ?";
            $params[] = $data->status_jogo;
            $types .= "s";
        }
        if (isset($data->modalidades_id_modalidade)) {
            $campos[] = "modalidades_id_modalidade = ?";
            $params[] = $data->modalidades_id_modalidade;
            $types .= "i";
        }
        if (isset($data->locais_id_local)) {
            $campos[] = "locais_id_local = ?";
            $params[] = $data->locais_id_local;
            $types .= "i";
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado enviado para atualização."]);
            break;
        }

        $sql = "UPDATE jogos SET " . implode(", ", $campos) . " WHERE id_jogo = ?";
        $params[] = $data->id_jogo;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Jogo atualizado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Metodo não permitido"]);
        break;
}